<?php
require_once 'auth.php';
require_once '../config/db.php';
check_access();

$total_games = 0;
$total_views = 0;
$top_game = null;
$recent_games = [];
$error = null;

try {
    // Total Game
    $total_games = $pdo->query("SELECT COUNT(*) FROM games")->fetchColumn();
    $total_games = $total_games ? (int)$total_games : 0;

    // Total Views
    $res_views = $pdo->query("SELECT SUM(views) FROM games")->fetchColumn();
    $total_views = $res_views ? (int)$res_views : 0;

    // Game Terpopuler
    $stmt_top = $pdo->query("SELECT title FROM games ORDER BY views DESC LIMIT 1");
    $top_game = $stmt_top ? $stmt_top->fetch() : null;

    // 5 Game Terbaru
    $stmt_recent = $pdo->query("SELECT title, created_at FROM games ORDER BY created_at DESC LIMIT 5");
    $recent_games = $stmt_recent ? $stmt_recent->fetchAll() : [];
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; margin: 0; }
        .sidebar { width: 250px; background: #1f1235; color: white; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: #ccc; text-decoration: none; display: block; padding: 10px; border-radius: 5px; }
        .sidebar a.active { background: #4c3575; color: white; }
        .content { flex: 1; padding: 40px; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0; font-size: 14px; color: #888; }
        .stat-card p { font-size: 24px; font-weight: bold; margin: 10px 0 0; }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>ARCADE PANEL</h2>
    <a href="index.php" class="active">Dashboard</a>
    <a href="fetch_gd.php">Ambil Game GD</a>
    <a href="manage_games.php">Kelola Game</a>
    <a href="../index.php" target="_blank">Lihat Situs</a>
</div>
<div class="content">
    <h1>Dashboard Overview</h1>
    <div class="stats">
        <div class="stat-card"><h3>Total Game</h3><p><?php echo $total_games; ?></p></div>
        <div class="stat-card"><h3>Total Plays</h3><p><?php echo number_format($total_views); ?></p></div>
        <div class="stat-card"><h3>Top Game</h3><p style="font-size:16px"><?php echo $top_game ? $top_game['title'] : '-'; ?></p></div>
    </div>
    <div style="background:white; padding:20px; border-radius:10px;">
        <h3>Baru Saja Ditambahkan</h3>
        <?php foreach($recent_games as $rg): ?>
            <div style="padding:10px 0; border-bottom:1px solid #eee;">
                <?php echo $rg['title']; ?> <small style="color:#aaa; float:right"><?php echo $rg['created_at']; ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>