<?php
$host = 'localhost';
$db   = 'hayekato_sns';
$user = 'hayekato_semua';
$pass = '@Rahas1alah';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}

if (!function_exists('safe_input')) {
    function safe_input($data) {
        return htmlspecialchars(strip_tags(trim((string)($data ?? ''))));
    }
}
?>