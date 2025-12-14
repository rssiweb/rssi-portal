<?php
// image_functions.php
function processImageUrl($imageUrl)
{
    if (empty($imageUrl)) {
        return $imageUrl;
    }
    
    $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
    if (preg_match($pattern, $imageUrl, $matches)) {
        $photoID = $matches[1];
        
        // Determine environment
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        if (strpos($host, 'localhost') !== false) {
            // Local development
            $proxyDomain = "http://localhost:8082";
        } else {
            // Production
            $proxyDomain = "https://login.rssi.in";
        }
        
        return "{$proxyDomain}/proxy_image.php?id={$photoID}&w=800&h=600";
    }
    return $imageUrl;
}
?>