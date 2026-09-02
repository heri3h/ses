<?php
require_once 'config/db.php';
require_once 'ads/ad-units.php';

// Menangkap slug dari rewrite rule .htaccess
$slug = isset($_GET['slug']) ? safe_input($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: /");
    exit;
}

try {
    // Ambil data game yang aktif
    $stmt = $pdo->prepare("SELECT * FROM games WHERE slug = ? AND status = 'publish' LIMIT 1");
    $stmt->execute([$slug]);
    $game = $stmt->fetch();

    if (!$game) {
        die("Sorry, game not found.");
    }

    // Update Views (Setiap kali halaman dibuka)
    $pdo->prepare("UPDATE games SET views = views + 1 WHERE id = ?")->execute([$game['id']]);

    // Ambil Game Terkait untuk Sidebar (10 game acak dari kategori yang sama)
    $stmtRelated = $pdo->prepare("SELECT title, slug, image_url FROM games WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 10");
    $stmtRelated->execute([$game['category'], $game['id']]);
    $relatedGames = $stmtRelated->fetchAll();

    // Statistik untuk Footer
    $totalGames = $pdo->query("SELECT COUNT(*) FROM games")->fetchColumn();
    $totalPlays = $pdo->query("SELECT SUM(views) FROM games")->fetchColumn() ?: 0;

} catch (PDOException $e) {
    die("Database Error.");
}

// Decode entities agar &amp; menjadi & dan &quot; menjadi "
$displayTitle = htmlspecialchars(html_entity_decode((string)($game['title'] ?? '')));
$displayDesc  = nl2br(htmlspecialchars(html_entity_decode((string)($game['description'] ?? ''))));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play <?php echo $displayTitle; ?> </title>
    
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/game-style.css?v=<?php echo time(); ?>">
    <?php //include 'ads/ads-header.php'; ?>
</head>
<body class="game-page-layout">

<?php
require_once 'header.php';
?>


<main class="game-container">
    <?php //echo get_ad_unit('gm-header'); ?>
    <div class="main-grid">
        
        <div class="player-column">
            <div id="video-viewport" class="viewport-box">
                <iframe id="game-iframe" src="<?php echo $game['iframe_url']; ?>" frameborder="0" allowfullscreen></iframe>
            </div>
            
            <div class="player-bar">
                <div class="game-meta">
                    <h1><?php echo $displayTitle; ?></h1>
                    <p>🎮 <?php echo strtoupper($game['category']); ?> • 👁 <?php echo number_format($game['views'] + 1); ?> Plays</p>
                </div>
                <div class="game-btns">
                    <button id="fullscreen-btn" class="btn-fs">⛶ FULLSCREEN</button>
                    <button onclick="window.location.reload();" class="btn-reload">🔄 RELOAD</button>
                </div>
            </div>

            <div class="description-card">
                <h3>About Game</h3>
                <p><?php echo $displayDesc; ?></p>
            </div>
        </div>

        <aside class="sidebar-column">
            <h3 class="side-title">Try More Games</h3>
            <div class="side-stack">
                <?php foreach ($relatedGames as $rg): ?>
                <a href="/game/<?php echo $rg['slug']; ?>" class="side-card">
                    <img src="/<?php echo $rg['image_url']; ?>" alt="Thumb">
                    <div class="side-info">
                        <h4><?php echo htmlspecialchars(html_entity_decode((string)($rg['title'] ?? ''))); ?></h4>
                        <span>Play Now!</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </aside>

    </div>
</main>

<?php
require_once 'footer.php';
?>

<script>
    const fsBtn = document.getElementById('fullscreen-btn');
    const stage = document.getElementById('video-viewport');

    fsBtn.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            if (stage.requestFullscreen) stage.requestFullscreen();
            else if (stage.mozRequestFullScreen) stage.mozRequestFullScreen();
            else if (stage.webkitRequestFullscreen) stage.webkitRequestFullscreen();
            fsBtn.textContent = "⛶ FULLSCREEN";
        } else {
            document.exitFullscreen();
            fsBtn.textContent = "⛶ FULLSCREEN";
        }
    });
</script>
<?php //echo get_ad_unit('gm-global'); ?>
</body>
</html>