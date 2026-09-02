<?php
require_once 'auth.php';
require_once '../config/db.php';
check_access();

// 1. DAFTAR KATEGORI LENGKAP GameMonetize (Label => Slug API GameMonetize)
$cat_map = [
    'All'          => 'All',
    'Action'       => 'Action',
    'Adventure'    => 'Adventure',
    'Arcade'       => 'Arcade',
    'Baby'         => 'Baby',
    'Beauty'       => 'Beauty',
    'Bejeweled'    => 'Bejeweled',
    'Boys'         => 'Boys',
    'Card'         => 'Card',
    'Casual'       => 'Casual',
    'Clicker'      => 'Clicker',
    'Cooking'      => 'Cooking',
    'Dress Up'     => 'Dress Up',
    'Fighting'     => 'Fighting',
    'Football'     => 'Football',
    'Girls'        => 'Girls',
    'Hypercasual'  => 'Hypercasual',
    'Multiplayer'  => 'Multiplayer',
    'Puzzle'       => 'Puzzle',
    'Racing'       => 'Racing',
    'Shooting'     => 'Shooting',
    'Sports'       => 'Sports',
    'Stickman'     => 'Stickman',
    '2 Player'     => '2 Player',
    '3D'           => '3D',
    '.IO'          => '.IO'
];

// 2. LOGIKA KATEGORI & API
$selected_cat = isset($_GET['cat']) ? (string)$_GET['cat'] : 'All';
$api_cat = urlencode($selected_cat);

$feed_url = "https://rss.gamemonetize.com/rssfeed.php?format=json&category={$api_cat}";

// 3. AMBIL DATA JSON
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
    ]
];
$context = stream_context_create($opts);
$json_content = @file_get_contents($feed_url, false, $context);

if ($json_content === FALSE) {
    die("<div style='padding:40px; background:#fff; color:red; font-family:sans-serif;'>Gagal mengambil data dari API GameMonetize. Silakan cek koneksi internet atau link API.</div>");
}

$data = json_decode($json_content, true);
$all_games = is_array($data) ? $data : [];

// 4. LOGIKA PAGINATION (12 GAME PER HALAMAN AGAR RAPI DI GRID)
$per_page     = 12; 
$total_item   = count($all_games);
$total_pages  = $per_page > 0 ? (int)ceil($total_item / $per_page) : 1;
$current_page = isset($_GET['page']) ? max(1, min(max(1, $total_pages), (int)$_GET['page'])) : 1;

$offset       = ($current_page - 1) * $per_page;
$games_to_show = array_slice($all_games, $offset, $per_page);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fetch GameMonetize - Katalog <?php echo htmlspecialchars($selected_cat); ?></title>
    <style>
        :root { --primary: #1f1235; --accent: #2ecc71; --bg: #f0f2f5; --card: #ffffff; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg); margin: 0; display: flex; }
        
        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--primary); color: white; height: 100vh; position: sticky; top: 0; padding: 25px; box-sizing: border-box; }
        .sidebar h2 { font-size: 20px; color: #ffcc00; margin-bottom: 30px; text-align: center; font-weight: 900; letter-spacing: 1px; }
        .sidebar a { color: #ccc; text-decoration: none; display: block; padding: 12px; border-radius: 10px; margin-bottom: 8px; transition: 0.3s; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #4c3575; color: white; }
        .sidebar .logout { color: #ff4757; border: 1px solid #ff4757; margin-top: 30px; text-align: center; }

        /* MAIN CONTENT */
        .content { flex: 1; padding: 40px; box-sizing: border-box; }
        
        .toolbar { 
            background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; 
            display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        .toolbar h1 { font-size: 22px; margin: 0; color: var(--primary); }
        
        select#cat-selector { 
            padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; 
            font-size: 14px; font-weight: 600; cursor: pointer; outline: none;
            background: #f9f9f9;
        }

        /* GRID GAMES */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 25px; }
        .game-card { 
            background: var(--card); border-radius: 15px; padding: 15px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center;
            display: flex; flex-direction: column; transition: 0.3s;
        }
        .game-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .game-card img { width: 100%; border-radius: 12px; aspect-ratio: 1/1; object-fit: cover; background: #eee; }
        .game-card h4 { font-size: 14px; margin: 15px 0; height: 35px; overflow: hidden; color: #333; line-height: 1.4; }

        .btn-add { 
            background: var(--accent); color: white; border: none; padding: 12px; 
            border-radius: 10px; cursor: pointer; font-weight: bold; width: 100%;
            transition: 0.2s;
        }
        .btn-add:hover { background: #27ae60; }
        .btn-add:disabled { background: #bdc3c7; cursor: not-allowed; }

        /* PAGINATION */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 40px; flex-wrap: wrap; padding-bottom: 50px; }
        .page-link { 
            padding: 10px 15px; background: white; border: 1px solid #ddd; 
            text-decoration: none; color: var(--primary); border-radius: 8px; font-size: 13px; font-weight: bold;
        }
        .page-link.active { background: var(--primary); color: #ffcc00; border-color: var(--primary); }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>ARCADE PANEL</h2>
    <a href="index.php">📊 Dashboard</a>
    <a href="fetch_gd.php" class="active">📥 Ambil Game Monetize</a>
    <a href="manage_games.php">🎮 Kelola Game</a>
    <a href="../index.php" target="_blank">🌐 Lihat Situs</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="content">
    <div class="toolbar">
        <div>
            <h1>Katalog GameMonetize: <?php echo ($selected_cat == 'All') ? 'Semua Game' : htmlspecialchars($selected_cat); ?></h1>
            <span style="color: #888; font-size: 13px;">Ditemukan <?php echo number_format($total_item); ?> game di feed ini.</span>
        </div>
        
        <select id="cat-selector" onchange="location = '?cat=' + encodeURIComponent(this.value);">
            <?php foreach($cat_map as $label => $api_slug): ?>
                <option value="<?php echo htmlspecialchars($api_slug); ?>" <?php echo ($selected_cat == $api_slug) ? 'selected' : ''; ?>>
                    Kategori: <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid">
        <?php foreach ($games_to_show as $game): 
            $title = $game['title'] ?? ($game['Title'] ?? 'No Title');
            $url   = $game['url'] ?? ($game['Url'] ?? '');
            $thumb = $game['thumb'] ?? ($game['Asset'][0] ?? 'https://via.placeholder.com/150');
            $desc  = $game['description'] ?? ($game['Description'] ?? '');
            
            // Ambil slug dari URL GameMonetize untuk ID unik
            $slug_path = parse_url($url, PHP_URL_PATH);
            $slug = trim(basename(rtrim($slug_path, '/')));
            if (empty($slug) && !empty($game['id'])) {
                $slug = 'gm-' . $game['id'];
            }
            
            // Tentukan kategori yang akan disimpan ke database
            $db_cat = ($selected_cat == 'All') ? ($game['category'] ?? ($game['Category'][0] ?? 'Casual')) : $selected_cat;
        ?>
        <div class="game-card">
            <img src="<?php echo htmlspecialchars($thumb); ?>" alt="thumb">
            <h4><?php echo htmlspecialchars($title); ?></h4>
            
            <form class="ajax-add-form">
                <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                <input type="hidden" name="url" value="<?php echo htmlspecialchars($url); ?>">
                <input type="hidden" name="thumb" value="<?php echo htmlspecialchars($thumb); ?>">
                <input type="hidden" name="desc" value="<?php echo htmlspecialchars($desc); ?>">
                <input type="hidden" name="cat" value="<?php echo htmlspecialchars($db_cat); ?>">
                <button type="submit" class="btn-add">TAMBAHKAN</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <?php 
        // Pagination sederhana: batasi maksimal 50 halaman agar rapi
        for ($i = 1; $i <= min($total_pages, 50); $i++): 
        ?>
            <a href="?cat=<?php echo urlencode($selected_cat); ?>&page=<?php echo $i; ?>" class="page-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script>
document.querySelectorAll('.ajax-add-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('.btn-add');
        const formData = new FormData(this);
        
        btn.innerText = 'Menyimpan...';
        btn.disabled = true;

        fetch('save_process.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                btn.innerText = '✅ Tersimpan';
                btn.style.background = '#95a5a6';
            } else {
                alert('Gagal: ' + data.message);
                btn.innerText = 'TAMBAHKAN';
                btn.disabled = false;
            }
        }).catch(err => {
            alert('Error saat mengirim data.');
            btn.disabled = false;
        });
    });
});
</script>

</body>
</html>