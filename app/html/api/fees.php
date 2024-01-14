<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/paytm-util.php");
include("../../util/email.php");
include("../../util/drive.php");
include("../../util/login_util.php");

date_default_timezone_set('Asia/Kolkata');

if (!isLoggedIn("aid")) {
    $response = array();
    $response['status'] = 401;
    $response['message'] = "Unauthorized";
    echo json_encode($response);
    exit();
}

$METHOD = $_SERVER['REQUEST_METHOD'];

if ($METHOD == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $rows = $data['rows'];

    // insert using feesRepository doctrine, use transaction 
    $entityManager->getConnection()->beginTransaction();
    try {
        $count = 0;
        foreach ($rows as $row) {
            $fees = new Fees();

            // $newId = "F" . $row['studentid'] . date("YmdHis");
            // $fees->setStudentid($newId);

            $fees->setDate(new DateTime($row['date']));
            $fees->setStudentid($row['studentid']);
            $fees->setFees($row['fees']);
            $fees->setMonth($row['month']);
            $fees->setCollectedby($row['collectedby']);
            $fees->setPtype($row['ptype']);
            $fees->setFeeyear($row['feeyear']);


            $entityManager->persist($fees);
            $entityManager->flush();
            $count++;
        }
        $entityManager->getConnection()->commit();
        $response = array();
        $response['status'] = 200;
        $response['message'] = "Successfully inserted " . $count . " fees";
        echo json_encode($response);
    } catch (Exception $e) {
        $entityManager->getConnection()->rollback();
        $response = array();
        $response['status'] = 500;
        $response['message'] = "Failed to insert fees";
        $response['error'] = $e->getMessage();
        echo json_encode($response);
        exit();
    }


}


?>