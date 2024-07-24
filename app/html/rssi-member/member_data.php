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
        $gm = $row[26];
        $photo = $row[28];
        $class = $row[30];
        $age = $row[32];
        $depb = $row[33];
        $attd = $row[34];
        $filterstatus = $row[35];
        $today = $row[36];
        $allocationdate = $row[37];
        $maxclass = $row[38];
        $classtaken = $row[39];
        $ctp = $row[41];
        $engagement = $row[48];
        $profile = $row[57];
        $filename = $row[58];
        $fname = $row[59];
        $yos = $row[61];
        $role = $row[62];
        $originaldoj = $row[63];
        $iddoc = $row[64];
        $scode = $row[66];
        $exitinterview = $row[67];
        $absconding = $row[68];
        $eduq = $row[72];
        $mjorsub = $row[73];
        $approveddate = $row[78];
        $password = $row[79];
        $password_updated_by = $row[80];
        $password_updated_on = $row[81];
        $default_pass_updated_by = $row[82];
        $default_pass_updated_on = $row[83];
        $job_type = $row[85];
        $salary = $row[87];
        $panno = $row[88];        
}
