<?php
session_start();

date_default_timezone_set('Asia/Kolkata');
$today = date("YmdHis");

//FEES EXPORT FUNTION

$export_type = $_POST["export_type"];
header("Content-type: application/csv");
header("Content-Disposition: attachment; filename={$export_type}_$today.csv");
header("Pragma: no-cache");
header("Expires: 0");

if ($export_type == "fees") {
    fees_export();
} else if ($export_type == "donation") {
    donation_export();
}

function fees_export()
{
    include("database.php");
    @$id = $_POST['id'];
    @$status = $_POST['status'];
    @$section = $_POST['section'];


    if (($section != null && $section != 'ALL') && ($status != null && $status != 'ALL')) {

        $result = pg_query($con, "SELECT * FROM fees 
        
        left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
        left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
        
        WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id AND student.category='$section' order by id desc");
    }


    if (($section == 'ALL' || $section == null) && ($status != null && $status != 'ALL')) {

        $result = pg_query($con, "SELECT * FROM fees 
        
        left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
        left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
        
        WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id order by id desc");
    }

    if (($section != null && $section != 'ALL') && $status == 'ALL') {

        $result = pg_query($con, "SELECT * FROM fees 
        
        left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
        left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
        
        WHERE DATE_PART('year', date::date)=$id AND student.category='$section' order by id desc");
    }

    if (($section == 'ALL' || $section == null) && ($status == 'ALL' || $status == null) && $id != null) {

        $result = pg_query($con, "SELECT * FROM fees 
        
        left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
        left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
        
        WHERE DATE_PART('year', date::date)=$id order by id desc");
    }


    if ($id == null) {
        $result = pg_query($con, "SELECT * FROM fees WHERE month='0'");
    }
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);

    echo 'Fees collection date,ID/F Name,Category,Month,Amount,Collected by' . "\n";

    foreach ($resultArr as $array) {

        echo substr($array['date'], 0, 10) . ',' . $array['studentid'] . '/' . strtok($array['studentname'], ' ') . ',' . $array['category'] . ',' . @strftime('%B', mktime(0, 0, 0,  $array['month'])) . ',' . $array['fees'] . ',' . $array['fullname'] . "\n";
    }
}

function donation_export()
{


    include("database.php");
    @$id = $_POST['invoice'];
    @$status = $_POST['fyear'];


    if ($id == null && $status == 'ALL') {
        $result = pg_query($con, "SELECT * FROM donation order by id desc");
        $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation");
    } else if ($id == null && $status != 'ALL') {
        $result = pg_query($con, "SELECT * FROM donation WHERE year='$status' order by id desc");
        $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation WHERE year='$status'");
    } else if ($id > 0 && $status != 'ALL') {
        $result = pg_query($con, "SELECT * FROM donation WHERE invoice='$id' AND year='$status' order by id desc");
        $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation WHERE invoice='$id' AND year='$status'");
    } else if ($id > 0 && $status == 'ALL') {
        $result = pg_query($con, "SELECT * FROM donation WHERE invoice='$id' order by id desc");
        $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation WHERE invoice='$id'");
    } else {
        $result = pg_query($con, "SELECT * FROM donation order by id desc");
        $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation");
    }

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);

    echo 'Date,Name,Contact,Transaction id,Amount,PAN,Mode of payment,Invoice,URL,Status,By' . "\n";

    foreach ($resultArr as $array) {

        echo substr($array['timestamp'], 0, 10) . ',' . $array['firstname'] . ' ' . $array['lastname'] . ',' . $array['mobilenumber'] . ',' . $array['transactionid'] . ',' . $array['currencyofthedonatedamount'] . ' ' . $array['donatedamount'] . ',' . $array['panno'] . ',' . $array['modeofpayment'] . ',' . $array['invoice'] . ',' . $array['profile'] . ',' ?><?php if ($array['approvedby'] != '--' && $array['approvedby'] != 'rejected') { ?><?php echo 'accepted' ?><?php } else if ($array['approvedby'] == 'rejected') { echo 'rejected' ?><?php } else {echo 'on hold' ?><?php }echo ',' . $array['approvedby'] . "\n";
        }
    }
?>