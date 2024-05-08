<?php
require_once __DIR__ . '/../bootstrap.php';



function uploadeToCloudinary($file, $filename)
{
    getenv("CLOUDINARY_API_KEY");

    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $client = new Google_Client();
    $client->setAuthConfig($secretfile_path);
    $client->setScopes(['https://www.googleapis.com/auth/drive']);
    $client->setSubject("info@rssi.in");
    $client->authorize();
    $service = new Google_Service_Drive($client);

    $filemeta = new Google_Service_Drive_DriveFile();
    $filemeta->setName($filename . '.' . $ext);
    $filemeta->setParents(array($parent));

    $udfile = $service->files->create(
        $filemeta,
        array(
            'data' => file_get_contents($file["tmp_name"]),
            'mimeType' => $file["type"],
            'uploadType' => 'multipart'
        )
    );
    // echo json_encode($udfile);
    $fileId = $udfile->getId();

    $link = "https://drive.google.com/file/d/" . $fileId . "/view"; // EDIT FILE URL
    unlink($secretfile_path);
    return $link;
}
