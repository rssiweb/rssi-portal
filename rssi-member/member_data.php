<?php  
        include("database.php");  
        $view_users_query="select * from rssimyaccount_members WHERE associatenumber='$user_check'";//select query for viewing users.  
        $run=pg_query($con,$view_users_query);//here run the sql query.  
  
        while($row=pg_fetch_array($run))//while look to fetch the result and store in a array $row.  
        { 
                $doj=$row[0];
                $associatenumber=$row[1];
                $fullname=$row[2];
                $email=$row[3];
                $basebranch=$row[4];
                $gender=$row[5];
                $dateofbirth=$row[6];
                $howyouwouldliketobeaddressed=$row[7];
                $currentaddress=$row[8];
                $permanentaddress=$row[9];
                $languagedetailsenglish=$row[10];
                $languagedetailshindi=$row[11];
                $workexperience=$row[12];
                $nationalidentifier=$row[13];
                $yourthoughtabouttheworkyouareengagedwith=$row[14];
                $applicationnumber=$row[15];
                $position=$row[16];
                $approvedby=$row[17];
                $phone=$row[18];
                $identifier=$row[19];
                $astatus=$row[20];
                $colors=$row[21];
                $gm=$row[22];
                $lastupdatedon=$row[23];
                $photo=$row[24];
                $mydoc=$row[25];
                $class=$row[26];
                $notification=$row[27];
                $age=$row[28];
                $depb=$row[29];
                $attd=$row[30];
                $filterstatus=$row[31];
                $today=$row[32];
                $allocationdate=$row[33];
                $maxclass=$row[34];
                $classtaken=$row[35];
                $leave=$row[36];
                $ctp=$row[37];
                $evaluationpath=$row[38];
                $leaveapply=$row[39];
                $cl=$row[40];
                $sl=$row[41];
                $engagement=$row[42];
                $othtaken=$row[43];
                $clbal=$row[44];
                $slbal=$row[45];
                $elbal=$row[46];
                $profile=$row[47];
                $filename=$row[48];
                $fname=$row[49];
                $quicklink=$row[50];
                $__hevo_id=$row[51];
                $associationstatus=$row[52];
                $effectivedate=$row[53];
                $remarks=$row[54];
                $badge=$row[55];
                $sltaken=$row[56];
                $cltaken=$row[57];
                $feedback=$row[58];
                $__hevo__ingested_at=$row[59];
                $__hevo__marked_deleted=$row[60];
                $yos=$row[61];
                $role=$row[62];
                $originaldoj=$row[63];
                $iddoc=$row[64];
                $vaccination=$row[65];
                $officialdoc=$row[66];
                $scode=$row[67];
                $exitinterview=$row[68];
                $questionflag=$row[69];              
                
?>     
<?php }?>    