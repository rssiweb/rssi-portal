<?php
require_once __DIR__ . '/../bootstrap.php';

function storeFile($file, $ownerId) {
    // Generate UUID for the file
    $fileId = uniqid();
    
    // Get the original filename
    $originalFilename = $file['name'];
    
    // Get file extension
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    
    // Create the full filename with UUID
    $newFilename = $fileId . '.' . $extension;
    
    // Define the storage path
    $storagePath = '/files/';
    
    // Create directory if it doesn't exist
    if (!file_exists($storagePath)) {
        mkdir($storagePath, 0777, true);
    }
    
    // Move the uploaded file to the storage location
    $destination = $storagePath . $newFilename;
    move_uploaded_file($file['tmp_name'], $destination);
    
    // Return the object with required attributes
    // TODO: insert this information in the database
    return [
        'fileId' => $fileId,
        'originalFilename' => $originalFilename,
        'path' => '/files/' . $newFilename,
        'ownerId' => $ownerId,
        'createdAt' => date('Y-m-d H:i:s'),
        'deletedAt' => null
    ];
}
