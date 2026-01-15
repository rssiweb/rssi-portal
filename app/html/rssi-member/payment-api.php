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


if ($formtype == "get_details") {
  @$contactnumber = $_POST['contactnumber_verify_input'];
  $getdetails = "SELECT fullname, email, tel FROM donation_userdata WHERE tel='$contactnumber'";
  $result = pg_query($con, $getdetails);
  if ($result) {
    $row = pg_fetch_assoc($result);
    if ($row) {
      echo json_encode(array('status' => 'success', 'data' => $row));
    } else {
      echo json_encode(array('status' => 'no_records', 'message' => 'No records found in the database. Donate as a new user.'));
    }
  } else {
    echo json_encode(array('status' => 'error', 'message' => 'Error retrieving user data'));
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

if ($formtype == "donation_form") {
  if (isset($_POST['form-type']) && $_POST['form-type'] === "donation_form") {
    $tel = $_POST['tel'];
    $currency = $_POST['currency'];
    $transactionId = $_POST['transactionid'];
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    $donationAmount = $_POST['donationAmount'];
    $timestamp = date('Y-m-d H:i:s');
    $donationId = uniqid();
    $cmdtuples = 0; // Initialize cmdtuples
    $errorOccurred = false; // Flag to track if an error occurred
    $errorMessage = '';

    if ($_POST['donationType'] === "existing") {
      $donationQuery = "INSERT INTO donation_paymentdata (donationid, tel, currency, amount, transactionid, message, timestamp) 
                          VALUES ('$donationId', '$tel', '$currency', '$donationAmount', '$transactionId', '$message', '$timestamp')";
      $resultUserdata = pg_query($con, $donationQuery);

      if ($resultUserdata) {
        $cmdtuples = pg_affected_rows($resultUserdata);
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionError($con, "Donation insertion", $tel);
      }
    } elseif ($_POST['donationType'] === "new") {
      $fullName = $_POST['fullName'];
      $email = $_POST['email'];
      $contactNumberNew = $_POST['contactNumberNew'];
      $idType = $_POST['idType'];
      $idNumber = $_POST['idNumber'];
      $governmentId = $_FILES['governmentId'];
      $postalAddress = htmlspecialchars($_POST['postalAddress'], ENT_QUOTES, 'UTF-8');

      // This is for governmentId which will be require for new visitor

      if (empty($_FILES['governmentId']['name'])) {
        $doclink_governmentId = null;
      } else {
        $filename_governmentId = "doc_" . $fullName . "_" . time();
        $parent_governmentId = '14NyXPZBNzetqMPr4i8Blf_wtItsSJ5eJ';
        $doclink_governmentId = uploadeToDrive($governmentId, $parent_governmentId, $filename_governmentId);
      }

      // Insert userdata
      $userdataQuery = "INSERT INTO donation_userdata (fullname, email, tel, id_type, id_number, nationalid, postaladdress) 
                          VALUES ('$fullName', '$email', '$contactNumberNew', '$idType', '$idNumber', '$doclink_governmentId', '$postalAddress')";
      @$resultUserdata = pg_query($con, $userdataQuery);

      if ($resultUserdata) {
        // Insert donation
        $donationQuery = "INSERT INTO donation_paymentdata (donationid, tel, currency, amount, transactionid, message, timestamp) 
                              VALUES ('$donationId', '$contactNumberNew', '$currency', '$donationAmount', '$transactionId', '$message', '$timestamp')";
        $resultDonation = pg_query($con, $donationQuery);

        if ($resultDonation) {
          $cmdtuples = pg_affected_rows($resultDonation);
        } else {
          $errorOccurred = true;
          $errorMessage = handleInsertionError($con, "Donation insertion", $contactNumberNew);
        }
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionError($con, "Userdata insertion", $contactNumberNew);
      }
    }

    // After successful form submission
    if (!$errorOccurred) {
      // Sending email based on the donation type
      if ($_POST['donationType'] === "existing") {
        $emailQuery = "SELECT email, fullname FROM donation_userdata WHERE tel='$tel'";
      } else if ($_POST['donationType'] === "new") {
        $emailQuery = "SELECT email, fullname FROM donation_userdata WHERE tel='$contactNumberNew'";
      }
      $result = pg_query($con, $emailQuery);

      if ($result) {
        $row = pg_fetch_assoc($result);
        $email = $row['email'];
        $name = $row['fullname'];
      } else {
        // Handle error if the query fails
        $email = null;
        $name = null;
      }

      if (($_POST['donationType'] === "existing" || $_POST['donationType'] === "new") && $email != "") {
        sendEmail("donation_ack", array(
          "fullname" => $name,
          "donationId" => $donationId,
          "timestamp" => $timestamp,
          "tel" => @$tel . @$contactNumberNew,
          "email" => $email,
          "transactionid" => $transactionId,
          "currency" => $currency,
          "amount" => $donationAmount
        ), $email);
      }
    }

    // Prepare the API response data
    $responseData = array(
      'error' => $errorOccurred,
      'errorMessage' => $errorOccurred ? $errorMessage : '', // Return the actual error message if an error occurred
      'cmdtuples' => $cmdtuples,
      'donationId' => $donationId
    );

    // Return the response as JSON
    echo json_encode($responseData);
    exit; // Stop further PHP execution
  }
}

function handleInsertionError($connection, $errorMessage, $tel)
{
  $error = pg_last_error($connection);

  // Check for duplicate key violation error
  if (strpos($error, 'duplicate key value violates unique constraint') !== false) {
    return "already_registered"; // Return a specific code for duplicate key violation error
    // Additional handling specific to duplicate key violation error can be done here
  } else {
    // Log or display the generic error message
    error_log($errorMessage . " error: " . $error);
    return "generic_error"; // Return a specific code for generic error
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

  // Close the database connection
  pg_close($con);
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

if ($formtype == "donation_review") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$donationid = $_POST['donationid'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $donation_review = "UPDATE donation_paymentdata SET  reviewedby = '$reviewer_id', status = '$reviewer_status', reviewer_remarks = '$reviewer_remarks', reviewedon = '$now' WHERE donationid = '$donationid'";
  $result = pg_query($con, $donation_review);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
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

if (@$_POST['form-type'] == "admission") {

  $type_of_admission = $_POST['type-of-admission'];
  $student_name = $_POST['student-name'];
  $date_of_birth = $_POST['date-of-birth'];
  $gender = $_POST['gender'];
  $uploadedFile_student_photo = $_FILES['student-photo'];
  $aadhar_available = $_POST['aadhar-card'];
  $aadhar_card = $_POST['aadhar-number'];
  $uploadedFile_aadhar_card = $_FILES['aadhar-card-upload'];
  $guardian_name = $_POST['guardian-name'];
  $guardian_relation = $_POST['relation'];
  $guardian_aadhar = $_POST['guardian-aadhar-number'];
  $state_of_domicile = $_POST['state'];
  $postal_address = htmlspecialchars($_POST['postal-address'], ENT_QUOTES, 'UTF-8');
  $permanent_address = htmlspecialchars($_POST['permanent-address'], ENT_QUOTES, 'UTF-8');
  $telephone_number = $_POST['telephone'];
  $email_address = $_POST['email'];
  $preferred_branch = $_POST['branch'];
  $class = $_POST['class'];
  $school_admission_required = $_POST['school-required'];
  $school_name = htmlspecialchars($_POST['school-name'], ENT_QUOTES, 'UTF-8');
  $board_name = $_POST['board-name'];
  $medium = $_POST['medium'];
  $family_monthly_income = $_POST['income'];
  $total_family_members = $_POST['family-members'];
  $payment_mode = $_POST['payment-mode'];
  $c_authentication_code = $_POST['c-authentication-code'];
  $transaction_id = $_POST['transaction-id'];
  $subject_select = $_POST['subject-select'];
  $timestamp = $_POST['admission-date'];
  $caste_document = $_FILES['caste-document'];
  $caste = $_POST['caste'];
  $c_auth_session_id = isset($_POST['c_auth_session_id']) ? $_POST['c_auth_session_id'] : null;
  $is_verified = false; // default
  $sup_document = $_FILES['supporting-document'];

  if ($c_auth_session_id) {
    $query = "SELECT is_verified FROM cash_verification_codes WHERE session_id = $1 LIMIT 1";
    $result = pg_query_params($con, $query, array($c_auth_session_id));
    if ($row = pg_fetch_assoc($result)) {
      // Adjust based on how is_verified is stored
      $is_verified = $row['is_verified'];

      // echo json_encode(array(
      //   'is_verified' => $is_verified,
      //   'c_auth_session_id' => $c_auth_session_id,
      //   'message' => $is_verified ? 'Verification successful.' : 'Verification failed.'
      // ));
      // exit;
    }
  }

  if ($payment_mode == 'online' || ($payment_mode == 'cash' && $is_verified == 't')) {
    // Determine if student is new admission or not
    $is_new_admission = 'A'; // Set to true if new admission, false otherwise

    // if ($type_of_admission == 'New Admission') {
    //   $is_new_admission = 'A';
    // } else {
    //   $is_new_admission = 'B';
    // }

    // Determine branch code
    $branch = ''; // Set branch code (LKO for Lucknow, KOL for West Bengal)

    // Replace with the correct branch code based on the branch location
    if ($preferred_branch == 'Lucknow') {
      $branch = 'LKO';
    } elseif ($preferred_branch == 'West Bengal') {
      $branch = 'KOL';
    }

    // Determine current year
    $current_year = date('y'); // Two-digit year from current date

    // Generate a random 3-digit number
    $random_number = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);

    // Generate student ID
    $logic_1 = $is_new_admission; // Logic 1
    $logic_2 = $branch; // Logic 2
    $logic_3 = $current_year; // Logic 3
    $logic_4 = $random_number; // Logic 4

    $student_id = $logic_1 . $logic_2 . $logic_3 . $logic_4; // Concatenate all four logics

    // send uploaded file to drive
    // get the drive link
    if (empty($_FILES['aadhar-card-upload']['name'])) {
      $doclink_aadhar_card = null;
    } else {
      $filename_aadhar_card = "doc_" . $student_name . "_" . time();
      $parent_aadhar_card = '186KMGzX07IohJUhQ72mfHQ6NHiIKV33E';
      $doclink_aadhar_card = uploadeToDrive($uploadedFile_aadhar_card, $parent_aadhar_card, $filename_aadhar_card);
    }
    if (empty($_FILES['student-photo']['name'])) {
      $doclink_student_photo = null;
    } else {
      $filename_student_photo = "doc_" . $student_name . "_" . time();
      $parent_student_photo = '1R1jZmG7xUxX_oaNJaT9gu68IV77zCbg9';
      $doclink_student_photo = uploadeToDrive($uploadedFile_student_photo, $parent_student_photo, $filename_student_photo);
    }

    if (empty($_FILES['caste-document']['name'])) {
      $doclink_caste_document = null;
    } else {
      $filename_caste_document = "caste_" . $student_name . "_" . time();
      $parent_caste_document = '186KMGzX07IohJUhQ72mfHQ6NHiIKV33E';
      $doclink_caste_document = uploadeToDrive($caste_document, $parent_caste_document, $filename_caste_document);
    }
    if (empty($_FILES['supporting-document']['name'])) {
      $doclink_sup_document = null;
    } else {
      $filename_sup_document = "sup_" . $student_name . "_" . time();
      $parent_sup_document = '1h2elj3V86Y65RFWkYtIXTJFMwG_KX_gC';
      $doclink_sup_document = uploadeToDrive($sup_document, $parent_sup_document, $filename_sup_document);
    }

    $student = "INSERT INTO rssimyprofile_student (type_of_admission,studentname,dateofbirth,gender,student_photo_raw,aadhar_available,studentaadhar,upload_aadhar_card,guardiansname,relationwithstudent,guardianaadhar,stateofdomicile,postaladdress,permanentaddress,contact,emailaddress,preferredbranch,class,schooladmissionrequired,nameoftheschool,nameoftheboard,medium,familymonthlyincome,totalnumberoffamilymembers,payment_mode,c_authentication_code,transaction_id,student_id,nameofthesubjects,doa,caste,caste_document,supporting_doc) VALUES ('$type_of_admission','$student_name','$date_of_birth','$gender','$doclink_student_photo','$aadhar_available','$aadhar_card','$doclink_aadhar_card','$guardian_name','$guardian_relation','$guardian_aadhar','$state_of_domicile','$postal_address','$permanent_address','$telephone_number','$email_address','$preferred_branch','$class','$school_admission_required','$school_name','$board_name','$medium','$family_monthly_income','$total_family_members','$payment_mode','$c_authentication_code','$transaction_id','$student_id','$subject_select','$timestamp','$caste','$doclink_caste_document','$doclink_sup_document')";

    $result = pg_query($con, $student);

    // Insert new history record
    $insertHistoryQuery = "INSERT INTO student_category_history (
      student_id, 
      category_type, 
      effective_from,
      class, 
      created_by
    ) VALUES (
      '$student_id', 
      '$type_of_admission', 
      DATE '$timestamp',
      $class, 
      'System'
    )";
    pg_query($con, $insertHistoryQuery);

    if ($result) {
      $cmdtuples = pg_affected_rows($result);
      if ($cmdtuples == 1) {
        $response = array("success" => true, "message" => "Form submitted successfully.");
        echo json_encode($response);
        if (@$cmdtuples == 1 && $email_address != "") {
          sendEmail("admission_success", array(
            "student_id" => $student_id,
            "student_name" => $student_name,
            "preferred_branch" => $preferred_branch,
            "doa" => date('d/m/Y', strtotime($timestamp)),
            "timestamp" => date("d/m/Y g:i a")
          ), $email_address);
        }
      } else {
        $response = array("success" => false, "message" => "Form submission failed.");
        echo json_encode($response);
      }
    }
  } else {
    $response = array("success" => false, "message" => "Form submission failed.");
    echo json_encode($response);
  }
}

if ($formtype == "get_details_visit") {
  @$contactnumber = $_POST['contactnumber_verify_input'];
  $getdetails = "SELECT fullname, email, tel FROM visitor_userdata WHERE tel='$contactnumber'";
  $result = pg_query($con, $getdetails);
  if ($result) {
    $row = pg_fetch_assoc($result);
    if ($row) {
      echo json_encode(array('status' => 'success', 'data' => $row));
    } else {
      echo json_encode(array('status' => 'no_records', 'message' => 'No records found in the database. Register as a new user.'));
    }
  } else {
    echo json_encode(array('status' => 'error', 'message' => 'Error retrieving user data'));
  }
}

if ($formtype == "visitor_form") {
  if (isset($_POST['form-type']) && $_POST['form-type'] === "visitor_form") {
    $tel = $_POST['tel'];
    $visitbranch = $_POST['visitbranch'];
    $visitstartdatetime = $_POST['visitstartdatetime'];
    $visitenddate = $_POST['visitenddate'];
    $visitpurpose = $_POST['visitpurpose'];
    $other_reason = htmlspecialchars($_POST['other_reason'], ENT_QUOTES, 'UTF-8');
    $institutename = $_POST['institutename'];
    $enrollmentnumber = $_POST['enrollmentnumber'];
    $instituteid = $_FILES['instituteid'];
    $mentoremail = $_POST['mentoremail'];
    $paymentdoc = $_FILES['paymentdoc'];
    $mentoremail = $_POST['mentoremail'];
    $declaration = $_POST['declaration'];
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    $timestamp = date('Y-m-d H:i:s');
    $visitId = uniqid();
    $cmdtuples = 0; // Initialize cmdtuples
    $errorOccurred = false; // Flag to track if an error occurred
    $errorMessage = '';
    $additional_services_array = [];

    if (!empty($_POST['additional_services'])) {

      foreach ($_POST['additional_services'] as $service) {
        if ($service === 'videography') {
          // Append camera count
          $camera_count = isset($_POST['camera_count']) ? intval($_POST['camera_count']) : 1;
          $additional_services_array[] = "videography({$camera_count})";
        } elseif ($service === 'interview') {
          // Append duration
          $duration = isset($_POST['interview_duration']) ? intval($_POST['interview_duration']) : 30;
          $additional_services_array[] = "interview({$duration}min)";
        } else {
          // Other services like certificate
          $additional_services_array[] = $service;
        }
      }
    }

    // Convert array to comma-separated string for DB
    $additional_services = implode(',', $additional_services_array);

    // This is for paymentdoc and instituteid which will be require in both new and existing visitor

    if (empty($_FILES['paymentdoc']['name'])) {
      $doclink_paymentdoc = null;
    } else {
      $filename_paymentdoc = "doc_" . $visitId . "_" . time();
      $parent_paymentdoc = '1f_UvwDaxvloRyYgNs9rjl6ZGW8nUB8RwXHQtQ3RsA9W6SaYY-7xLNn0kXvGV8A9fAjJ6x9yZ';
      $doclink_paymentdoc = uploadeToDrive($paymentdoc, $parent_paymentdoc, $filename_paymentdoc);
    }
    if (empty($_FILES['instituteid']['name'])) {
      $doclink_instituteid = null;
    } else {
      $filename_instituteid = "doc_" . $visitId . "_" . time();
      $parent_instituteid = '1OFTSdUFZm1RVYNWaux1jv_EW5iftuKh_RSLHXkdbqLacK9nziY-Vx4KrUrJoiNIvohQPSzi3';
      $doclink_instituteid = uploadeToDrive($instituteid, $parent_instituteid, $filename_instituteid);
    }

    if ($_POST['visitorType'] === "existing") {
      $visitorQuery = "INSERT INTO visitor_visitdata (visitid, tel, visitbranch, visitstartdatetime, visitenddate, visitpurpose, institutename, enrollmentnumber, instituteid, mentoremail, paymentdoc, declaration, timestamp, other_reason, additional_services) 
                          VALUES ('$visitId', '$tel', '$visitbranch', '$visitstartdatetime', '$visitenddate', '$visitpurpose', '$institutename', '$enrollmentnumber', '$doclink_instituteid', '$mentoremail', '$doclink_paymentdoc', '$declaration', '$timestamp', '$other_reason', '$additional_services')";
      $resultUserdata = pg_query($con, $visitorQuery);

      if ($resultUserdata) {
        $cmdtuples = pg_affected_rows($resultUserdata);
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionErrorVisit($con, "Visitor insertion", $tel);
      }
    } elseif ($_POST['visitorType'] === "new") {
      $fullName = $_POST['fullName'];
      $email = $_POST['email'];
      $contactNumberNew = $_POST['contactNumberNew'];
      $idType = $_POST['idType'];
      $idNumber = $_POST['idNumber'];
      $governmentId = $_FILES['governmentId'];
      $photo = $_FILES['photo'];

      // This is for governmentId and photo which will be require for new visitor

      if (empty($_FILES['governmentId']['name'])) {
        $doclink_governmentId = null;
      } else {
        $filename_governmentId = "doc_" . $fullName . "_" . time();
        $parent_governmentId = '1sdu_dkdrRezOr6IdRMJOknFOSovz1qGP2zRg9Db5IyLIMtVxwWgy-Io8aV36B4uTx9-Gwg3W';
        $doclink_governmentId = uploadeToDrive($governmentId, $parent_governmentId, $filename_governmentId);
      }
      if (empty($_FILES['photo']['name'])) {
        $doclink_photo = null;
      } else {
        $filename_photo = "doc_" . $fullName . "_" . time();
        $parent_photo = '1bgjv3Ei5Go073xcZa7sXKjlOFSTHdoqo8Ffdba_ICNFdcq4ashhxTHGEr0rbX3KdH3CjDbwH';
        $doclink_photo = uploadeToDrive($photo, $parent_photo, $filename_photo);
      }

      // Insert userdata
      $userdataQuery = "INSERT INTO visitor_userdata (fullname, email, tel, id_type, id_number, nationalid, photo) 
                          VALUES ('$fullName', '$email', '$contactNumberNew', '$idType', '$idNumber', '$doclink_governmentId', '$doclink_photo')";
      @$resultUserdata = pg_query($con, $userdataQuery);

      if ($resultUserdata) {
        // Insert visit
        $visitQuery = "INSERT INTO visitor_visitdata (visitid, tel, visitbranch, visitstartdatetime, visitenddate, visitpurpose, institutename, enrollmentnumber, instituteid, mentoremail, paymentdoc, declaration, timestamp, other_reason, additional_services) 
                              VALUES ('$visitId', '$contactNumberNew', '$visitbranch', '$visitstartdatetime', '$visitenddate', '$visitpurpose', '$institutename', '$enrollmentnumber', '$doclink_instituteid', '$mentoremail', '$doclink_paymentdoc', '$declaration', '$timestamp', '$other_reason', '$additional_services')";
        $resultVisitor = pg_query($con, $visitQuery);

        if ($resultVisitor) {
          $cmdtuples = pg_affected_rows($resultVisitor);
        } else {
          $errorOccurred = true;
          $errorMessage = handleInsertionErrorVisit($con, "Visitor insertion", $contactNumberNew);
        }
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionErrorVisit($con, "Userdata insertion", $contactNumberNew);
      }
    }

    // After successful form submission
    if (!$errorOccurred) {
      // Sending email based on the visitor type
      if ($_POST['visitorType'] === "existing") {
        $emailQuery = "SELECT email, fullname FROM visitor_userdata WHERE tel='$tel'";
      } else if ($_POST['visitorType'] === "new") {
        $emailQuery = "SELECT email, fullname FROM visitor_userdata WHERE tel='$contactNumberNew'";
      }
      $result = pg_query($con, $emailQuery);

      if ($result) {
        $row = pg_fetch_assoc($result);
        $email = $row['email'];
        $name = $row['fullname'];
      } else {
        // Handle error if the query fails
        $email = null;
        $name = null;
      }

      if (($_POST['visitorType'] === "existing" || $_POST['visitorType'] === "new") && $email != "") {
        sendEmail("visit_request_ack", array(
          "fullname" => $name,
          "visitId" => $visitId,
          "timestamp" => date("d/m/Y h:i A", strtotime($timestamp)),
          "tel" => @$tel . @$contactNumberNew,
          "email" => $email,
          "visitstartdatetime" => date("d/m/Y h:i A", strtotime($visitstartdatetime)),
          "visitenddate" => $visitenddate,
          "visitpurpose" => $visitpurpose
        ), $email, true);
      }
    }

    // Prepare the API response data
    $responseData = array(
      'error' => $errorOccurred,
      'errorMessage' => $errorOccurred ? $errorMessage : '', // Return the actual error message if an error occurred
      'cmdtuples' => $cmdtuples,
      'visitorId' => $visitId
    );

    // Return the response as JSON
    echo json_encode($responseData);
    exit; // Stop further PHP execution
  }
}

function handleInsertionErrorVisit($connection, $errorMessage, $tel)
{
  $error = pg_last_error($connection);

  // Check for duplicate key violation error
  if (strpos($error, 'duplicate key value violates unique constraint') !== false) {
    return "already_registered"; // Return a specific code for duplicate key violation error
    // Additional handling specific to duplicate key violation error can be done here
  } else {
    // Log or display the generic error message
    error_log($errorMessage . " error: " . $error);
    return "generic_error"; // Return a specific code for generic error
  }
}

if ($formtype == "visitreviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$visitid = $_POST['visitid'];
  @$visitbranch = $_POST['visitbranch'];
  @$visitstartdatetime = $_POST['visitstartdatetime'];
  @$visitenddate = $_POST['visitenddate'];
  @$visitstatus = $_POST['visitstatus'];
  @$hrremarks = htmlspecialchars($_POST['hrremarks'], ENT_QUOTES, 'UTF-8');
  $now = date('Y-m-d H:i:s');

  $visitapproval = "UPDATE visitor_visitdata SET  visitstatusupdatedby = '$reviewer_id', visitstatusupdatedon = '$now', visitstatus = '$visitstatus',remarks = '$hrremarks', visitbranch = '$visitbranch', visitstartdatetime = '$visitstartdatetime', visitenddate = '$visitenddate' WHERE visitid = '$visitid'";
  $result = pg_query($con, $visitapproval);
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

// Ensure this is included in your script where the POST request is handled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form-type'] == "email_verify_signup") {

  $email = strtolower($_POST['email_verify']);

  $query = "SELECT COUNT(*) AS count FROM signup WHERE email = $1 AND is_active = true";
  $result = pg_query_params($con, $query, [$email]);

  if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode(['exists' => $row['count'] > 0]);
  } else {
    echo json_encode(['exists' => false]);
  }
  exit; // Ensure no further output is sent
}

if (@$_POST['form-type'] == "signup") {
  $applicant_name = pg_escape_string($con, trim($_POST['applicant-name']));
  $date_of_birth = $_POST['date-of-birth'];
  $gender = $_POST['gender'];
  $telephone = $_POST['telephone'];
  $email = $_POST['email'];
  $branch = $_POST['branch'];
  $association = $_POST['association'];
  $job_select = $_POST['job-select'];
  $purpose = pg_escape_string($con, trim($_POST['purpose']));
  $interests = $_POST['interests'];
  $post_select = $_POST['post-select'];
  $membership_purpose = pg_escape_string($con, trim($_POST['membershipPurpose']));
  $subject1 = $_POST['subject1'];
  $subject2 = $_POST['subject2'];
  $subject3 = $_POST['subject3'];
  $heard_about = $_POST['heard_about'];
  $postal_address = pg_escape_string($con, trim($_POST['postal-address']));
  $permanent_address = pg_escape_string($con, trim($_POST['permanent-address']));
  $education_qualification = $_POST['education-qualification'];
  $specialization = pg_escape_string($con, trim($_POST['specialization']));
  $work_experience = pg_escape_string($con, trim($_POST['work-experience']));
  $consent = !empty($_POST['consent']) ? 1 : 0;
  $college_name = $_POST['college_name'];
  $enrollment_number = $_POST['enrollment_number'];
  // SQL query to generate the application number
  $sql = "
    SELECT CONCAT(
    RIGHT(EXTRACT(YEAR FROM CURRENT_DATE)::text, 2),          -- Year (2 digits)
    LPAD(EXTRACT(MONTH FROM CURRENT_DATE)::text, 2, '0'),     -- Month (2 digits)
    (SELECT COUNT(application_number) + 1 FROM signup)::text, -- Dynamic length for application count
    LPAD(
        FLOOR(RANDOM() * POWER(10, GREATEST(0, 12 - (2 + 2 + LENGTH((SELECT COUNT(application_number) + 1 FROM signup)::text)))))::text,
        GREATEST(0, 12 - (2 + 2 + LENGTH((SELECT COUNT(application_number) + 1 FROM signup)::text))), 
        '0'
    )
  ) AS application_number;
  ";

  // Execute the query
  $result = pg_query($con, $sql);

  if ($result) {
    $row = pg_fetch_assoc($result); // Use pg_fetch_assoc to fetch a single row
    $application_number = $row['application_number'];
  }
  $timestamp = date('Y-m-d H:i:s');
  $uploadedFile_payment = $_FILES['payment-photo'];
  $uploadedFile_photo = $_FILES['applicant-photo'];
  $uploadedFile_resume = $_FILES['resume-upload'];
  $duration = $_POST['duration'] ?? null;
  $availability = !empty($_POST['availability']) ? implode(",", $_POST['availability']) : null;
  $medium = !empty($_POST['medium']) ? implode(",", $_POST['medium']) : null;

  $randomPassword = bin2hex(random_bytes(3)); // Generates a random 6-character alphanumeric password
  $newpass_hash = password_hash($randomPassword, PASSWORD_DEFAULT);

  if (empty($_FILES['payment-photo']['name'])) {
    $doclink_payment_photo = null;
  } else {
    $filename_payment_photo = "payment_" . "$application_number" . "_" . time();
    $parent_payment_photo = '1_XqHbekgxQSSwjScG8V8-lL-3l_Spx52';
    $doclink_payment_photo = uploadeToDrive($uploadedFile_payment, $parent_payment_photo, $filename_payment_photo);
  }

  if (empty($_FILES['applicant-photo']['name'])) {
    $doclink_applicant_photo = null;
  } else {
    $filename_applicant_photo = "photo_" . "$application_number" . "_" . time();
    $parent_applicant_photo = '1gv6JnDX5QTzlcZV-CekoherLdKeriH-A';
    $doclink_applicant_photo = uploadeToDrive($uploadedFile_photo, $parent_applicant_photo, $filename_applicant_photo);
  }

  if (empty($_FILES['resume-upload']['name'])) {
    $doclink_resume_photo = null;
  } else {
    $filename_resume_photo = "resume_" . "$application_number" . "_" . time();
    $parent_resume_photo = '1wxt6Q2lIvgWyP0fzMx8cjY5iVImmoxVA';
    $doclink_resume_photo = uploadeToDrive($uploadedFile_resume, $parent_resume_photo, $filename_resume_photo);
  }


  // Build the SQL query
  $columns = "applicant_name, date_of_birth, gender, telephone, email, branch, association, job_select, purpose, interests, post_select, membership_purpose, payment_photo, applicant_photo, resume_upload, heard_about, consent, timestamp, application_number, subject1, subject2, subject3, password,default_pass_updated_on,postal_address,permanent_address,education_qualification,specialization,work_experience,application_status,college_name,enrollment_number";
  $values = "'$applicant_name', '$date_of_birth', '$gender', '$telephone', '$email', '$branch', '$association', '$job_select', '$purpose', '$interests', '$post_select', '$membership_purpose', '$doclink_payment_photo', '$doclink_applicant_photo','$doclink_resume_photo','$heard_about', '$consent','$timestamp','$application_number','$subject1','$subject2','$subject3','$newpass_hash','$timestamp','$postal_address', '$permanent_address', '$education_qualification', '$specialization', '$work_experience','Application Submitted', '$college_name','$enrollment_number'";

  // Conditionally add duration to columns and values
  if ($duration != null) {
    $columns .= ", duration";
    $values .= ", $duration";
  }
  if ($availability != null) {
    $columns .= ", availability";
    $values .= ", '$availability'";
  }
  if ($medium != null) {
    $columns .= ", medium";
    $values .= ", '$medium'";
  }


  // Build the full query
  $signup = "INSERT INTO signup ($columns) VALUES ($values)";

  // Execute the query
  $result = pg_query($con, $signup);
  $cmdtuples = pg_affected_rows($result);

  // Check if the query was successful
  if ($result) {
    if ($cmdtuples == 1) {
      echo json_encode(array("error" => false, "application_number" => $application_number));
      if ($email != "") {
        // Adjust the parameters for your sendEmail function accordingly
        sendEmail("signup_success", array(
          "applicant_name" => $applicant_name,
          "branch" => $branch,
          "association" => $association,
          "application_number" => $application_number,
          "email" => $email,
          "randomPassword" => $randomPassword,
          "timestamp" => date("d/m/Y g:i a", strtotime($timestamp))
        ), $email);
      }
    }
  } else {
    // Query failed
    echo json_encode(array("error" => true, "errorMessage" => "Error occurred during form submission."));
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
