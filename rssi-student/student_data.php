<?php  
        include("database.php");  
        $view_users_query="select * from studentdata WHERE Student_ID='$user_check'";//select query for viewing users.  
        $run=pg_query($con,$view_users_query);//here run the sql query.  
  
        while($row=pg_fetch_array($run))//while look to fetch the result and store in a array $row.  
        { 
$Category=$row[0]; 
$Student_ID=$row[1];
$Roll_Number=$row[2];
$StudentName=$row[3];
$Gender=$row[4];
$Age=$row[5];
$Class=$row[6];
$Profile=$row[7];
$Contact=$row[8];
$GuardiansName=$row[9];
$RelationwithStudent=$row[10];
$StudentAAdhar=$row[11];
$GuardianAAdhar=$row[12];
$DateofBirth=$row[13];
$PostalAddress=$row[14];
$NameOfTheSubjects=$row[15];
$PreferredBranch=$row[16];
$NameOfTheSchool=$row[17];
$NameOfTheBoard=$row[18];
$StateofDomicile=$row[19];
$EmailAddress=$row[20];
$SchoolAdmissionRequired=$row[21];
$Selectdateofformsubmission=$row[22];
$Status=$row[23];
$EffectiveFrom=$row[24];
$Remarks=$row[25];
$NameofVendorFoundation=$row[26];
$PhotoURL=$row[27];
$Familymonthlyincome=$row[28];
$Totalnumberoffamilymembers=$row[29];
$Medium=$row[30];
$Fees=$row[31];
$BookStstus=$row[32];
$MyDocument=$row[33];
$ProfileStatus=$row[34];
$lastupdatedon=$row[35];
$colors=$row[36];
$ClassURL=$row[37];
$Remarks1=$row[38];
$Notice=$row[39];
$Badge=$row[40];
$Filterstatus=$row[41];
$AllocationDate=$row[42];
$Maxclass=$row[43];
$Attd=$row[44];
$Leaveapply=$row[45];
$CLTaken=$row[46];
$SLTaken=$row[47];
$OTHTaken=$row[48];
$FileName=$row[49];
$Lastlogin=$row[50]
 
?>     
<?php }?>    