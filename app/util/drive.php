<?php
require_once __DIR__ . '/../bootstrap.php';



function uploadeToDrive($file, $parent, $filename)
{
    // uploade to drive using credential file and return drive link
    $secretfile_path = "/var/tmp/drive-storage-cred";
    $secretfile = fopen($secretfile_path, "w+") or die("Unable to open secret file!");
    fwrite($secretfile, getenv("STORAGE_CRED"));
    fclose($secretfile);

    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $client = new Google_Client();
    $client->setAuthConfig($secretfile_path);
    $client->setScopes(['https://www.googleapis.com/auth/drive']);
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
    $link = $fileId; // EDIT FILE URL
    unlink($secretfile_path);
    return $link;
}