<?php  
        include("database.php");  
        $view_users_query="select * from rssimyprofile_student WHERE student_id='$user_check'";//select query for viewing users.  
        $run=pg_query($con,$view_users_query);//here run the sql query.  
  
        while($row=pg_fetch_array($run))//while look to fetch the result and store in a array $row.  
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
                $dateofbirth=$row[12];
                $postaladdress=$row[13];
                $nameofthesubjects=$row[14];
                $preferredbranch=$row[15];
                $nameoftheschool=$row[16];
                $nameoftheboard=$row[17];
                $stateofdomicile=$row[18];
                $schooladmissionrequired=$row[19];
                $selectdateofformsubmission=$row[20];
                $photourl=$row[21];
                $familymonthlyincome=$row[22];
                $totalnumberoffamilymembers=$row[23];
                $medium=$row[24];
                $fees=$row[25];
                $mydocument=$row[26];
                $profilestatus=$row[27];
                $lastupdatedon=$row[28];
                $colors=$row[29];
                $classurl=$row[30];
                $filterstatus=$row[31];
                $allocationdate=$row[32];
                $maxclass=$row[33];
                $attd=$row[34];
                $leaveapply=$row[35];
                $filename=$row[36];
                $lastlogin=$row[37];
                $__hevo_id=$row[38];
                $guardianaadhar=$row[39];
                $emailaddress=$row[40];
                $notice=$row[41];
                $status=$row[42];
                $effectivefrom=$row[43];
                $remarks=$row[44];
                $remarks1=$row[45];
                $__hevo__ingested_at=$row[46];
                $__hevo__marked_deleted=$row[47];
                $bookststus=$row[48];
                $cltaken=$row[49];
                $othtaken=$row[50];
                $nameofvendorfoundation=$row[51];
                $badge=$row[52];
                $sltaken=$row[53];
                $doa=$row[54]
                
 
?>     
<?php }?>    