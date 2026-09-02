<?php
require_once 'auth.php';
require_once '../config/db.php';
check_access();

// --- 1. LOGIKA HAPUS GAME ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmtImg = $pdo->prepare("SELECT image_url FROM games WHERE id = ?");
        $stmtImg->execute([$id]);
        $game = $stmtImg->fetch();

        if ($game) {
            $filePath = "../" . $game['image_url'];
            if (file_exists($filePath) && !empty($game['image_url'])) {
                unlink($filePath);
            }
            $pdo->prepare("DELETE FROM games WHERE id = ?")->execute([$id]);
        }
        header("Location: manage_games.php?msg=deleted&category=" . ($_GET['category'] ?? '') . "&page=" . ($_GET['page'] ?? 1));
        exit;
    } catch (PDOException $e) { die("Gagal hapus: " . $e->getMessage()); }
}

// --- 2. LOGIKA UPDATE & UPLOAD (KOMPRES 10%) ---
if (isset($_POST['update_game'])) {
    $id    = (int)($_POST['game_id'] ?? 0);
    $title = safe_input($_POST['title'] ?? '');
    $desc  = safe_input($_POST['description'] ?? '');
    $cat   = safe_input($_POST['category'] ?? '');

    try {
        $stmtOld = $pdo->prepare("SELECT slug, image_url FROM games WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch();
        $final_image_path = $oldData['image_url'] ?? '';

        if (!empty($_FILES['thumb_upload']['name']) && !empty($oldData['slug'])) {
            $file = $_FILES['thumb_upload'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = $oldData['slug'] . ".jpg";
                $target_path = "../assets/thumbs/" . $filename;
                if (file_exists($target_path)) { unlink($target_path); }

                $file_size = filesize($file['tmp_name']);
                $max_size  = 20 * 1024; // 20KB

                if ($file_size > $max_size) {
                    if ($ext == 'png') $src = @imagecreatefrompng($file['tmp_name']);
                    elseif ($ext == 'webp') $src = @imagecreatefromwebp($file['tmp_name']);
                    else $src = @imagecreatefromjpeg($file['tmp_name']);

                    if ($src !== false) {
                        $quality = 80;
                        ob_start();
                        imagejpeg($src, null, $quality);
                        $compressed_data = ob_get_clean();

                        while (strlen($compressed_data) > $max_size && $quality > 10) {
                            $quality -= 10;
                            ob_start();
                            imagejpeg($src, null, $quality);
                            $compressed_data = ob_get_clean();
                        }

                        if (strlen($compressed_data) > $max_size) {
                            $orig_w = imagesx($src);
                            $orig_h = imagesy($src);
                            $scale  = 0.8;
                            $new_w  = (int)($orig_w * $scale);
                            $new_h  = (int)($orig_h * $scale);
                            $dst    = imagecreatetruecolor($new_w, $new_h);
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

                            ob_start();
                            imagejpeg($dst, null, 70);
                            $compressed_data = ob_get_clean();
                            imagedestroy($dst);
                        }

                        file_put_contents($target_path, $compressed_data);
                        imagedestroy($src);
                        $final_image_path = "assets/thumbs/" . $filename;
                    } else {
                        move_uploaded_file($file['tmp_name'], $target_path);
                        $final_image_path = "assets/thumbs/" . $filename;
                    }
                } else {
                    move_uploaded_file($file['tmp_name'], $target_path);
                    $final_image_path = "assets/thumbs/" . $filename;
                }
            }
        }

        $stmtUp = $pdo->prepare("UPDATE games SET title = ?, description = ?, category = ?, image_url = ? WHERE id = ?");
        $stmtUp->execute([$title, $desc, $cat, $final_image_path, $id]);
        header("Location: manage_games.php?msg=updated&category=" . urlencode($cat) . "&page=" . (int)($_GET['page'] ?? 1)); 
        exit;
    } catch (PDOException $e) { die("Error: " . $e->getMessage()); }
}

// --- 3. FILTER, SORTING & SLIDING PAGINATION ---
$filter_cat = isset($_GET['category']) ? safe_input($_GET['category']) : '';
$allowed_sort = ['title', 'category', 'views', 'created_at'];
$sort  = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'created_at';
$order = (isset($_GET['order']) && $_GET['order'] == 'asc') ? 'asc' : 'desc';
$toggle_order = ($order == 'asc') ? 'desc' : 'asc';

$all_cats = $pdo->query("SELECT DISTINCT category FROM games WHERE category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

$limit = 15; // SET KE 15 LIST PER HALAMAN
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = max(0, ($page - 1) * $limit);
$range = 2; 

$where_sql = $filter_cat ? "WHERE category = :cat" : "";
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM games $where_sql");
if ($filter_cat) $stmt_count->bindValue(':cat', $filter_cat);
$stmt_count->execute();
$total_items = (int)$stmt_count->fetchColumn();
$total_pages = $limit > 0 ? (int)ceil($total_items / $limit) : 1;

$stmt = $pdo->prepare("SELECT * FROM games $where_sql ORDER BY $sort $order LIMIT :limit OFFSET :offset");
if ($filter_cat) $stmt->bindValue(':cat', $filter_cat);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$games = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Games - Arcade Panel</title>
    <style>
        :root { --primary: #1f1235; --accent: #2ecc71; --bg: #f4f7f6; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 250px; background: var(--primary); color: white; min-height: 100vh; padding: 25px; box-sizing: border-box; position: sticky; top: 0; }
        .sidebar h2 { color: #ffcc00; text-align: center; font-size: 20px; font-weight: 900; }
        .sidebar a { color: #ccc; text-decoration: none; display: block; padding: 12px 15px; border-radius: 10px; margin-bottom: 5px; }
        .sidebar a.active { background: #4c3575; color: #ffcc00; font-weight: bold; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        select { padding: 8px 15px; border-radius: 8px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #eee; font-size: 14px; }
        thead th a { text-decoration: none; color: #333; }
        td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 30px; flex-wrap: wrap; }
        .page-link { min-width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; padding: 0 10px; background: white; border: 1px solid #ddd; text-decoration: none; color: var(--primary); border-radius: 8px; font-weight: bold; font-size: 13px; }
        .page-link.active { background: var(--primary); color: #ffcc00; border-color: var(--primary); }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; width: 450px; border-radius: 15px; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-save { background: var(--accent); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>ARCADE PANEL</h2>
    <a href="index.php">📊 Dashboard</a>
    <a href="fetch_gd.php">📥 Ambil Game GD</a>
    <a href="manage_games.php" class="active">🎮 Kelola Game</a>
    <hr style="border:0; border-top:1px solid rgba(255,255,255,0.1); margin:15px 0;">
    <a href="../index.php" target="_blank" style="color:#2ecc71">🌐 Lihat Situs</a>
    <a href="logout.php" style="color:#ff4757">🚪 Logout</a>
</div>

<div class="content">
    <div class="card">
        <div class="toolbar">
            <h2 style="margin:0">List Game (<?= number_format($total_items) ?>)</h2>
            <form method="GET">
                <select name="category" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach($all_cats as $c): ?>
                        <option value="<?= $c ?>" <?= ($filter_cat == $c) ? 'selected' : '' ?>><?= strtoupper($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="sort" value="<?= $sort ?>">
                <input type="hidden" name="order" value="<?= $order ?>">
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Thumb</th>
                    <th><a href="?category=<?= $filter_cat ?>&sort=title&order=<?= $toggle_order ?>&page=<?= $page ?>">Judul <?= ($sort=='title')?($order=='asc'?'↑':'↓'):'' ?></a></th>
                    <th><a href="?category=<?= $filter_cat ?>&sort=category&order=<?= $toggle_order ?>&page=<?= $page ?>">Kategori</a></th>
                    <th><a href="?category=<?= $filter_cat ?>&sort=views&order=<?= $toggle_order ?>&page=<?= $page ?>">Views</a></th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $row): ?>
                <tr>
                    <td><img src="../<?= $row['image_url'] ?>?v=<?= time() ?>" width="40" height="40" style="object-fit:cover; border-radius:6px;"></td>
                    <td><strong><?= htmlspecialchars(html_entity_decode((string)($row['title'] ?? ''))) ?></strong></td>
                    <td><span style="background:#eee; padding:3px 8px; border-radius:5px; font-size:11px;"><?= strtoupper((string)($row['category'] ?? '')) ?></span></td>
                    <td><?= number_format((int)($row['views'] ?? 0)) ?></td>
                    <td>
                        <button class="btn-edit" onclick='openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>Edit</button>
                        <a href="?delete=<?= $row['id'] ?>&category=<?= $filter_cat ?>&page=<?= $page ?>" class="btn-delete" onclick="return confirm('Hapus game ini?')">❌</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1&category=<?= $filter_cat ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="page-link">«</a>
                <a href="?page=<?= $page - 1 ?>&category=<?= $filter_cat ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="page-link">‹</a>
            <?php endif; ?>

            <?php
            for ($i = ($page - $range); $i < (($page + $range) + 1); $i++) {
                if ($i > 0 && $i <= $total_pages) {
                    $active = ($i == $page) ? 'active' : '';
                    echo "<a href='?page=$i&category=$filter_cat&sort=$sort&order=$order' class='page-link $active'>$i</a>";
                }
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&category=<?= $filter_cat ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="page-link">›</a>
                <a href="?page=<?= $total_pages ?>&category=<?= $filter_cat ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="page-link">»</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0">Edit Game</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="game_id" id="edit_id">
            <input type="text" name="title" id="edit_title" required placeholder="Judul">
            <input type="text" name="category" id="edit_cat" required placeholder="Kategori">
            <label style="font-size:11px; color:#888;">Ganti Gambar:</label>
            <input type="file" name="thumb_upload" accept="image/*">
            <textarea name="description" id="edit_desc" rows="4" placeholder="Deskripsi"></textarea>
            <button type="submit" name="update_game" class="btn-save">SIMPAN PERUBAHAN</button>
            <button type="button" onclick="closeEdit()" style="background:none; border:none; width:100%; margin-top:10px; color:gray; cursor:pointer;">Batal</button>
        </form>
    </div>
</div>

<script>
function openEdit(game) {
    if(!game) return;
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('edit_id').value = game.id;
    document.getElementById('edit_title').value = game.title;
    document.getElementById('edit_cat').value = game.category;
    document.getElementById('edit_desc').value = game.description || "";
}
function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
window.onclick = function(e) { if(e.target == document.getElementById('editModal')) closeEdit(); }
</script>

</body>
</html>