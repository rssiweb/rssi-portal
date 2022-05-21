<!-- This API has been used in student.php file for fees collection -->
<?php
    include("database.php");
    date_default_timezone_set('Asia/Kolkata');
    echo json_encode($_POST);
    if ($_POST['form-type'] == "payment") {
      @$sname = strtoupper($_POST['sname']);
      @$studentid = $_POST['studentid'];
      @$fees = $_POST['fees'];
      // @$month = join(',', $_POST['month']);
      @$month = $_POST['month'];
      @$collectedby = $_POST['collectedby'];
      $now = date('Y-m-d H:i:s');
      $feesupdate = "INSERT INTO fees VALUES ('$now','$sname','$studentid','$fees','$month','$collectedby')";
      $result = pg_query($con, $feesupdate);
    } ?>

<?php
    include("database.php");
    if ($_POST['form-type'] == "transfer") {
      @$refid = $_POST['pid'];
      $pstatus = "UPDATE fees SET  pstatus = 'transferred' WHERE id = $refid";
      $result = pg_query($con, $pstatus);
    } ?>