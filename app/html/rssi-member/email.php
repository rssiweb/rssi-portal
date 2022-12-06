<?php
// This API has been used in student.php file for fees collection 
include("database.php");
date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");

function SendLeaveUpdateEmail($leaveid,$reviewer_id)
{
    global $con;
    $result= pg_query($con,"Select applicantid,applicantname,fromdate,todate,typeofleave,category,creason from leavedb_leavedb where leaveid='$leaveid'");
    $applicantid = pg_fetch_result($result, 0, 0);
    $applicantname = pg_fetch_result($result, 0, 1);
    $fromdate = pg_fetch_result($result, 0, 2);
    $todate = pg_fetch_result($result, 0, 3);
    $typeofleave = pg_fetch_result($result, 0, 4);
    $category = pg_fetch_result($result, 0, 5);
    $creason = pg_fetch_result($result, 0, 6);
    @$now = date('Y-m-d H:i:s');

    $result= pg_query($con,"SELECT fullname, email, phone FROM rssimyaccount_members where associatenumber='$applicantid'");
   if (pg_num_rows( $result)){
    $fullname = pg_fetch_result($result, 0, 0);
    $email = pg_fetch_result($result, 0, 1);
    $phone = pg_fetch_result($result, 0, 2);
   }

    $result= pg_query($con,"SELECT studentname,emailaddress, contact FROM rssimyprofile_student where student_id='$applicantid'");
    if (pg_num_rows( $result)){
        $fullname = pg_fetch_result($result, 0, 0);
        $email = pg_fetch_result($result, 0, 1);
        $phone = pg_fetch_result($result, 0, 2);
       }

       $result= pg_query($con,"SELECT fullname, email, phone FROM rssimyaccount_members where associatenumber='$reviewer_id'");
       if (pg_num_rows( $result)){
        $fullname_reviewer = pg_fetch_result($result, 0, 0);
        $email_reviewer = pg_fetch_result($result, 0, 1);
        $phone_reviewer = pg_fetch_result($result, 0, 2);
       }
    

    sendEmail("leaveconf", array(
      "leaveid" => $leaveid,
      "applicantid" => $applicantid,
      "applicantname" => @$fullname,
      "fromdate" => @date("d/m/Y", strtotime($fromdate)),
      "todate" => @date("d/m/Y", strtotime($todate)),
      "typeofleave" => $typeofleave,
      "category" => $creason,
      "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1),
      "now" => @date("d/m/Y g:i a", strtotime($now))
  ), $email);

  sendEmail("leaveconf", array(
    "leaveid" => $leaveid,
    "applicantid" => $applicantid,
    "applicantname" => @$fullname,
    "fromdate" => @date("d/m/Y", strtotime($fromdate)),
    "todate" => @date("d/m/Y", strtotime($todate)),
    "typeofleave" => $typeofleave,
    "category" => $creason,
    "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1),
    "now" => @date("d/m/Y g:i a", strtotime($now))
), $email_reviewer);
return 'hello 1.5';
}
?>
