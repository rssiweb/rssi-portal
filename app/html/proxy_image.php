<?php
require_once __DIR__ . '/../bootstrap.php';

$fileId = $_GET['id'] ?? '';
$width  = (int)($_GET['w'] ?? 800);
$height = (int)($_GET['h'] ?? 600);

if (!$fileId) {
    http_response_code(400);
    exit('Missing file ID');
}

// Fetch from Google Drive
// $url = "https://drive.google.com/uc?export=download&id={$fileId}";
$url = "https://drive.google.com/thumbnail?id={$fileId}&sz=w800-h600";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$imageData = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $imageData) {

    // if (extension_loaded('gd')) {
    //     $source = imagecreatefromstring($imageData);
    //     if ($source) {
    //         $origW = imagesx($source);
    //         $origH = imagesy($source);

    //         $ratio = $origW / $origH;
    //         if ($width / $height > $ratio) {
    //             $width = (int)($height * $ratio);
    //         } else {
    //             $height = (int)($width / $ratio);
    //         }

    //         $thumb = imagecreatetruecolor($width, $height);
    //         imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $origW, $origH);

    //         header('Content-Type: image/jpeg');
    //         imagejpeg($thumb, null, 85);

    //         imagedestroy($source);
    //         imagedestroy($thumb);
    //         exit;
    //     }
    // }

    // If GD not available or resize failed
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . strlen($imageData));
    echo $imageData;
    exit;
}

// Fallback
$placeholder = __DIR__ . '/../assets/placeholder.jpg';
if (file_exists($placeholder)) {
    header('Content-Type: image/jpeg');
    readfile($placeholder);
    exit;
}

header('Content-Type: image/svg+xml');
echo '<svg width="800" height="600" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="#f0f0f0"/>
    <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" fill="#666">
        Image not available
    </text>
</svg>';
