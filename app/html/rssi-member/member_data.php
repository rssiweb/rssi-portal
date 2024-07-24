<?php
require_once __DIR__ . "/../../bootstrap.php";

$user_check = $_SESSION['aid'];
$view_users_query = "select * from rssimyaccount_members 
left join (SELECT applicantid, 1 as onleave FROM leavedb_leavedb WHERE CURRENT_DATE BETWEEN fromdate AND todate AND status='Approved') onleave ON rssimyaccount_members.associatenumber=onleave.applicantid
WHERE associatenumber='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
        $doj = $row[0];
        $associatenumber = $row[1];
        $fullname = $row[2];
        $email = $row[3];
        $basebranch = $row[4];
        $gender = $row[5];
        $dateofbirth = $row[6];
        $howyouwouldliketobeaddressed = $row[7];
        $currentaddress = $row[8];
        $permanentaddress = $row[9];
        $languagedetailsenglish = $row[10];
        $languagedetailshindi = $row[11];
        $workexperience = $row[12];
        $nationalidentifier = $row[13];
        $yourthoughtabouttheworkyouareengagedwith = $row[14];
        $applicationnumber = $row[15];
        $position = $row[16];
        $approvedby = $row[17];
        $associationstatus = $row[18];
        $effectivedate = $row[19];
        $remarks = $row[20];
        $phone = $row[21];
        $identifier = $row[22];
        $gm = $row[23];
        $photo = $row[24];
        $class = $row[25];
        $age = $row[26];
        $depb = $row[27];
        $attd = $row[28];
        $filterstatus = $row[29];
        $today = $row[30];
        $allocationdate = $row[31];
        $maxclass = $row[32];
        $classtaken = $row[33];
        $ctp = $row[34];
        $engagement = $row[35];
        $profile = $row[36];
        $filename = $row[37];
        $fname = $row[38];
        $yos = $row[39];
        $role = $row[40];
        $originaldoj = $row[41];
        $iddoc = $row[42];
        $scode = $row[43];
        $exitinterview = $row[44];
        $absconding = $row[45];
        $eduq = $row[46];
        $mjorsub = $row[47];
        $approveddate = $row[48];
        $password = $row[49];
        $password_updated_by = $row[50];
        $password_updated_on = $row[51];
        $default_pass_updated_by = $row[52];
        $default_pass_updated_on = $row[53];
        $job_type = $row[54];
        $salary = $row[55];
        $panno = $row[56];
        $bankname = $row[57];
        $accountnumber = $row[58];
        $ifsccode = $row[59];
        $grade = $row[60];
}
