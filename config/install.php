<?php
/**
 * ARCADE FUN - Auto Installer Script
 * Tahun: 2026
 */

// 1. KONFIGURASI DATABASE (Sesuaikan di sini)
$db_config = [
    'host' => 'localhost',
    'name' => 'hayekato_sns',
    'user' => 'hayekato_semua',
    'pass' => '@Rahas1alah'
];

echo "<h2>ArcadeFun Auto-Installer</h2>";
echo "<hr>";

try {
    // Koneksi Awal
    $pdo = new PDO("mysql:host=".$db_config['host'], $db_config['user'], $db_config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Buat Database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".$db_config['name']."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".$db_config['name']."`");
    echo "✅ Database <b>".$db_config['name']."</b> siap.<br>";

    // 3. Buat Tabel Games
    $sql_games = "CREATE TABLE IF NOT EXISTS `games` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `iframe_url` TEXT NOT NULL,
        `image_url` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `category` VARCHAR(50),
        `views` INT DEFAULT 0,
        `status` ENUM('publish', 'draft') DEFAULT 'publish',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_games);
    echo "✅ Tabel <b>games</b> berhasil dibuat.<br>";

    // 4. Buat Tabel Users (Untuk Login Admin)
    $sql_users = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `last_login` DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_users);
    echo "✅ Tabel <b>users</b> berhasil dibuat.<br>";

    // 5. Insert Akun Admin Default (User: admin | Pass: admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $user = 'admin';
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute([$user, $pass]);
        echo "⚠️ <b>Akun Admin Dibuat:</b> User: <i>admin</i> | Pass: <i>admin123</i> (Segera ganti!) <br>";
    }

    // 6. Membuat Struktur Folder
    $folders = ['assets/css', 'assets/thumbs', 'config'];
    foreach ($folders as $f) {
        if (!file_exists($f)) {
            mkdir($f, 0755, true);
            echo "✅ Folder <b>$f</b> dibuat.<br>";
        }
    }

    // 7. Otomatis Membuat file config/db.php
    $config_content = "<?php
\$host = '{$db_config['host']}';
\$db   = '{$db_config['name']}';
\$user = '{$db_config['user']}';
\$pass = '{$db_config['pass']}';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$db;charset=utf8mb4\", \$user, \$pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException \$e) {
    die(\"Koneksi Gagal: \" . \$e->getMessage());
}

if (!function_exists('safe_input')) {
    function safe_input(\$data) {
        return htmlspecialchars(strip_tags(trim((string)(\$data ?? ''))));
    }
}
?>";
    
    file_put_contents('config/db.php', $config_content);
    echo "✅ File <b>config/db.php</b> berhasil dibuat secara otomatis.<br>";

    echo "<hr>";
    echo "<h3 style='color:green;'>INSTALASI SELESAI!</h3>";
    echo "<p>Silakan <b>HAPUS</b> file <code>install.php</code> sekarang demi keamanan.</p>";
    echo "<a href='index.php'>Buka Halaman Utama</a> | <a href='dash/index.php'>Masuk Admin Panel</a>";

} catch (PDOException $e) {
    die("<h3 style='color:red;'>Gagal Instalasi:</h3> " . $e->getMessage());
}
?>