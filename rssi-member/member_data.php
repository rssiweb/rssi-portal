<?php  
        include("database.php");  
        $view_users_query="select * from memberdata WHERE AssociateNumber='$user_check'";//select query for viewing users.  
        $run=pg_query($con,$view_users_query);//here run the sql query.  
  
        while($row=pg_fetch_array($run))//while look to fetch the result and store in a array $row.  
        { 
                $DOJ=$row[0];
                $AssociateNumber=$row[1];
                $Fullname=$row[2];
                $Email=$row[3];
                $BaseBranch=$row[4];
                $Gender=$row[5];
                $DateofBirth=$row[6];
                $Howyouwouldliketobeaddressed=$row[7];
                $CurrentAddress=$row[8];
                $PermanentAddress=$row[9];
                $LanguageDetailsEnglish=$row[10];
                $LanguageDetailsHindi=$row[11];
                $WorkExperience=$row[12];
                $NationalIdentifier=$row[13];
                $Yourthoughtabouttheworkyouareengagedwith=$row[14];
                $ApplicationNumber=$row[15];
                $Position=$row[16];
                $ApprovedBy=$row[17];
                $AssociationStatus=$row[18];
                $EffectiveDate=$row[19];
                $Remarks=$row[20];
                $Phone=$row[21];
                $Identifier=$row[22];
                $Astatus=$row[23];
                $Badge=$row[24];
                $colors=$row[25];
                $GM=$row[26];
                $Lastupdatedon=$row[27];
                $Photo=$row[28];
                $mydoc=$row[29];
                $Class=$row[30];
                $Notification=$row[31];
                $Age=$row[32];
                $Depb=$row[33];
                $Attd=$row[34];
                $Filterstatus=$row[35];
                $today=$row[36];
                $AllocationDate=$row[37];
                $Maxclass=$row[38];
                $Classtaken=$row[39];
                $Leave=$row[40];
                $CTP=$row[41];
                $Feedback=$row[42];
                $EvaluationPath=$row[43];
                $Leaveapply=$row[44];
                $CL=$row[45];
                $SL=$row[46];
                $EL=$row[47];
                $Engagement=$row[48];
                $CLTaken=$row[49];
                $SLTaken=$row[50];
                $ELTaken=$row[51];
                $OTHTaken=$row[52];
                $CLBal=$row[53];
                $SLBal=$row[54];
                $ELBal=$row[55];
                $Lastlogin=$row[56];
                $Quicklink=$row[57]
                
 
?>     
<?php }?>    