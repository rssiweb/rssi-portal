<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
header('Content-Type: application/json');
if (!isLoggedIn("aid")) { echo json_encode(['status'=>'error']); exit; }

$r = pg_query($con, "SELECT id, name, contact FROM vendors WHERE active ORDER BY name");
$out = [];
while ($row = pg_fetch_assoc($r)) $out[] = $row;
echo json_encode(['status'=>'success','vendors'=>$out]);