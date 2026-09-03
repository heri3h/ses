<?php
require_once 'config/db.php';
require_once 'ads/ad-units.php';

// Menangkap slug atau id dari parameter URL
$slug = isset($_GET['slug']) ? safe_input($_GET['slug']) : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if (!empty($slug)) {
        $stmt = $pdo->prepare("SELECT * FROM games WHERE slug = ? AND status = 'publish' LIMIT 1");
        $stmt->execute([$slug]);
        $game = $stmt->fetch();
    } elseif ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? AND status = 'publish' LIMIT 1");
        $stmt->execute([$id]);
        $game = $stmt->fetch();
    } else {
        // Ambil game acak jika tidak ada parameter
        $stmt = $pdo->query("SELECT * FROM games WHERE status = 'publish' ORDER BY RAND() LIMIT 1");
        $game = $stmt->fetch();
    }

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

// Decode entities
$displayTitle = htmlspecialchars(html_entity_decode((string)($game['title'] ?? '')));
$displayDesc  = nl2br(htmlspecialchars(html_entity_decode((string)($game['description'] ?? ''))));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play <?php echo $displayTitle; ?></title>
    
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/game-style.css?v=<?php echo time(); ?>">
    <?php require_once 'ads/ads-header.php'; ?>
    <?php require_once 'meta-header.php'; ?>

    <style>
        /* Styles khusus untuk indikator loading dan tombol play 5 detik */
        .play-btn.loading-state {
            background: linear-gradient(135deg, #4a4a5e 0%, #2b2b36 100%) !important;
            color: #d0d0e0 !important;
            cursor: not-allowed !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4) !important;
            animation: none !important;
            opacity: 0.85;
        }

        .play-btn.ready-state {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%) !important;
            color: #ffffff !important;
            cursor: pointer !important;
            animation: playPulse 2s infinite !important;
        }

        .countdown-timer-badge {
            display: inline-block;
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 14px;
            margin-left: 6px;
        }

        .spinner-icon {
            display: inline-block;
            animation: spin 1s linear infinite;
            margin-right: 6px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="game-page-layout">

<?php require_once 'header.php'; ?>

<?php echo get_ad_unit('gm-header'); ?>
<main class="game-container">
    <div class="main-grid">
        <div class="player-column">
            <?php 
                $rawThumb = $game['image_url'] ?? '';
                $thumbUrl = (strpos($rawThumb, 'http') === 0) ? $rawThumb : '/' . ltrim($rawThumb, '/');
            ?>
            <div id="video-viewport" class="viewport-box">
                <!-- Screen Cover Play Button (TANPA IFRAME SAMA SEKALI SAAT TAMPILAN AWAL) -->
                <div id="play-overlay" class="play-overlay">
                    <img src="<?php echo htmlspecialchars($thumbUrl); ?>" alt="" class="overlay-bg-img">
                    <div class="overlay-backdrop"></div>
                    <div class="overlay-content">
                        <div class="overlay-thumb-wrapper">
                            <img src="<?php echo htmlspecialchars($thumbUrl); ?>" alt="<?php echo $displayTitle; ?>" class="overlay-thumb">
                        </div>
                        <h2 class="overlay-title"><?php echo $displayTitle; ?></h2>

                        <!-- Tombol Play (Disabled 5 detik dengan caption Wait...) -->
                        <button id="play-game-btn" class="play-btn loading-state" type="button" disabled>
                            <span class="spinner-icon">⏳</span> Wait... <span id="countdown-num" class="countdown-timer-badge">5s</span>
                        </button>
                    </div>
                </div>

                <!-- Container Iframe Game (Baru dipanggil setelah loading 5 detik & tombol play diklik) -->
                <div id="iframe-container" class="iframe-container" style="display: none;"></div>
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
                <a href="/play.php?slug=<?php echo $rg['slug']; ?>" class="side-card">
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

<?php require_once 'footer.php'; ?>

<script>
    let timeLeft = 5;
    let isReady = false;

    const playBtn = document.getElementById('play-game-btn');
    const countdownNum = document.getElementById('countdown-num');
    const playOverlay = document.getElementById('play-overlay');
    const iframeContainer = document.getElementById('iframe-container');
    const gameUrl = <?php echo json_encode($game['iframe_url']); ?>;

    // Timer Countdown 5 Detik
    const timerInterval = setInterval(() => {
        timeLeft--;
        if (countdownNum) {
            countdownNum.textContent = timeLeft + 's';
        }

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            isReady = true;
            if (playBtn) {
                playBtn.disabled = false;
                playBtn.classList.remove('loading-state');
                playBtn.classList.add('ready-state');
                playBtn.innerHTML = '<span class="play-icon">▶</span> PLAY NOW';
            }
        }
    }, 1000);

    // Fungsi memanggil iframe HANYA setelah loading 5 detik & tombol play diklik
    function loadAndStartGame(e) {
        if (e) e.stopPropagation();
        
        // Cegah klik jika belum selesai loading 5 detik
        if (!isReady) {
            return;
        }

        if (playOverlay && iframeContainer) {
            playOverlay.style.display = 'none';
            iframeContainer.style.display = 'block';
            // Memanggil iframe game secara dinamis
            iframeContainer.innerHTML = `<iframe id="game-iframe" src="${gameUrl}" frameborder="0" allowfullscreen allow="autoplay; keyboard"></iframe>`;
            const iframe = document.getElementById('game-iframe');
            if (iframe) iframe.focus();
        }
    }

    if (playBtn) {
        playBtn.addEventListener('click', loadAndStartGame);
    }
    if (playOverlay) {
        playOverlay.addEventListener('click', loadAndStartGame);
    }

    // Fullscreen support
    const fsBtn = document.getElementById('fullscreen-btn');
    const stage = document.getElementById('video-viewport');

    if (fsBtn && stage) {
        fsBtn.addEventListener('click', () => {
            if (playOverlay && playOverlay.style.display !== 'none') {
                loadAndStartGame();
            }
            if (!document.fullscreenElement) {
                if (stage.requestFullscreen) stage.requestFullscreen();
                else if (stage.mozRequestFullScreen) stage.mozRequestFullScreen();
                else if (stage.webkitRequestFullscreen) stage.webkitRequestFullscreen();
                fsBtn.textContent = "⛶ FULLSCREEN";
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                fsBtn.textContent = "⛶ FULLSCREEN";
            }
        });
    }
</script>
<?php echo get_ad_unit('gm-global'); ?>
</body>
</html>
