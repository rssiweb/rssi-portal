<?php
// proxy_image.php
require_once __DIR__ . '/../bootstrap.php';

$fileId = $_GET['id'] ?? '';
$width = $_GET['w'] ?? 800;
$height = $_GET['h'] ?? 600;

if (!$fileId) {
    http_response_code(400);
    echo 'Missing file ID';
    exit;
}

// Cache directory
$cacheDir = __DIR__ . '/../cache/images/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$cacheFile = $cacheDir . 'drive_' . md5($fileId . $width . $height) . '.jpg';

// Serve from cache if exists and fresh (1 day)
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($cacheFile));
    readfile($cacheFile);
    exit;
}

// Fetch from Google Drive
$url = "https://drive.google.com/uc?export=download&id={$fileId}";

// Use cURL for better handling
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $imageData) {
    // Resize image if needed (optional)
    if (extension_loaded('gd')) {
        $source = imagecreatefromstring($imageData);
        if ($source) {
            $origWidth = imagesx($source);
            $origHeight = imagesy($source);
            
            // Calculate new dimensions
            $ratio = $origWidth / $origHeight;
            if ($width / $height > $ratio) {
                $width = $height * $ratio;
            } else {
                $height = $width / $ratio;
            }
            
            $thumb = imagecreatetruecolor($width, $height);
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
            
            // Save to cache
            imagejpeg($thumb, $cacheFile, 85);
            
            // Output
            header('Content-Type: image/jpeg');
            imagejpeg($thumb);
            imagedestroy($source);
            imagedestroy($thumb);
            exit;
        }
    }
    
    // If GD not available or image processing failed, save original
    file_put_contents($cacheFile, $imageData);
    
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . strlen($imageData));
    echo $imageData;
    exit;
}

// Fallback to placeholder
$placeholder = __DIR__ . '/../assets/placeholder.jpg';
if (file_exists($placeholder)) {
    header('Content-Type: image/jpeg');
    readfile($placeholder);
} else {
    // Create simple placeholder
    header('Content-Type: image/svg+xml');
    echo '<svg width="800" height="600" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#f0f0f0"/>
        <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" fill="#666">Image not available</text>
    </svg>';
}
?>