<?php
// util/image_utils.php

/**
 * Convert proxy_image.php URLs to Google Drive direct URLs
 * 
 * @param string $proxyUrl The proxy URL (e.g., http://localhost:8082/proxy_image.php?id=1HORgM6wyj14G6NtPiBavk7LtY7Cf2m2W&w=800&h=600)
 * @return string Google Drive direct URL (e.g., https://drive.google.com/file/d/1HORgM6wyj14G6NtPiBavk7LtY7Cf2m2W/view)
 */
function convertProxyToDriveUrl($proxyUrl)
{
    if (empty($proxyUrl)) {
        return $proxyUrl;
    }

    // Check if it's a proxy URL
    if (strpos($proxyUrl, 'proxy_image.php') === false) {
        return $proxyUrl; // Not a proxy URL, return as-is
    }

    // Extract the file ID from the proxy URL
    $urlParts = parse_url($proxyUrl);
    if (!isset($urlParts['query'])) {
        return $proxyUrl;
    }

    parse_str($urlParts['query'], $queryParams);

    if (!isset($queryParams['id']) || empty($queryParams['id'])) {
        return $proxyUrl;
    }

    $fileId = $queryParams['id'];

    // Convert to Google Drive direct URL
    return "https://drive.google.com/file/d/{$fileId}/view";
}

/**
 * Process content to convert all proxy image URLs to Drive URLs
 * 
 * @param string $content HTML content with potential proxy image URLs
 * @return string Processed content with Drive URLs
 */
function processProxyImagesInContent($content)
{
    if (empty($content)) {
        return $content;
    }

    // Pattern to match img tags with proxy URLs
    $pattern = '/<img[^>]+src="([^"]*proxy_image\.php[^"]*)"[^>]*>/i';

    return preg_replace_callback($pattern, function ($matches) {
        $fullImgTag = $matches[0];
        $proxyUrl = $matches[1];

        // Convert proxy URL to Drive URL
        $driveUrl = convertProxyToDriveUrl($proxyUrl);

        // Replace the src attribute with Drive URL
        $newImgTag = str_replace($proxyUrl, $driveUrl, $fullImgTag);

        return $newImgTag;
    }, $content);
}
