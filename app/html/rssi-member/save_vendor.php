<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');
if (!isLoggedIn("aid")) { echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit; }

$id      = (int)($_POST['id'] ?? 0);
$name    = trim($_POST['name'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$active  = ($_POST['active'] ?? '1') === '1';

if ($name === '') { echo json_encode(['status'=>'error','message'=>'Name is required']); exit; }
if (strlen($name) > 150)  { echo json_encode(['status'=>'error','message'=>'Name too long (max 150)']); exit; }
if (strlen($contact) > 100) { echo json_encode(['status'=>'error','message'=>'Contact too long (max 100)']); exit; }

try {
    if ($id > 0) {
        $res = pg_query_params($con,
            "UPDATE vendors
                SET name = $1, contact = $2, active = $3
              WHERE id = $4",
            [$name, $contact !== '' ? $contact : null, $active ? 't' : 'f', $id]);
        if (!$res) throw new Exception(pg_last_error($con));
        if (pg_affected_rows($res) === 0) throw new Exception('Vendor not found');
    } else {
        $res = pg_query_params($con,
            "INSERT INTO vendors (name, contact, active)
             VALUES ($1, $2, $3) RETURNING id",
            [$name, $contact !== '' ? $contact : null, $active ? 't' : 'f']);
        if (!$res) throw new Exception(pg_last_error($con));
    }
    echo json_encode(['status'=>'success']);
} catch (Exception $e) {
    // Friendly message for the UNIQUE(name) constraint
    $msg = $e->getMessage();
    if (strpos($msg, 'vendors_name_key') !== false) {
        $msg = 'A vendor with this name already exists';
    }
    echo json_encode(['status'=>'error','message'=>$msg]);
}