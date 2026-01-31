<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/paytm-util.php");
include("../../util/email.php");
include("../../util/drive.php");
// require_once("email.php");

date_default_timezone_set('Asia/Kolkata');

$formtype = $_POST['form-type'];

if ($formtype == "payment") {
  @$studentid = $_POST['studentid'];
  @$fees = $_POST['fees'];
  // @$month = join(',', $_POST['month']);
  @$month = $_POST['month'];
  @$collectedby = $_POST['collectedby'];
  @$ptype = $_POST['ptype'];
  @$feeyear = $_POST['year'];
  $now = date('Y-m-d H:i:s');
  $feesupdate = "INSERT INTO fees (date, studentid, fees, month, collectedby, ptype,feeyear) VALUES ('$now','$studentid','$fees','$month','$collectedby','$ptype','$feeyear')";
  $result = pg_query($con, $feesupdate);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "distribution") {
  @$distributedto = $_POST['distributedto'];
  @$distributedby = $_POST['distributedby'];
  @$items = $_POST['items'];
  @$quantity = $_POST['quantity'];
  @$amount = $_POST['amount'];
  @$issuance_date = $_POST['issuance_date'];
  $now = date('Y-m-d H:i:s');
  $distribution_data = "INSERT INTO distribution_data (distributedto, distributedby, items, quantity, issuance_date,timestamp) VALUES ('$distributedto', '$distributedby', '$items', '$quantity', '$issuance_date','$now')";
  $result = pg_query($con, $distribution_data);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "transfer") {
  @$refid = $_POST['pid'];
  $pstatus = "UPDATE fees SET  pstatus = 'transferred' WHERE id = $refid";
  $result = pg_query($con, $pstatus);
}

if ($formtype == "eclose") {
  @$refid = $_POST['eid'];
  $estatus = "UPDATE exams SET  estatus = 'disabled' WHERE exam_id = '$refid'";
  $result = pg_query($con, $estatus);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "noticebodyedit") {
  @$noticeid = $_POST['noticeid'];
  @$noticebody = $_POST['noticebody'];
  $noticebodyedit = "UPDATE notice SET  noticebody = '$noticebody' WHERE noticeid = '$noticeid'";
  $result = pg_query($con, $noticebodyedit);
}

if ($formtype == "pay_comment") {
  @$payslip_entry_id = $_POST['payslip_entry_id'];
  @$comment = $_POST['comment'];
  $commentedit = "UPDATE payslip_entry SET  comment = '$comment' WHERE payslip_entry_id = '$payslip_entry_id'";
  $result = pg_query($con, $commentedit);
}

if ($formtype == "policybodyedit") {
  @$policyid = $_POST['policyid'];
  @$remarks = $_POST['remarks'];
  $policybodyeditt = "UPDATE policy SET  remarks = '$remarks' WHERE policyid = '$policyid'";
  $result = pg_query($con, $policybodyeditt);
}

if ($formtype == "toggleInactive") {
  $policyid = $_POST['policyid'];
  $is_inactive = $_POST['is_inactive'] === 'true' ? 'true' : 'false';

  // Update the is_inactive status
  $toggleQuery = "UPDATE policy SET is_inactive = $is_inactive WHERE policyid = '$policyid'";
  $result = pg_query($con, $toggleQuery);

  if ($result) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
  }
  exit;
}


if ($formtype == "short_description" || $formtype == "long_description") {
  // Get the ticket ID and the new value from the form
  $ticket_id = $_POST['ticket_id'];
  $value = $_POST['value'];

  if (empty($ticket_id) || empty($value)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
  }

  // Determine which column to update
  $column = ($formtype === 'short_description') ? 'short_description' : 'long_description';

  // Prepare and execute the update query
  $query = "UPDATE support_ticket SET $column = $1 WHERE ticket_id = $2";
  $stmt = pg_prepare($con, "update_query", $query);
  $result = pg_execute($con, "update_query", [$value, $ticket_id]);

  // Return a response based on the result
  if ($result) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
  }
}


if ($formtype == "comment_edit" || $formtype == "comment_delete") {
  $formType = $_POST['form-type'];
  $commentId = $_POST['comment_id'];

  if ($formType == 'comment_edit') {
    $comment = $_POST['comment'];

    // Sanitize and validate comment input
    $comment = htmlspecialchars(trim($comment));

    // Use PostgreSQL prepare and execute
    $query = "UPDATE support_comment SET comment = $1, edit_flag = true WHERE comment_id = $2";
    $result = pg_query_params($con, $query, [$comment, $commentId]);

    if ($result) {
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to update comment.']);
    }
    exit; // Stop further script execution
  }

  if ($formType == 'comment_delete') {
    $query = "DELETE FROM support_comment WHERE comment_id = $1";
    $result = pg_query_params($con, $query, [$commentId]);

    if ($result) {
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to delete comment.']);
    }
    exit; // Stop further script execution
  }
}


if ($formtype == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
}

if ($formtype == "policydelete") {
  $policydid = $_POST['policydeleteid'];
  $deletepolicy = "DELETE from policy WHERE policyid = '$policydid'";
  $result = pg_query($con, $deletepolicy);
}

if ($formtype == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
}

if ($formtype == "leavedelete") {
  @$leavedeleteid = $_POST['leavedeleteid'];
  $leavedelete = "DELETE from leavedb_leavedb WHERE leaveid = '$leavedeleteid'";
  $result = pg_query($con, $leavedelete);
}

if ($formtype == "claimdelete") {
  @$claimdeleteid = $_POST['claimdeleteid'];
  $claimdelete = "DELETE from claim WHERE reimbid = '$claimdeleteid'";
  $result = pg_query($con, $claimdelete);
}

if ($formtype == "leaveadjdelete") {
  @$leaveadjdeleteid = $_POST['leaveadjdeleteid'];
  $leaveadjdelete = "DELETE from leaveadjustment WHERE leaveadjustmentid = '$leaveadjdeleteid'";
  $result = pg_query($con, $leaveadjdelete);
}

if ($formtype == "leaveallodelete") {
  @$leaveallodeleteid = $_POST['leaveallodeleteid'];
  $leaveallodelete = "DELETE from leaveallocation WHERE leaveallocationid = '$leaveallodeleteid'";
  $result = pg_query($con, $leaveallodelete);
}

if ($formtype == "cmsdelete") {
  @$certificate_no = $_POST['cmsid'];
  $cmsdelete = "DELETE from certificate WHERE certificate_no = '$certificate_no'";
  $result = pg_query($con, $cmsdelete);
}

if ($formtype == "gemsdelete") {
  @$redeem_id = $_POST['redeem_id'];
  $gemsdelete = "DELETE from gems WHERE redeem_id = '$redeem_id'";
  $result = pg_query($con, $gemsdelete);
}

if ($formtype == "gpsedit") {
  $itemid = isset($_POST['itemid1']) ? pg_escape_string($con, $_POST['itemid1']) : '';
  $updatedby = isset($_POST['updatedby']) ? pg_escape_string($con, $_POST['updatedby']) : '';
  $mode = isset($_POST['mode']) ? $_POST['mode'] : 'details'; // Default to 'details'

  $photo_path = $_FILES['asset_photo'] ?? null;
  $bill_path = $_FILES['purchase_bill'] ?? null;

  $now = date('Y-m-d H:i:s');

  // Fetch current data
  $current_query = "SELECT * FROM gps WHERE itemid = '$itemid'";
  $current_result = pg_query($con, $current_query);
  if (!$current_result || pg_num_rows($current_result) == 0) {
    echo "invalid";
    exit;
  }
  $current_data = pg_fetch_assoc($current_result);

  // Prepare changes array
  $changes = [];
  $update_fields = [];

  if ($mode == 'details') {
    // Full edit mode - process all fields
    $itemname = isset($_POST['itemname']) ? pg_escape_string($con, $_POST['itemname']) : '';
    $itemtype = isset($_POST['itemtype']) ? pg_escape_string($con, $_POST['itemtype']) : '';
    $quantity = isset($_POST['quantity']) ? pg_escape_string($con, $_POST['quantity']) : '';
    $remarks = isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8') : '';
    $asset_status = isset($_POST['asset_status']) ? pg_escape_string($con, $_POST['asset_status']) : '';
    $collectedby = isset($_POST['collectedby']) ? strtoupper(pg_escape_string($con, $_POST['collectedby'])) : '';
    $taggedto = isset($_POST['taggedto']) ? strtoupper(pg_escape_string($con, $_POST['taggedto'])) : '';
    $asset_category = isset($_POST['asset_category']) ? pg_escape_string($con, $_POST['asset_category']) : '';
    $unit_cost = isset($_POST['unit_cost']) ? pg_escape_string($con, $_POST['unit_cost']) : '';
    $purchase_date = isset($_POST['purchase_date']) ? pg_escape_string($con, $_POST['purchase_date']) : '';

    // NEW: Location field
    $location = isset($_POST['location']) && $_POST['location'] !== '' ? (int)$_POST['location'] : null;

    // NEW: Linked assets (JSON array)
    $linked_assets_json = isset($_POST['linked_assets']) ? $_POST['linked_assets'] : '[]';
    $linked_assets = json_decode($linked_assets_json, true);

    // Check each field for changes
    $fields_to_check = [
      'itemtype' => $itemtype,
      'itemname' => $itemname,
      'quantity' => $quantity,
      'remarks' => $remarks,
      'collectedby' => $collectedby,
      'taggedto' => $taggedto,
      'asset_status' => $asset_status,
      'asset_category' => $asset_category,
      'unit_cost' => $unit_cost,
      'purchase_date' => $purchase_date,
      'location' => $location  // NEW: Add location field
    ];

    foreach ($fields_to_check as $field => $new_value) {
      if ($current_data[$field] !== $new_value) {
        $changes[$field] = $new_value;
        $update_fields[] = "$field = '" . pg_escape_string($con, $new_value) . "'";
      }
    }

    // Process asset links (NEW)
    if (is_array($linked_assets)) {
      // Track link changes for history
      $link_changes = [];

      // Get current linked assets
      $current_links_query = "
        SELECT linked_asset_itemid 
        FROM asset_links 
        WHERE asset_itemid = '$itemid' 
        AND is_active = TRUE";
      $current_links_result = pg_query($con, $current_links_query);

      $current_links = [];
      while ($row = pg_fetch_assoc($current_links_result)) {
        $current_links[] = $row['linked_asset_itemid'];
      }

      // Find links to remove (in current but not in new)
      $links_to_remove = array_diff($current_links, $linked_assets);

      // Find links to add (in new but not in current)
      $links_to_add = array_diff($linked_assets, $current_links);

      // Deactivate removed links
      if (!empty($links_to_remove)) {
        $links_to_remove_str = "'" . implode("','", array_map('pg_escape_string', $links_to_remove)) . "'";
        $deactivate_query = "
          UPDATE asset_links 
          SET is_active = FALSE, 
              unlinked_on = '$now',
              linked_by = '$updatedby'
          WHERE asset_itemid = '$itemid' 
          AND linked_asset_itemid IN ($links_to_remove_str)";
        pg_query($con, $deactivate_query);

        $link_changes['removed'] = $links_to_remove;
      }

      // Add new links
      foreach ($links_to_add as $linked_asset_id) {
        $linked_asset_id = pg_escape_string($con, $linked_asset_id);

        // Check if link already exists (inactive)
        $check_query = "
          SELECT id FROM asset_links 
          WHERE asset_itemid = '$itemid' 
          AND linked_asset_itemid = '$linked_asset_id'";
        $check_result = pg_query($con, $check_query);

        if (pg_num_rows($check_result) > 0) {
          // Reactivate existing link
          $update_link_query = "
            UPDATE asset_links 
            SET is_active = TRUE, 
                unlinked_on = NULL,
                linked_on = '$now',
                linked_by = '$updatedby'
            WHERE asset_itemid = '$itemid' 
            AND linked_asset_itemid = '$linked_asset_id'";
        } else {
          // Create new link
          $insert_link_query = "
            INSERT INTO asset_links (
              asset_itemid, 
              linked_asset_itemid, 
              linked_by,
              linked_on
            ) VALUES (
              '$itemid', 
              '$linked_asset_id',
              '$updatedby',
              '$now'
            )";
          pg_query($con, $insert_link_query);
        }
      }

      // Add link changes to main changes array
      if (!empty($link_changes)) {
        $changes['asset_links'] = $link_changes;
      }

      // Add to update_fields to indicate links were processed
      if (!empty($links_to_add) || !empty($links_to_remove)) {
        $update_fields[] = "lastupdatedon = '$now'"; // Force update timestamp
      }
    }
  }

  // Handle file uploads (for both modes)
  $doclink_photo_path = null;
  $doclink_bill_path = null;

  if (!empty($photo_path['name'])) {
    $filename_photo_path = "photo_path_" . "$itemid" . "_" . time();
    $parent_photo_path = '19maeFLJUscJcS6k2xwR6Y-Bg6LtHG7NR';
    $doclink_photo_path = uploadeToDrive($photo_path, $parent_photo_path, $filename_photo_path);

    if ($current_data['asset_photo'] !== $doclink_photo_path) {
      $changes['asset_photo'] = $doclink_photo_path;
      $update_fields[] = "asset_photo = '" . pg_escape_string($con, $doclink_photo_path) . "'";
    }
  }

  if (!empty($bill_path['name'])) {
    $filename_bill_path = "bill_path_" . "$itemid" . "_" . time();
    $parent_bill_path = '1TxjIHmYuvvyqe48eg9q_lnsyt1wDq6os';
    $doclink_bill_path = uploadeToDrive($bill_path, $parent_bill_path, $filename_bill_path);

    if ($current_data['purchase_bill'] !== $doclink_bill_path) {
      $changes['purchase_bill'] = $doclink_bill_path;
      $update_fields[] = "purchase_bill = '" . pg_escape_string($con, $doclink_bill_path) . "'";
    }
  }

  // If there are changes, update the database
  if (!empty($update_fields)) {
    // Ensure lastupdatedon and lastupdatedby are always updated
    if (!in_array("lastupdatedon = '$now'", $update_fields)) {
      $update_fields[] = "lastupdatedon = '$now'";
    }
    if (!in_array("lastupdatedby = '$updatedby'", $update_fields)) {
      $update_fields[] = "lastupdatedby = '$updatedby'";
    }

    $update_query = "UPDATE gps SET " . implode(", ", $update_fields) . " WHERE itemid = '$itemid'";
    $update_result = pg_query($con, $update_query);

    if ($update_result) {
      // Insert into history
      $changes_json = json_encode($changes);
      $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                        VALUES ('$itemid', '" . ($mode == 'upload' ? 'file_upload' : 'edit') . "', '$updatedby', '$now', '" . pg_escape_string($con, $changes_json) . "')";
      pg_query($con, $history_query);

      echo "success";
    } else {
      echo "failed";
    }
  } else {
    // Special handling for upload mode when no files were selected
    if ($mode == 'upload' && empty($photo_path['name']) && empty($bill_path['name'])) {
      echo "nofiles"; // You could add this as a new response type
    } else {
      echo "nochange";
    }
  }
}

if ($formtype == "gen_otp_associate") {
  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$associate_name = $_POST['associate_name'];
  @$email = $_POST['associate_email'];
  $item = $entityManager->getRepository('Onboarding')->find($otp_initiatedfor); //primary key
  if ($item) {
    // Generate a random 6 digit number
    $otp = rand(100000, 999999);
    $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

    $item->setOnboardingGenOtpAssociate($hashedValue);
    $entityManager->persist($item);
    $entityManager->flush();
    echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'onboarding',
        "otp" => @$otp,
        "receiver" => @$associate_name,
      ), $email, False);
    }
  } else {
    echo "failed";
  }
}

if ($formtype == "gen_otp_centr") {

  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$email = $_POST['centre_incharge_email'];
  @$centre_incharge_name = $_POST['centre_incharge_name'];
  $item = $entityManager->getRepository('Onboarding')->find($otp_initiatedfor); //primary key
  if ($item) {
    // Generate a random 6 digit number
    $otp = rand(100000, 999999);
    $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

    $item->setOnboardingGenOtpCenterIncharge($hashedValue);
    $entityManager->persist($item);
    $entityManager->flush();
    echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'onboarding',
        "otp" => @$otp,
        "receiver" => @$centre_incharge_name,
      ), $email, False);
    }
  } else {
    echo "failed";
  }
}

if ($formtype == "get_details_vrc") {
  @$applicationNumber = $_POST['applicationNumber_verify_input'];

  // Query to fetch data based on application number
  $getDetails = "SELECT applicant_name, email FROM signup WHERE application_number = '$applicationNumber'";
  $result = pg_query($con, $getDetails);

  if ($result) {
    $row = pg_fetch_assoc($result);
    if ($row) {
      // Return 'success' with name and email data
      echo json_encode(array(
        'status' => 'success',
        'data' => array(
          'fullname' => $row['applicant_name'],
          'email' => $row['email']
        )
      ));
    } else {
      // No matching record found
      echo json_encode(array('status' => 'no_records', 'message' => 'No records found for the given application number.'));
    }
  } else {
    // Error in query execution
    echo json_encode(array('status' => 'error', 'message' => 'Error retrieving user data.'));
  }
}

if ($formtype == "fetch_employee") {
  $employee_no = strtoupper(pg_escape_string($con, $_POST['employee_no']));
  $query = "SELECT associatenumber AS id, fullname AS name, position 
            FROM rssimyaccount_members 
            WHERE associatenumber = '$employee_no' AND filterstatus = 'Active'";
  $result = pg_query($con, $query);

  if ($result && pg_num_rows($result) > 0) {
    $data = pg_fetch_assoc($result);
    echo json_encode(['success' => true, 'data' => $data]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Employee not found.']);
  }
  exit;
}

if ($formtype == "fetch_rtet") {
  if (isset($_POST['application_number']) && !empty(trim($_POST['application_number']))) {
    // Sanitize the application number by trimming any unnecessary spaces
    $applicationNumber = trim($_POST['application_number']);

    // Step 1: Fetch rtet_session_id from the signup table
    $signupQuery = "SELECT rtet_session_id FROM signup WHERE application_number = $1;";
    $signupResult = pg_query_params($con, $signupQuery, array($applicationNumber));

    if ($signupResult && pg_num_rows($signupResult) > 0) {
      $signupRow = pg_fetch_assoc($signupResult);
      $rtetSessionId = $signupRow['rtet_session_id'];

      if ($rtetSessionId) {
        // Step 2: Fetch status and user_exam_id from test_user_sessions table
        $sessionQuery = "SELECT status, user_exam_id FROM test_user_sessions WHERE id = $1;";
        $sessionResult = pg_query_params($con, $sessionQuery, array($rtetSessionId));

        if ($sessionResult && pg_num_rows($sessionResult) > 0) {
          $sessionRow = pg_fetch_assoc($sessionResult);
          $status = $sessionRow['status'];
          $userExamId = $sessionRow['user_exam_id'];

          // Step 3: Check the status and fetch score accordingly
          if ($status === 'submitted') {
            // Fetch score from test_user_exams table
            $scoreQuery = "SELECT score FROM test_user_exams WHERE id = $1;";
            $scoreResult = pg_query_params($con, $scoreQuery, array($userExamId));

            if ($scoreResult && pg_num_rows($scoreResult) > 0) {
              $scoreRow = pg_fetch_assoc($scoreResult);
              $score = floatval($scoreRow['score']);
              echo json_encode(['status' => 'success', 'writtenTest' => $score]);
            } else {
              echo json_encode(['status' => 'error', 'message' => 'Score not found for the exam.']);
            }
          } elseif ($status === 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Exam has not started yet.']);
          } elseif ($status === 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Exam is still in progress.']);
          } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid exam status.']);
          }
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Session not found for the given application number.']);
        }
      } else {
        echo json_encode(['status' => 'error', 'message' => 'No RTET session found for the given application number.']);
      }
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Application number not found in the signup table.']);
    }
  } else {
    // If application number is missing or invalid
    echo json_encode(['status' => 'error', 'message' => 'Application number is missing or invalid.']);
  }
}

// Add this to your payment-api.php file to handle bulk approvals
if ($_POST['form-type'] == 'bulk-archive-approval') {
  $reviewer_id = $_POST['reviewer_id'];
  $selected_docs = explode(',', $_POST['selected_docs']);
  $bulk_action_type = $_POST['bulk_action_type'];
  $bulk_field_status = $_POST['bulk_field_status'];
  $bulk_reviewer_remarks = $_POST['bulk_reviewer_remarks'];

  $success_count = 0;
  $error_count = 0;

  foreach ($selected_docs as $doc_id) {
    $verification_status = ($bulk_action_type == 'approve') ? 'Verified' : 'Rejected';

    $update_query = "UPDATE archive SET 
                        verification_status = '$verification_status',
                        field_status = '$bulk_field_status',
                        remarks = '$bulk_reviewer_remarks',
                        reviewed_by = '$reviewer_id',
                        reviewed_on = NOW()
                        WHERE doc_id = '$doc_id'";

    if (pg_query($con, $update_query)) {
      $success_count++;
    } else {
      $error_count++;
    }
  }

  echo json_encode([
    'success' => true,
    'message' => "Bulk action completed: $success_count successful, $error_count failed"
  ]);
  exit;
}

if ($formtype == "update-document") {
  // Get the document ID, status, and remarks from the POST data
  $docId = $_POST['doc_id'];
  $status = $_POST['status'];
  $remarks = $_POST['remarks'];
  $field_status = ($_POST['status'] == 'Verified') ? 'disabled' : null;
  $reviewed_by = $_POST['reviewed_by'];
  $reviewed_on = date('Y-m-d H:i:s');

  // Prepare the SQL query to update the document status and remarks
  $query = "UPDATE archive SET verification_status = $1, remarks = $2, field_status = $3,reviewed_by = $4,reviewed_on = $5 WHERE doc_id = $6";
  $result = pg_query_params($con, $query, array($status, $remarks, $field_status, $reviewed_by, $reviewed_on, $docId));

  // Check if the update was successful
  if ($result) {
    // Send a successful JSON response
    echo json_encode(['success' => true, 'message' => 'Document status updated successfully.']);
  } else {
    // Send a failure JSON response
    echo json_encode(['success' => false, 'message' => 'Failed to update document status.']);
  }
}

if ($formtype == "exit_gen_otp_associate") {
  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$associate_name = $_POST['associate_name'];
  @$email = $_POST['associate_email'];
  // Generate a random 6 digit number
  $otp = rand(100000, 999999);
  $hashedValue = password_hash($otp, PASSWORD_DEFAULT);
  $exit_otp_associate = "UPDATE associate_exit SET  exit_gen_otp_associate = '$hashedValue' WHERE exit_associate_id = '$otp_initiatedfor'";
  $result = pg_query($con, $exit_otp_associate);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'exit',
        "otp" => @$otp,
        "receiver" => @$associate_name,
      ), $email, False);
    } else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "exit_gen_otp_centr") {

  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$email = $_POST['centre_incharge_email'];
  @$centre_incharge_name = $_POST['centre_incharge_name'];
  // Generate a random 6 digit number
  $otp = rand(100000, 999999);
  $hashedValue = password_hash($otp, PASSWORD_DEFAULT);
  $exit_otp_centreincharge = "UPDATE associate_exit SET  exit_gen_otp_center_incharge = '$hashedValue' WHERE exit_associate_id = '$otp_initiatedfor'";
  $result = pg_query($con, $exit_otp_centreincharge);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'exit',
        "otp" => @$otp,
        "receiver" => @$centre_incharge_name,
      ), $email, False);
    } else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "initiatingonboarding") {
  @$initiatedfor = $_POST['initiatedfor'];
  @$initiatedby = $_POST['initiatedby'];
  $now = date('Y-m-d H:i:s');
  $initiatingonboarding = "INSERT INTO onboarding (onboarding_associate_id, onboard_initiated_by, onboard_initiated_on) VALUES ('$initiatedfor','$initiatedby','$now')";
  $result = pg_query($con, $initiatingonboarding);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "initiatingexit") {
  @$initiatedfor = $_POST['initiatedfor'];
  @$initiatedby = $_POST['initiatedby'];
  $now = date('Y-m-d H:i:s');
  $initiatingexit = "INSERT INTO associate_exit (exit_associate_id, exit_initiated_by, exit_initiated_on) VALUES ('$initiatedfor','$initiatedby','$now')";
  $result = pg_query($con, $initiatingexit);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "ipfsubmission") {
  @$ipfid = $_POST['ipfid'];
  @$ipf = $_POST['ipf'];
  @$status2 = $_POST['status2'];
  @$ipf_response_by = $_POST['ipf_response_by'];
  @$name = $_POST['ipf_response_by_name'];
  @$email = $_POST['ipf_response_by_email'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_response = '$status2', ipf_response_on = '$now', ipf_response_by='$ipf_response_by' WHERE goalsheetid = '$ipfid'";
  $ipf_history = "INSERT INTO ipf_history (goalsheetid, ipf_response, ipf_response_on, ipf_response_by, ipf) VALUES ('$ipfid','$status2','$now','$ipf_response_by',$ipf)";
  $result = pg_query($con, $ipfclose);
  $result_history = pg_query($con, $ipf_history);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    if ($email != "") {
      sendEmail("ipfsubmission", array(
        "goalsheetid" => @$ipfid,
        "responseby" => @$ipf_response_by,
        "name" => @$name,
        "status" => @$status2,
        "time" => @$now,
      ), $email);
    } else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "transfer_all") {
  $pid = $_POST['pid'];
  $selectedIds = explode(', ', $pid);
  $transferAllQuery = "UPDATE fees
                       SET pstatus = 'transferred'
                       WHERE id IN (" . implode(',', $selectedIds) . ")";
  $result = pg_query_params($con, $transferAllQuery, array());

  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples > 0) {
      echo "success";
    } else {
      echo "failed";
    }
  } else {
    // Handle the error here
    error_log(pg_last_error($con));
    echo "failed";
  }
}

if ($formtype == "gemsredeem") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$redeem_idd = $_POST['redeem_idd'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $gemsredeem = "UPDATE gems SET  reviewer_id = '$reviewer_id',  reviewer_name = '$reviewer_name', reviewer_status = '$reviewer_status', reviewer_remarks = '$reviewer_remarks', reviewer_status_updated_on = '$now' WHERE redeem_id = '$redeem_idd'";
  $result = pg_query($con, $gemsredeem);
}

if ($formtype == "exceptionreviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$exceptionid = $_POST['exceptionid'];
  @$exception_status = $_POST['exception_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $exceptionreview = "UPDATE exception_requests SET  reviewer_id = '$reviewer_id',  status = '$exception_status', reviewer_remarks = '$reviewer_remarks', reviewer_status_updated_on = '$now' WHERE id = '$exceptionid'";
  $result = pg_query($con, $exceptionreview);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "archiveapproval") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$doc_idd = $_POST['doc_idd'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$field_status = $_POST['field_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $archive = "UPDATE archive SET  reviewed_by = '$reviewer_id',  verification_status = '$reviewer_status', field_status = '$field_status',remarks = '$reviewer_remarks', reviewed_on = '$now' WHERE doc_id = '$doc_idd'";
  $result = pg_query($con, $archive);
}

if ($formtype == "leavereviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$leaveid = $_POST['leaveidd'];
  @$status = $_POST['leave_status'];
  @$comment = $_POST['reviewer_remarks'];
  @$fromdate = $_POST['fromdate'];
  @$todate = $_POST['todate'];
  @$halfdayhr = $_POST['is_userhr'] ?? 0;
  $now = date('Y-m-d H:i:s');

  if ($halfdayhr != '' && $halfdayhr != 0) {

    @$day = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1) / 2;
  } else {
    @$day = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);
  }
  $leaveapproval = "UPDATE leavedb_leavedb SET  status = '$status', fromdate = '$fromdate',  todate = '$todate', comment = '$comment',reviewer_id = '$reviewer_id',  reviewer_name = '$reviewer_name', days = '$day', halfday = $halfdayhr WHERE leaveid = '$leaveid'";

  $result = pg_query($con, $leaveapproval);
}

if ($formtype == "claimreviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$reimbidd = $_POST['reimbid'];
  @$claimstatus = $_POST['claimstatus'];
  @$approvedamount = $_POST['approvedamount'] ?? 0;
  @$transactionid = $_POST['transactionid'];
  @$transfereddate = $_POST['transfereddate'];
  @$closedon = $_POST['closedon'];
  @$mediremarks = $_POST['mediremarks'];
  $now = date('Y-m-d H:i:s');

  $baseQuery = "UPDATE claim SET reviewer_id = '$reviewer_id', reviewer_name = '$reviewer_name', updatedon = '$now', claimstatus = '$claimstatus', mediremarks = '$mediremarks'";

  if ($claimstatus == "Claim settled") {
    $claimapproval = "$baseQuery, approvedamount = $approvedamount, transactionid = '$transactionid', transfereddate = '$transfereddate', closedon = '$closedon' WHERE reimbid = '$reimbidd'";
  } elseif ($claimstatus == "Approved") {
    $claimapproval = "$baseQuery, approvedamount = $approvedamount WHERE reimbid = '$reimbidd'";
  } else {
    $claimapproval = "$baseQuery WHERE reimbid = '$reimbidd'";
  }
  $result = pg_query($con, $claimapproval);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "ipfclose") {
  @$ipfid = $_POST['ipfid'];
  @$ipf_process_closed_by = $_POST['ipf_process_closed_by'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_process_closed_by = '$ipf_process_closed_by', ipf_process_closed_on = '$now' WHERE goalsheetid = '$ipfid'";
  $result = pg_query($con, $ipfclose);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "manager_unlock") {
  @$goalsheetid = $_POST['goalsheetid'];
  $manager_unlock = "UPDATE appraisee_response SET appraisee_response_complete= null, manager_unlocked= 'yes' WHERE goalsheetid='$goalsheetid'";
  $result = pg_query($con, $manager_unlock);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "unlock_request") {
  @$goalsheetid = $_POST['goalsheetid'];
  $unlock_request = "UPDATE appraisee_response SET unlock_request= 'yes' WHERE goalsheetid='$goalsheetid'";
  $result = pg_query($con, $unlock_request);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "test") {
  @$sname = $_POST['sname'];
  @$sid = $_POST['sid'];
  @$amount = $_POST['amount'];
  $orderid = "ORDER_" . time();
  $now = date('Y-m-d H:i:s');
  $test = "INSERT INTO test VALUES ('$now', '$sname', '$sid', '$amount','$orderid', 'initiated')";
  $result = pg_query($con, $test);


  // payment step 1: create a order in database - form saved orderId created.
  // create row in database
  $txn_token = get_paytm_tnx_token($orderid, $amount, $sid);
  echo json_encode(
    array(
      "txnToken" => $txn_token,
      "amount" => $amount,
      "orderid" => $orderid
    )
  );
}


if ($formtype === "attendance") {
  $user_id = isset($_POST['userId']) ? $_POST['userId'] : null;
  $recorded_by = isset($_POST['recorded_by']) ? $_POST['recorded_by'] : null;
  $punch_time = date('Y-m-d H:i:s');
  $date = date('Y-m-d');
  function getUserIpAddr()
  {
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // HTTP_X_FORWARDED_FOR can contain a comma-separated list of IPs. The first one is the client's real IP.
      $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      $ip = trim($ipList[0]);
      if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'];
      }
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  $ip_address = getUserIpAddr();

  // Assuming the GPS location is sent from the frontend in the following format
  $latitude = @$_POST['latitude'];
  $longitude = @$_POST['longitude'];
  $gps = ($latitude && $longitude) ? "POINT($latitude $longitude)" : null;

  // Fetch the status from rssimyprofile_student table
  $select_status_query = 'SELECT studentname, filterstatus, category, class FROM rssimyprofile_student WHERE student_id = $1 LIMIT 1';
  pg_prepare($con, "select_status", $select_status_query);
  $status_result = pg_execute($con, "select_status", array($user_id));
  $status_row = pg_fetch_assoc($status_result);

  if ($status_row) {
    // If a result is found in rssimyprofile_student table
    $status = $status_row['filterstatus'];
    $name = $status_row['studentname'];
    $category = $status_row['category'];
    $class = $status_row['class'];
    $engagement = ""; // Assuming engagement is not present in rssimyprofile_student
    $absconding = ""; // Assuming absconding is not present in rssimyprofile_student
  } else {
    // If no result is found in rssimyprofile_student table, fetch from rssimyaccount_members table
    $select_status_query = 'SELECT filterstatus, fullname, engagement, absconding, mandatory_training_pending FROM rssimyaccount_members WHERE associatenumber = $1 LIMIT 1';
    pg_prepare($con, "select_status_members", $select_status_query);
    $status_result_members = pg_execute($con, "select_status_members", array($user_id));
    $status_row_members = pg_fetch_assoc($status_result_members);

    if ($status_row_members) {
      // If a result is found in rssimyaccount_members table
      $status = $status_row_members['filterstatus'];
      $name = $status_row_members['fullname'];
      $engagement = $status_row_members['engagement'];
      $absconding = $status_row_members['absconding'];
      $mandatory_training_pending = $status_row_members['mandatory_training_pending'];
      $category = ""; // Set a default value or handle accordingly, as this column doesn't exist in rssimyaccount_members
      $class = ""; // Set a default value or handle accordingly, as this column doesn't exist in rssimyaccount_members
    } else {
      // If no result is found in both tables, set default values or handle accordingly
      $status = 'default_status';
      $name = 'default_name';
      $engagement = 'default_engagement';
      $category = 'default_category';
      $class = 'default_class';
      $absconding = 'default_absconding';
      $mandatory_training_pending = 'default_mandatory_training_pending';
    }
  }

  // Now $status, $name, $engagement, $category, and $class contain the desired values.

  // Check if absconding is 'Yes' and stop further processing
  if ($absconding === 'Yes') {
    echo json_encode(array(
      "error" => "Error recording attendance. The scanned account is flagged as inactive. Please contact support.",
      "absconding" => $absconding
    ));
    exit(); // Stop further execution so attendance is not inserted
  }

  // Check if mandatory training is pending and stop further processing
  if ($mandatory_training_pending === 't') {
    echo json_encode(array(
      "error" => "Error recording attendance. Mandatory training is pending for this associate. Please contact support.",
      "mandatory_training_pending" => $mandatory_training_pending
    ));
    exit(); // Stop further execution so attendance is not inserted
  }

  // Prepared statement for INSERT query
  $insert_query = 'INSERT INTO public.attendance(user_id, punch_in, ip_address, gps_location, recorded_by, date, status) VALUES ($1, $2, $3, $4, $5, $6, $7)';
  pg_prepare($con, "insert_attendance", $insert_query);
  $result = pg_execute($con, "insert_attendance", array($user_id, $punch_time, $ip_address, $gps, $recorded_by, $date, $status));

  if (pg_affected_rows($result) !== 1) {
    echo json_encode(
      array(
        "error" => "Error recording attendance. Please try again later or contact support."
      )
    );
  } else {
    // Prepared statement for first SELECT query
    $select_query = "SELECT user_id, min(punch_in) punch_in, max(punch_in) punch_out FROM attendance WHERE date='$date' AND user_id='$user_id' GROUP BY user_id";
    $result = pg_query($con, $select_query);
    $row = pg_fetch_assoc($result);
    $punch_in = $row["punch_in"];
    $punch_out = $row["punch_out"];
    if (strtotime($punch_in) === strtotime($punch_out)) {
      // not yet punched out
      $punch_out = "";
    } else {
      $punch_out = date('d/m/Y h:i:s a', strtotime($punch_out));
    }

    // Set the category field based on its availability (either category or engagement)
    $categoryValue = ($category !== "") ? $category : $engagement;

    echo json_encode(
      array(
        "userId" => $user_id,
        "userName" => $name,
        "punchIn" => date('d/m/Y h:i:s a', strtotime($punch_in)),
        "timestamp" => date('d/m/Y h:i:s a', strtotime($punch_time)),
        "status" => $status,
        "category" => $categoryValue,
        "class" => $class,
        "ipAddress" => $ip_address,
        "gpsLocation" => $gps,
        "punchOut" => $punch_out,
      )
    );
  }
}

if ($_POST['form-type'] == "contact_Form") {

  // =========== CAPTCHA VALIDATION ===========
  // Check if CAPTCHA fields exist
  if (!isset($_POST['captchaAnswer']) || !isset($_POST['captchaExpected'])) {
    echo json_encode(array("error" => true, "errorMessage" => "Security verification failed. Please refresh and try again."));
    exit;
  }

  $userAnswer = trim($_POST['captchaAnswer']);
  $expectedAnswer = trim($_POST['captchaExpected']);

  // Convert to integers for comparison
  $userAnswerInt = intval($userAnswer);
  $expectedAnswerInt = intval($expectedAnswer);

  // Validate CAPTCHA answer
  if ($userAnswerInt !== $expectedAnswerInt) {
    echo json_encode(array("error" => true, "errorMessage" => "Incorrect verification answer. Please try again."));
    exit;
  }

  // Honeypot check (if you added honeypot field)
  if (isset($_POST['website']) && !empty(trim($_POST['website']))) {
    // Silently fail for bots - don't give them feedback
    echo json_encode(array("error" => true, "errorMessage" => ""));
    exit;
  }

  // Optional: Time-based validation to prevent too fast submissions
  if (isset($_POST['submission_time']) && isset($_POST['form_load_time'])) {
    $formLoadTime = intval($_POST['form_load_time']);
    $submissionTime = strtotime($_POST['submission_time']) * 1000; // Convert to milliseconds
    $timeDiff = $submissionTime - $formLoadTime;

    // If form was submitted in less than 3 seconds, likely a bot
    if ($timeDiff < 3000) {
      echo json_encode(array("error" => true, "errorMessage" => "Please take your time to fill the form properly."));
      exit;
    }
  }
  // =========== END CAPTCHA VALIDATION ===========

  // Retrieve form data (existing code)
  $queryid = uniqid();
  $name = $_POST['name'];
  $email = $_POST['email'];
  $contact = $_POST['contact'];
  $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
  $timestamp = date('Y-m-d H:i:s');

  // Insert data into the database
  $contactinsert = "INSERT INTO contact (queryid, name, email, contact, message, timestamp) 
                      VALUES (
                          '$queryid', 
                          '$name', 
                          '$email', 
                          '$contact', 
                          '$message', 
                          '$timestamp'
                      );";

  // Execute the query
  $result = pg_query($con, $contactinsert);

  // Check if the query was successful
  if ($result) {
    // Query executed successfully
    echo json_encode(array("error" => false, "queryid" => $queryid));
  } else {
    // Query failed
    echo json_encode(array("error" => true, "errorMessage" => "Error occurred during form submission."));
  }

  if ($email != "") {
    sendEmail("contact_us_ack", array(
      "name" => $name,
      "queryid" => $queryid,
      "email" => $email,
      "timestamp" => date("d/m/Y h:i A", strtotime($timestamp)),
      "contact" => @$contact,
      "message" => $message
    ), $email, true);
  }
}

if (isset($_POST['form-type']) && $_POST['form-type'] == 'holiday') {
  // Get the year from the POST request
  $year = isset($_POST['year']) ? $_POST['year'] : date("Y");

  // Query to fetch holidays for the given year
  $query = "SELECT holiday_date, TO_CHAR(holiday_date, 'Day') AS day, holiday_name 
          FROM holidays 
          WHERE EXTRACT(YEAR FROM holiday_date) = $1
          AND is_public=true
          ORDER BY holiday_date ASC";
  // Prepare the query
  $result = pg_prepare($con, "holiday_query", $query);

  // Execute the query with the year parameter
  $result = pg_execute($con, "holiday_query", array($year));

  // Check if the query executed successfully
  if ($result) {
    $holidays = pg_fetch_all($result);
    if ($holidays) {
      echo json_encode($holidays);
    } else {
      echo json_encode(["message" => "No holidays found for this year."]);
    }
  } else {
    echo json_encode(["error" => "Error executing the query."]);
  }
}

if (@$_POST['form-type'] == "hierarchy") {
  function fetchHierarchy($associatenumber, $con)
  {
    $hierarchy = [];
    $seen = []; // To track visited nodes and prevent infinite loops

    while (!in_array($associatenumber, $seen)) {
      $seen[] = $associatenumber;

      $sql = "SELECT fullname, associatenumber, position, supervisor, photo FROM rssimyaccount_members WHERE associatenumber = '$associatenumber'";
      $result = pg_query($con, $sql);
      if ($result && pg_num_rows($result) > 0) {
        $currentAssociate = pg_fetch_assoc($result);
        $hierarchy[] = $currentAssociate;

        $associatenumber = $currentAssociate['supervisor'];
      } else {
        break; // Stop if no supervisor is found
      }
    }

    return array_reverse($hierarchy); // Reverse the hierarchy for the desired order
  }

  header('Content-Type: application/json');
  $associatenumber = htmlspecialchars($_POST['associatenumber'], ENT_QUOTES, 'UTF-8');
  $hierarchy = fetchHierarchy($associatenumber, $con);
  echo json_encode($hierarchy);
  exit;
}
if (@$_POST['form-type'] == "reportees") {
  function fetchReportees($associatenumber, $con)
  {
    $reportees = [];

    // SQL query to get associates where their supervisor matches the passed associatenumber
    $sql = "SELECT fullname, associatenumber, position, supervisor, photo FROM rssimyaccount_members WHERE supervisor = '$associatenumber' AND filterstatus='Active'";
    $result = pg_query($con, $sql);

    if ($result && pg_num_rows($result) > 0) {
      while ($row = pg_fetch_assoc($result)) {
        $reportees[] = $row;
      }
    }

    return $reportees;
  }

  header('Content-Type: application/json');
  $associatenumber = htmlspecialchars($_POST['associatenumber'], ENT_QUOTES, 'UTF-8');
  $reportees = fetchReportees($associatenumber, $con);
  echo json_encode($reportees);
  exit;
}
if (isset($_POST['form-type']) && $_POST['form-type'] == 'post_review') {
  $event_id = $_POST['event_id'] ?? null;
  $review_status = $_POST['review_status'] ?? null;

  if ($event_id && $review_status) {
    $query = "UPDATE events SET review_status = $1, reviewed_by = $2, reviewed_at = NOW() WHERE event_id = $3";
    $reviewed_by = $_POST['reviewed_by']; // Use the logged-in user's ID here

    $result = pg_query_params(
      $con,
      $query,
      [$review_status, $reviewed_by, $event_id]
    );
    $cmdtuples = pg_affected_rows($result);

    if ($cmdtuples === 1) {
      echo json_encode(['success' => true, 'message' => 'The event has been reviewed successfully.']);
    } else {
      echo json_encode(['success' => false, 'message' => 'ERROR: There was an error while reviewing the event. Please try again.']);
    }
  }
}
if (isset($_POST['form-type']) && $_POST['form-type'] == 'orders') {
  // Validate and sanitize inputs
  $totalPoints = isset($_POST['totalPoints']) ? (int)$_POST['totalPoints'] : null;
  $cart = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : null;
  $orderBy = isset($_POST['associatenumber']) ? pg_escape_string($con, $_POST['associatenumber']) : null;
  $doj = ($_POST['doj']);
  $email = ($_POST['email']);
  $fullname = ($_POST['fullname']);
  // Fetch total gems redeemed and received for the user
  $query_totalgemsredeem = pg_query_params(
    $con,
    "SELECT COALESCE(SUM(product_points), 0) 
     FROM order_items 
     JOIN orders ON orders.id = order_items.order_id 
     WHERE order_by = $1 AND (status IS NULL OR status != 'Refunded')",
    [$orderBy]
  );
  $query_totalgemsreceived = pg_query(
    $con,
    "SELECT COALESCE(SUM(gems), 0) 
     FROM certificate 
     WHERE awarded_to_id = '$orderBy'"
  );

  // Fetch results
  $totalgemsredeem = pg_fetch_result($query_totalgemsredeem, 0, 0);
  $totalgemsreceived = pg_fetch_result($query_totalgemsreceived, 0, 0);

  // Calculate max limit (total gems received - total gems redeemed)
  $maxLimit = $totalgemsreceived - $totalgemsredeem;

  if ($totalPoints === null || $cart === null || $orderBy === null) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
    exit;
  }

  // First check: Validate if totalPoints are 1000 or more
  if ($maxLimit < 1000) {
    echo json_encode(['status' => 'error', 'message' => 'You can only redeem gems if you have 1000 or more points.']);
    exit;
  }

  // Check if totalPoints exceed maxLimit
  if ($totalPoints > $maxLimit) {
    echo json_encode(['status' => 'error', 'message' => 'Total points exceed the maximum limit.']);
    exit;
  }

  // Check if DOJ duration is less than 1 year
  $dojDate = new DateTime($doj);
  $currentDate = new DateTime();
  $diff = $currentDate->diff($dojDate);

  if ($diff->y < 1) {
    echo json_encode(['status' => 'error', 'message' => 'You are not yet eligible to redeem gems, as your Date of Joining is less than 1 year.']);
    exit;
  }

  // Insert into orders table
  $query = "INSERT INTO orders (total_points, order_by) VALUES ($totalPoints, '$orderBy') RETURNING id, order_date";
  $result = pg_query($con, $query);

  if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert order.']);
    exit;
  }

  $orderId = pg_fetch_result($result, 0, 'id');
  $orderDate = pg_fetch_result($result, 0, 'order_date');

  // Insert into order_items table
  foreach ($cart as $item) {
    $productId = (int)$item['productId'];
    $quantity = (int)$item['count'];
    $productPoints = (int)$item['productPoints'];
    $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity,product_points) VALUES ($orderId, $productId, $quantity,$productPoints)";
    if (!pg_query($con, $itemQuery)) {
      echo json_encode(['status' => 'error', 'message' => 'Failed to insert order item.']);
      exit;
    }
  }
  // Call sendEmail to notify the user
  sendEmail("redeem_apply", array(
    "fullname" => $fullname,
    "totalPoints" => $totalPoints,
    "orderId" => $orderId,
    "now" => date("d/m/Y g:i a", strtotime($orderDate))
  ), $email);
  echo json_encode(['status' => 'success', 'message' => 'Order placed successfully!']);
}
