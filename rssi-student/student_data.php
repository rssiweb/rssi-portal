<?php
include("database.php");
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
        $profile = $row[7];
        $contact = $row[8];
        $guardiansname = $row[9];
        $relationwithstudent = $row[10];
        $studentaadhar = $row[11];
        $dateofbirth = $row[12];
        $postaladdress = $row[13];
        $nameofthesubjects = $row[14];
        $preferredbranch = $row[15];
        $nameoftheschool = $row[16];
        $nameoftheboard = $row[17];
        $stateofdomicile = $row[18];
        $schooladmissionrequired = $row[19];
        $photourl = $row[20];
        $familymonthlyincome = $row[21];
        $totalnumberoffamilymembers = $row[22];
        $medium = $row[23];
        $fees = $row[24];
        $mydocument = $row[25];
        $profilestatus = $row[26];
        $lastupdatedon = $row[27];
        $colors = $row[28];
        $classurl = $row[29];
        $filterstatus = $row[30];
        $allocationdate = $row[31];
        $maxclass = $row[32];
        $attd = $row[33];
        $leaveapply = $row[34];
        $filename = $row[35];
        $lastlogin = $row[36];
        $doa = $row[37];
        $__hevo_id = $row[38];
        $guardianaadhar = $row[39];
        $emailaddress = $row[40];
        $notice = $row[41];
        $sltaken = $row[42];
        $__hevo__ingested_at = $row[43];
        $cltaken = $row[44];
        $othtaken = $row[45];
        $feesflag = $row[46];
        $__hevo__marked_deleted = $row[47];
        $remarks = $row[48];
        $bookststus = $row[49];
        $status = $row[50];
        $remarks1 = $row[51];
        $badge = $row[52];



?>     
<?php } ?>    