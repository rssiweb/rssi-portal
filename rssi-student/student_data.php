<?php
include("database.php");
$view_users_query = "select * from rssimyprofile_student WHERE student_id='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
        $category=$row[0];
        $student_id=$row[1];
        $roll_number=$row[2];
        $studentname=$row[3];
        $gender=$row[4];
        $age=$row[5];
        $class=$row[6];
        $profile=$row[7];
        $contact=$row[8];
        $guardiansname=$row[9];
        $relationwithstudent=$row[10];
        $studentaadhar=$row[11];
        $guardianaadhar=$row[12];
        $dateofbirth=$row[13];
        $postaladdress=$row[14];
        $nameofthesubjects=$row[15];
        $preferredbranch=$row[16];
        $nameoftheschool=$row[17];
        $nameoftheboard=$row[18];
        $stateofdomicile=$row[19];
        $emailaddress=$row[20];
        $schooladmissionrequired=$row[21];
        $status=$row[22];
        $remarks=$row[23];
        $nameofvendorfoundation=$row[24];
        $photourl=$row[25];
        $familymonthlyincome=$row[26];
        $totalnumberoffamilymembers=$row[27];
        $medium=$row[28];
        $fees=$row[29];
        $bookststus=$row[30];
        $mydocument=$row[31];
        $profilestatus=$row[32];
        $lastupdatedon=$row[33];
        $colors=$row[34];
        $classurl=$row[35];
        $remarks1=$row[36];
        $notice=$row[37];
        $badge=$row[38];
        $filterstatus=$row[39];
        $allocationdate=$row[40];
        $maxclass=$row[41];
        $attd=$row[42];
        $leaveapply=$row[43];
        $cltaken=$row[44];
        $sltaken=$row[45];
        $othtaken=$row[46];
        $filename=$row[47];
        $lastlogin=$row[48];
        $doa=$row[49];
        $feesflag=$row[50];

?>     
<?php } ?>    