<?php
require_once 'config/db.php';

// Mendapatkan parameter dari request AJAX
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$sort   = isset($_GET['sort']) ? (string)$_GET['sort'] : 'new';

// Mapping filter sortir agar aman
$orderMap = [
    'popular' => 'views DESC',
    'random'  => 'RAND()',
    'new'     => 'created_at DESC'
];
$orderBy = isset($orderMap[$sort]) ? $orderMap[$sort] : 'created_at DESC';

try {
    /**
     * Menggunakan Prepared Statement PDO
     * Di PHP 7, LIMIT dan OFFSET harus di-bind sebagai Integer (PARAM_INT)
     * agar tidak error saat dieksekusi oleh driver MySQL.
     */
    $query = "SELECT title, slug, image_url FROM games 
              WHERE status = 'publish' 
              ORDER BY $orderBy 
              LIMIT :limit OFFSET :offset";
              
    $stmt = $pdo->prepare($query);
    
    // Bind nilai dengan tipe data integer secara eksplisit
    $stmt->bindValue(':limit', 21, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    // Ambil semua hasil sebagai Array Associative
    $games = $stmt->fetchAll();

    // Set header sebagai JSON
    header('Content-Type: application/json');
    
    // Kirim data kembali ke JavaScript
    echo json_encode($games);

} catch (PDOException $e) {
    // Log error jika terjadi kegagalan database
    error_log("Load More Error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Gagal memuat data']);
}