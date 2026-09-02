<?php
// Pengaturan Database
$host     = "localhost";
$username = "hayeka_semua";      // Ganti sesuai username database Anda
$password = "@Rahas1alah";          // Ganti sesuai password database Anda
$db_name = "mbummm_ses"; // Nama database yang kita buat tadi

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    // Di PHP 7, sangat penting menyalakan mode error secara manual
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Matikan emulasi prepare agar lebih aman dan kompatibel
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

if (!function_exists('safe_input')) {
    function safe_input($data) {
        return htmlspecialchars(strip_tags(trim((string)($data ?? ''))));
    }
}
?>