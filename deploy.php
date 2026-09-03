<?php
// Password / Secret Token rahasia Anda
$secret_token = 'TokenRahasiaSaya123!';

// Ambil header signature dari GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Ambil isi payload
$payload = file_get_contents('php://input');

// Validasi Keamanan
if ($signature) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret_token);
    if (!hash_equals($hash, $signature)) {
        http_response_code(403);
        die('Access denied: Invalid signature');
    }
} else {
    http_response_code(403);
    die('Access denied: No signature');
}

// Eksekusi git pull jika validasi berhasil
$output = shell_exec('cd /home/mbummm/web/ses.skuy.me/public_html && git pull 2>&1');
echo "<pre>$output</pre>";
