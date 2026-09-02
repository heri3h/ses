<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ganti di sini untuk Username dan Password Anda
$admin_user = "admin";
$admin_pass = "Agen123";

// Fungsi untuk cek status login
function is_logged_in() {
    return isset($_SESSION['admin_status']) && $_SESSION['admin_status'] === 'logged_in';
}

// Proteksi halaman: panggil ini di setiap file admin (seperti fetch_gd.php)
function check_access() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
?>