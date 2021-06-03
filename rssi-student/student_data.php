<?php  
        include("database.php");  
        $view_users_query="select * from rssimyprofile_student WHERE student_id='$user_check'";//select query for viewing users.  
        $run=pg_query($con,$view_users_query);//here run the sql query.  
  
        while($row=pg_fetch_array($run))//while look to fetch the result and store in a array $row.  
        { 
                $category=$row[0];
                $student_id=$row[1];
                $studentname=$row[2];
                $gender=$row[3];
                $age=$row[4];
                $class=$row[5];
                $contact=$row[6];
                $guardiansname=$row[7];
                $relationwithstudent=$row[8];
                $studentaadhar=$row[9];
                $guardianaadhar=$row[10];
                $dateofbirth=$row[11];
                $postaladdress=$row[12];
                $nameofthesubjects=$row[13];
                $preferredbranch=$row[14];
                $stateofdomicile=$row[15];
                $emailaddress=$row[16];
                $schooladmissionrequired=$row[17];
                $status=$row[18];
                $remarks=$row[19];
                $photourl=$row[20];
                $familymonthlyincome=$row[21];
                $totalnumberoffamilymembers=$row[22];
                $profilestatus=$row[23];
                $lastupdatedon=$row[24];
                $colors=$row[25];
                $remarks1=$row[26];
                $filterstatus=$row[27];
                $filename=$row[28];
                $doa=$row[29];
                $__hevo_id=$row[30];
                $nameoftheschool=$row[31];
                $nameoftheboard=$row[32];
                $profile=$row[33];
                $roll_number=$row[34];
                $medium=$row[35];
                $fees=$row[36];
                $mydocument=$row[37];
                $classurl=$row[38];
                $allocationdate=$row[39];
                $maxclass=$row[40];
                $attd=$row[41];
                $leaveapply=$row[42];
                $lastlogin=$row[43];
                $__hevo__ingested_at=$row[44];
                $__hevo__marked_deleted=$row[45];
                $notice=$row[46];
                $cltaken=$row[47];
                $othtaken=$row[48];
                $bookststus=$row[49]                
                
 
?>     
<?php }?>    