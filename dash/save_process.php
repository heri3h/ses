<?php
require_once 'auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = safe_input($_POST['title'] ?? '');
    $slug  = safe_input($_POST['slug'] ?? '');
    $url   = $_POST['url'] ?? ''; 
    $thumb_url = $_POST['thumb'] ?? '';
    $desc  = safe_input($_POST['desc'] ?? '');
    $cat   = safe_input($_POST['cat'] ?? '');

    // 1. Cek Duplikasi dengan PDO
    $stmt = $pdo->prepare("SELECT id FROM games WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Game sudah ada!']);
        exit;
    }

    // 2. Proses Simpan / Kompres Gambar Thumbnail (> 20KB dikompres)
    $target_dir = "../assets/thumbs/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $filename = $slug . ".jpg";
    $target_file = $target_dir . $filename;

    $final_thumb = $thumb_url;
    if (!empty($thumb_url)) {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $img_data = @file_get_contents($thumb_url, false, $context);
        if ($img_data !== false) {
            $file_size = strlen($img_data);
            $max_size  = 20 * 1024; // 20 KB (20480 bytes)

            if ($file_size > $max_size) {
                // Kompres gambar jika ukuran > 20KB
                $src = @imagecreatefromstring($img_data);
                if ($src !== false) {
                    $quality = 80;
                    ob_start();
                    imagejpeg($src, null, $quality);
                    $compressed_data = ob_get_clean();

                    while (strlen($compressed_data) > $max_size && $quality > 10) {
                        $quality -= 10;
                        ob_start();
                        imagejpeg($src, null, $quality);
                        $compressed_data = ob_get_clean();
                    }

                    // Jika dengan quality 10 masih > 20KB, lakukan resize 80%
                    if (strlen($compressed_data) > $max_size) {
                        $orig_w = imagesx($src);
                        $orig_h = imagesy($src);
                        $scale  = 0.8;
                        $new_w  = (int)($orig_w * $scale);
                        $new_h  = (int)($orig_h * $scale);
                        $dst    = imagecreatetruecolor($new_w, $new_h);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

                        ob_start();
                        imagejpeg($dst, null, 70);
                        $compressed_data = ob_get_clean();
                        imagedestroy($dst);
                    }

                    file_put_contents($target_file, $compressed_data);
                    imagedestroy($src);
                    $final_thumb = "assets/thumbs/" . $filename;
                } else {
                    file_put_contents($target_file, $img_data);
                    $final_thumb = "assets/thumbs/" . $filename;
                }
            } else {
                // Ukuran <= 20KB: simpan langsung tanpa kompresi
                file_put_contents($target_file, $img_data);
                $final_thumb = "assets/thumbs/" . $filename;
            }
        }
    }

    // 3. Insert dengan PDO
    $sql = "INSERT INTO games (title, slug, iframe_url, image_url, description, category) VALUES (?, ?, ?, ?, ?, ?)";
    try {
        $pdo->prepare($sql)->execute([$title, $slug, $url, $final_thumb, $desc, $cat]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}