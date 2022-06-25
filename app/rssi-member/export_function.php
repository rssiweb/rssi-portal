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
else if ($export_type == "student") {
    student_export();
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

    echo 'Fees collection date,ID/F Name,Category,Month,Amount,Collected by,Status' . "\n";

    foreach ($resultArr as $array) {

        echo substr($array['date'], 0, 10) . ',' . $array['studentid'] . '/' . strtok($array['studentname'], ' ') . ',' . $array['category'] . ',' . @strftime('%B', mktime(0, 0, 0,  $array['month'])) . ',' . $array['fees'] . ',' . $array['fullname'] . ',' . $array['pstatus'] . "\n";
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

    echo 'Sl. No.,Pre Acknowledgement Number,ID Code,Unique Identification Number,Section Code,Unique Registration Number (URN),Date of Issuance of Unique Registration Number,Name of donor,Address of donor,Donation Type,Mode of receipt,Amount of donation (Indian rupees)' . "\n";

    foreach ($resultArr as $array) {

        echo ',' . ','. $array['uitype'] . ',' . $array['uinumber'] . ',' . $array['section_code'] . ',' . $array['invoice'] . ',' . date("d/m/Y H:i", strtotime($array['timestamp'])) . ',' . $array['firstname'] . ' ' . $array['lastname'] . ',"' . $array['address'] . '",' . $array['donation_type'] . ',' . $array['modeofpayment'] . ',' . $array['currencyofthedonatedamount'] . ' ' . $array['donatedamount'] . "\n";

        }
    }

    function student_export()
{


    include("database.php");
    @$module = $_POST['module'];
    @$id = $_POST['id'];
    @$category = $_POST['category'];
    @$class = $_POST['class'];


    if ($id == 'ALL' && $category == 'ALL' && ($class == 'ALL' || $class==null)) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
        left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE module='$module' order by filterstatus asc, category asc");
      } 
      
      if ($id == 'ALL' && $category == 'ALL' && ($class != 'ALL' && $class != null)) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student
        left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE class='$class' AND module='$module' order by category asc");
      } 
      
      if ($id != 'ALL' && $category == 'ALL' && ($class == null || $class == 'ALL')) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
        left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE filterstatus='$id' AND module='$module' order by category asc");
      } 
      
      if ($id != 'ALL' && $category != 'ALL' && ($class != 'ALL' && $class != null)) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE filterstatus='$id' AND module='$module' AND category='$category' order by category asc");
      } 
      
      if ($id == 'ALL' && $category != 'ALL' && $class != 'ALL' && $class != null) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE class='$class' AND module='$module' AND category='$category' order by filterstatus asc,category asc");
      }
      
      if ($id != 'ALL' && $category != 'ALL' && $class != 'ALL' && $class != null) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE class='$class' AND module='$module' AND filterstatus='$id' AND category='$category' order by category asc");
      }
      
      if ($id != 'ALL' && $category == 'ALL' && $class == 'ALL') {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE module='$module' AND filterstatus='$id' order by category asc");
      }
      
      if ($id != 'ALL' && $category == 'ALL' && $class != 'ALL' && $class != null) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE class='$class' AND module='$module' AND filterstatus='$id' order by category asc");
      }
      
      if ($id != 'ALL' && $category != 'ALL' && ($class == 'ALL' || $class==null)) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE category='$category' AND module='$module' AND filterstatus='$id' order by category asc");
      }
      
      if ($id == 'ALL' && $category != 'ALL' && ($class == 'ALL' || $class==null)) {
        $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
          ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE module='$module' AND category='$category' order by category asc");
      }      

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);

    echo 'Student Id,Name,Category,Class,DOA,Paid month,Special Service' . "\n";

    foreach ($resultArr as $array) {

        echo $array['student_id'] . ',' . $array['studentname'] . ',' . $array['category'] . ',' . $array['class'] . ',' . $array['doa'] . ',' . $array['maxmonth'] .',' . $array['special_service'] ."\n";
        }
    }
