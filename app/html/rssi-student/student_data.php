<?php
require_once __DIR__ . "/../../bootstrap.php";

$user_check = $_SESSION['sid'];
$view_users_query = "select * from rssimyprofile_student WHERE student_id='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
        $category = $row[0];
        $student_id = $row[1];
        $roll_number = $row[2];
        $studentname = $row[3];
        $gender = $row[4];
        $age = $row[5];
        $class = $row[6];
        $contact = $row[7];
        $guardiansname = $row[8];
        $relationwithstudent = $row[9];
        $studentaadhar = $row[10];
        $guardianaadhar = $row[11];
        $dateofbirth = $row[12];
        $postaladdress = $row[13];
        $nameofthesubjects = $row[14];
        $preferredbranch = $row[15];
        $nameoftheschool = $row[16];
        $nameoftheboard = $row[17];
        $stateofdomicile = $row[18];
        $emailaddress = $row[19];
        $schooladmissionrequired = $row[20];
        $status = $row[21];
        $remarks = $row[22];
        $nameofvendorfoundation = $row[23];
        $photourl = $row[24];
        $familymonthlyincome = $row[25];
        $totalnumberoffamilymembers = $row[26];
        $medium = $row[27];
        $mydocument = $row[28];
        $extracolumn = $row[29];
        $colors = $row[30];
        $classurl = $row[31];
        $badge = $row[32];
        $filterstatus = $row[33];
        $allocationdate = $row[34];
        $maxclass = $row[35];
        $attd = $row[36];
        $cltaken = $row[37];
        $sltaken = $row[38];
        $othtaken = $row[39];
        $doa = $row[40];
        $feesflag = $row[41];
        $module = $row[42];
        $scode = $row[43];
        $exitinterview = $row[44];
        $sipf = $row[45];
        $password_updated_by = $row[47];
        $password_updated_on = $row[48];
        $upload_aadhar_card = $row[49];
        $special_service = $row[50];
        $feecycle = $row[51];
        $default_pass_updated_by= $row[52];
        $default_pass_updated_on= $row[53];
        $effectivefrom= $row[54];} ?>
