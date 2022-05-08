<?php
    include("database.php");
    if ($_POST['form-type'] == "payment") {
      @$sname = strtoupper($_POST['sname']);
      @$studentid = $_POST['studentid'];
      @$fees = $_POST['fees'];
      @$month = $_POST['month'];
      @$collectedby = $_POST['collectedby'];
      $now = date('Y-m-d H:i:s');
      $feesupdate = "INSERT INTO fees VALUES ('$now','$sname','$studentid','$fees','$month','$collectedby')";
      $result = pg_query($con, $feesupdate);
      echo "done";
    } ?>