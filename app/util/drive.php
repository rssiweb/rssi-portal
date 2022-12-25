<?php
require_once __DIR__ . '/../bootstrap.php';



function uploadeToDrive($file, $parent, $filename){
    // uploade to drive using credential file and return drive link
    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $cred = json_decode(getenv("GOOGLE_DRIVE_UPLOADER_CRED"));
    $client = new Google_Client();
    $client->setAuthConfig($cred);
    $client->setScopes(['https://www.googleapis.com/auth/drive']);
    $client->authorize();
    $service = new Google_Service_Drive($client);
    $filemeta = new Google_Service_Drive_DriveFile();
    $filemeta->setName($filename.'.'.$ext);
    $filemeta->setParents(array($parent));

    $udfile = $service->files->create($filemeta, array(
        'data' => file_get_contents($file["tmp_name"]),
        'mimeType' => $file["type"],
        'uploadType' => 'multipart'
    ));
    // echo json_encode($udfile);
    $fileId = $udfile->getId();
    $link = $fileId; // EDIT FILE URL
    return $link;
}