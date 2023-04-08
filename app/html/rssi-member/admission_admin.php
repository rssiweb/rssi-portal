<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
// Retrieve student ID from form input
@$student_id = $_GET['student_id'];
// Query database for student information based on ID

$result = pg_query($con, "SELECT * FROM student WHERE student_id = '$student_id'");
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>
<?php
if (@$_POST['form-type'] == "admission_admin") {
    // Get the form data
    $type_of_admission = $_POST['type-of-admission'];
    $student_name = $_POST['student-name'];
    $date_of_birth = $_POST['date-of-birth'];
    $gender = $_POST['gender'];
    $doclink_student_photo = $_POST['student-photo'];
    $aadhar_available = $_POST['aadhar-card'];
    $aadhar_card = $_POST['aadhar-number'];
    $doclink_aadhar_card = $_POST['aadhar-card-upload'];
    $guardian_name = $_POST['guardian-name'];
    $guardian_relation = $_POST['relation'];
    $guardian_aadhar = $_POST['guardian-aadhar-number'];
    $state_of_domicile = $_POST['state'];
    $postal_address = $_POST['postal-address'];
    $telephone_number = $_POST['telephone'];
    $email_address = $_POST['email'];
    $preferred_branch = $_POST['branch'];
    $class = $_POST['class'];
    $school_admission_required = $_POST['school-required'];
    $school_name = $_POST['school-name'];
    $board_name = $_POST['board-name'];
    $medium = $_POST['medium'];
    $family_monthly_income = $_POST['income'];
    $total_family_members = $_POST['family-members'];
    $payment_mode = $_POST['payment-mode'];
    $c_authentication_code = $_POST['c-authentication-code'];
    $transaction_id = $_POST['transaction-id'];
    $subject_select = $_POST['subject-select'];

    $module = $_POST['module'];
    $category = $_POST['category'];
    $photo_url = $_POST['photo-url'];
    $id_card_issued = $_POST['id-card-issued'];
    $status = $_POST['status'];

    if (!empty($_POST['effectivefrom'])) {
        $effective_from = $_POST['effectivefrom'];
        $effective_from_str = "effective_from='$effective_from'";
    } else {
        $effective_from_str = "effective_from=null";
    }

    $remarks = $_POST['remarks'];
    $scode = $_POST['scode'];
    $updated_by = $_POST['updatedby'];
    $student_id = $_POST['student-id'];
    @$timestamp = date('Y-m-d H:i:s');

    @$student_update = "UPDATE student SET type_of_admission='$type_of_admission', student_name='$student_name', date_of_birth='$date_of_birth', gender='$gender', student_photo='$doclink_student_photo', aadhar_available='$aadhar_available', student_aadhar='$aadhar_card', aadhar_card='$doclink_aadhar_card', guardian_name='$guardian_name', guardian_relation='$guardian_relation', guardian_aadhar='$guardian_aadhar', state_of_domicile='$state_of_domicile', postal_address='$postal_address', telephone_number='$telephone_number', email_address='$email_address', preferred_branch='$preferred_branch', class='$class', school_admission_required='$school_admission_required', school_name='$school_name', board_name='$board_name', medium='$medium', family_monthly_income='$family_monthly_income', total_family_members='$total_family_members', payment_mode='$payment_mode', c_authentication_code='$c_authentication_code', transaction_id='$transaction_id', student_id='$student_id', subject_select='$subject_select', module='$module', category='$category', photo_url='$photo_url', id_card_issued='$id_card_issued', status='$status', remarks='$remarks', $effective_from_str, scode='$scode', updated_by='$updated_by', updated_on='$timestamp' WHERE student_id = '$student_id'";
    $resultt = pg_query($con, $student_update);
    $cmdtuples = pg_affected_rows($resultt);
}
?>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Admin Update Admission Form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!------ Include the above in your HEAD tag ---------->
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>

    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <style>
        .prebanner {
            display: none;
        }
    </style>

</head>

<body>
    <?php if (@$type_of_admission != null && @$cmdtuples == 0) { ?>

        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <span class="blink_me"><i class="fa-solid fa-xmark"></i></span>&nbsp;&nbsp;<span>Error: We encountered an error while updating the record. Please try again.</span>
        </div>
    <?php } else if (@$cmdtuples == 1) { ?>

        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <span class="blink_me"><i class="fa-solid fa-xmark"></i></i></span>&nbsp;&nbsp;<span>Your changes have been saved successfully.</span>
        </div>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
    <?php } ?>
    <div class="container mt-5">

        <form method="GET" action="">
            <div class="form-group">
                <label>Enter Student ID:</label>
                <input type="text" name="student_id" value="<?php echo @$_GET['student_id']; ?>" required>
                <input type="submit" name="submit" value="Search"> <button type='button'>Lock / Unlock Form</button>
            </div>
        </form>
        <br>
        <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">RSSI NGO Admission form</h2>


        <p>Unique Id: WB/2021/0282726 (NGO Darpan, NITI Aayog, Government of India)</p>
        <p>The admission fee is ₹100. The admission fee is one-time, non-refundable, and has to be paid at the time of admission.</p>
        <hr>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            foreach ($resultArr as $array) {
            ?>
                <form name="admission_admin" id="admission_admin" action="admission_admin.php" method="post" enctype="multipart/form-data">
                <button type="submit" id="submitBtn" class="btn btn-danger">Update</button>
                    <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Last updated on <?php echo $array['updated_on'] ?> by <?php echo $array['updated_by'] ?></p>

                    <fieldset>


                        <input type="hidden" name="form-type" value="admission_admin">
                        <div class="form-group">
                            <label for="student-id">Student ID:</label>
                            <input type="text" class="form-control" id="student-id" name="student-id" placeholder="Enter student ID" value="<?php echo $array['student_id'] ?>" required readonly>
                            <small id="student-id-help" class="form-text text-muted"></small>
                        </div>
                        <div class="form-group">
                            <label for="type-of-admission">Type of Admission:</label>
                            <select class="form-control" id="type-of-admission" name="type-of-admission" required>
                                <?php if ($array['type_of_admission'] == null) { ?>
                                    <option value="" selected>--Select Type of Admission--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Type of Admission--</option>
                                    <option hidden selected><?php echo $array['type_of_admission'] ?></option>
                                <?php }
                                ?>
                                <option value="New Admission">New Admission</option>
                                <option value="Transfer Admission">Transfer Admission</option>
                            </select>
                            <small id="type-of-admission-help" class="form-text text-muted">Please select the type of admission you are applying for.</small>
                        </div>
                        <div class="form-group">
                            <label for="student-name">Student Name:</label>
                            <input type="text" class="form-control" id="student-name" name="student-name" placeholder="Enter student name" value="<?php echo $array['student_name'] ?>" required>
                            <small id="student-name-help" class="form-text text-muted">Please enter the name of the student.</small>
                        </div>
                        <div class="form-group">
                            <label for="date-of-birth">Date of Birth:</label>
                            <input type="date" class="form-control" id="date-of-birth" name="date-of-birth" value="<?php echo $array['date_of_birth'] ?>" required>
                            <small id="date-of-birth-help" class="form-text text-muted">Please enter the date of birth of the student.</small>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <?php if ($array['gender'] == null) { ?>
                                    <option value="" selected>--Select Gender--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Gender--</option>
                                    <option hidden selected><?php echo $array['gender'] ?></option>
                                <?php }
                                ?>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Binary">Binary</option>
                            </select>
                            <small id="gender-help" class="form-text text-muted">Please select the gender of the student.</small>
                        </div>

                        <!-- <input type="file" id="photo-upload" name="photo" accept="image/*" capture> -->
                        <div class="form-group">
                            <label for="student-photo">Upload Student Photo:</label>
                            <input type="text" class="form-control" id="student-photo" name="student-photo" value="<?php echo $array['student_photo'] ?>" required>
                            <small id="student-photo-help" class="form-text text-muted">Please upload a recent passport size photograph of the student.</small>
                        </div>

                        <div class="form-group">
                            <label for="aadhar-card">Aadhar Card Available?:</label>
                            <select class="form-control" id="aadhar-card" name="aadhar-card" required>
                                <?php if ($array['aadhar_available'] == null) { ?>
                                    <option value="" selected>--Select--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select--</option>
                                    <option hidden selected><?php echo $array['aadhar_available'] ?></option>
                                <?php }
                                ?>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                            <small id="aadhar-card-help" class="form-text text-muted">Please select whether you have an Aadhar card or not.</small>
                        </div>


                        <div id="hidden-panel">
                            <div class="form-group">
                                <label for="aadhar-number">Aadhar of the Student:</label>
                                <input type="text" class="form-control" id="aadhar-number" name="aadhar-number" placeholder="Enter Aadhar number" value="<?php echo $array['student_aadhar'] ?>">
                                <small id="aadhar-number-help" class="form-text text-muted">Please enter the Aadhar number of the student.</small>
                            </div>
                            <div class="form-group">
                                <label for="aadhar-card-upload">Upload Aadhar Card:</label>
                                <input type="text" class="form-control" id="aadhar-card-upload" name="aadhar-card-upload" value="<?php echo $array['aadhar_card'] ?>">
                                <small id="aadhar-card-upload-help" class="form-text text-muted">Please upload a scanned copy of the Aadhar card (if available).</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="guardian-name">Guardian's Name:</label>
                            <input type="text" class="form-control" id="guardian-name" name="guardian-name" placeholder="Enter guardian name" value="<?php echo $array['guardian_name'] ?>" required>
                            <small id="guardian-name-help" class="form-text text-muted">Please enter the name of the student's guardian.</small>
                        </div>

                        <div class="form-group">
                            <label for="relation">Relation with Student:</label>
                            <select class="form-control" id="relation" name="relation" required>
                                <?php if ($array['guardian_relation'] == null) { ?>
                                    <option value="" selected>--Select Type of Relation--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Type of Relation--</option>
                                    <option hidden selected><?php echo $array['guardian_relation'] ?></option>
                                <?php }
                                ?>
                                <option value="Mother">Mother</option>
                                <option value="Father">Father</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Other">Other</option>
                            </select>
                            <small id="relation-help" class="form-text text-muted">Please enter the relation of the guardian with the student.</small>
                        </div>
                        <div id="hidden-panel-guardian-aadhar">
                            <div class="form-group">
                                <label for="guardian-aadhar-number">Aadhar of Guardian:</label>
                                <input type="text" class="form-control" id="guardian-aadhar-number" name="guardian-aadhar-number" placeholder="Enter Aadhar number" value="<?php echo $array['guardian_aadhar'] ?>">
                                <small id="guardian-aadhar-number-help" class="form-text text-muted">Please enter the Aadhar number of the guardian.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="state">State of Domicile:</label>
                            <select class="form-control" id="state" name="state" required>
                                <?php if ($array['state_of_domicile'] == null) { ?>
                                    <option value="" selected>--Select State--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select State--</option>
                                    <option hidden selected><?php echo $array['state_of_domicile'] ?></option>
                                <?php }
                                ?>

                                <option value="Andhra Pradesh">Andhra Pradesh</option>
                                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                <option value="Assam">Assam</option>
                                <option value="Bihar">Bihar</option>
                                <option value="Chhattisgarh">Chhattisgarh</option>
                                <option value="Goa">Goa</option>
                                <option value="Gujarat">Gujarat</option>
                                <option value="Haryana">Haryana</option>
                                <option value="Himachal Pradesh">Himachal Pradesh</option>
                                <option value="Jammu Kashmir">Jammu and Kashmir</option>
                                <option value="Jharkhand">Jharkhand</option>
                                <option value="Karnataka">Karnataka</option>
                                <option value="Kerala">Kerala</option>
                                <option value="Madhya Pradesh">Madhya Pradesh</option>
                                <option value="Maharashtra">Maharashtra</option>
                                <option value="Manipur">Manipur</option>
                                <option value="Meghalaya">Meghalaya</option>
                                <option value="Mizoram">Mizoram</option>
                                <option value="Nagaland">Nagaland</option>
                                <option value="Odisha">Odisha</option>
                                <option value="Punjab">Punjab</option>
                                <option value="Rajasthan">Rajasthan</option>
                                <option value="Sikkim">Sikkim</option>
                                <option value="Tamil Nadu">Tamil Nadu</option>
                                <option value="Telangana">Telangana</option>
                                <option value="Tripura">Tripura</option>
                                <option value="Uttar Pradesh">Uttar Pradesh</option>
                                <option value="Uttarakhand">Uttarakhand</option>
                                <option value="West Bengal">West Bengal</option>
                            </select>
                            <small id="state-help" class="form-text text-muted">Please select the state where the student resides.</small>
                        </div>
                        <div class="form-group">
                            <label for="postal-address">Postal Address:</label>
                            <textarea class="form-control" id="postal-address" name="postal-address" rows="3" placeholder="Enter postal address" required><?php echo $array['postal_address'] ?></textarea>
                            <small id="postal-address-help" class="form-text text-muted">Please enter the complete postal address of the student.</small>
                        </div>
                        <div class="form-group">
                            <label for="telephone">Telephone Number:</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Enter telephone number" value="<?php echo $array['telephone_number'] ?>" required>
                            <small id="telephone-help" class="form-text text-muted">Please enter a valid telephone number.</small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" value="<?php echo $array['email_address'] ?>">
                            <small id="email-help" class="form-text text-muted">Please enter a valid email address.</small>
                        </div>
                        <div class="form-group">
                            <label for="branch">Preferred Branch:</label>
                            <select class="form-control" id="branch" name="branch" required>
                                <?php if ($array['preferred_branch'] == null) { ?>
                                    <option value="" selected>--Select Branch--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Branch--</option>
                                    <option hidden selected><?php echo $array['preferred_branch'] ?></option>
                                <?php }
                                ?>
                                <option value="Lucknow">Lucknow</option>
                                <option value="West Bengal">West Bengal</option>
                            </select>
                            <small id="branch-help" class="form-text text-muted">Please select the preferred branch of study.</small>
                        </div>

                        <div class="form-group">
                            <label for="class">Class:</label>
                            <select class="form-control" id="class" name="class" required>
                                <?php if ($array['class'] == null) { ?>
                                    <option value="" selected>--Select Class--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Class--</option>
                                    <option hidden selected><?php echo $array['class'] ?></option>
                                <?php }
                                ?>
                                <option value="Pre-school">Pre-school</option>
                                <option value="1">Class 1</option>
                                <option value="2">Class 2</option>
                                <option value="3">Class 3</option>
                                <option value="4">Class 4</option>
                                <option value="5">Class 5</option>
                                <option value="6">Class 6</option>
                                <option value="7">Class 7</option>
                                <option value="8">Class 8</option>
                                <option value="9">Class 9</option>
                                <option value="10">Class 10</option>
                                <option value="11">Class 11</option>
                                <option value="12">Class 12</option>
                                <option value="Vocational training">Vocational training</option>
                            </select>
                            <small id="class-help" class="form-text text-muted">Please select the class the student wants to join.</small>
                        </div>

                        <div class="form-group">
                            <label for="subject-select">Select subject(s): </label>
                            <select class="form-control" id="subject-select" name="subject-select" required>
                                <?php if ($array['subject_select'] == null) { ?>
                                    <option value="" selected>--Select Subject--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Subject--</option>
                                    <option hidden selected><?php echo $array['subject_select'] ?></option>
                                <?php }
                                ?>
                                <option value="ALL Subjects">ALL Subjects</option>
                                <option value="English">English</option>
                                <option value="Embroidery">Embroidery</option>
                            </select>
                            <small class="form-text text-muted">Please select the subject(s) that you want to study from the drop-down list.</small>
                        </div>

                        <div class="form-group" id="school-required-group">
                            <label for="school-required">School Admission Required:</label>
                            <select class="form-control" id="school-required" name="school-required">
                                <?php if ($array['school_admission_required'] == null) { ?>
                                    <option value="" selected>--Select--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select--</option>
                                    <option hidden selected><?php echo $array['school_admission_required'] ?></option>
                                <?php }
                                ?>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                            <small id="school-required-help" class="form-text text-muted">Do you require admission in a new school?</small>
                        </div>


                        <div id="school-details">
                            <div class="form-group">
                                <label for="school-name">Name Of The School:</label>
                                <input type="text" class="form-control" id="school-name" name="school-name" placeholder="Enter name of the school" value="<?php echo $array['school_name'] ?>">
                                <small id="school-name-help" class="form-text text-muted">Please enter the name of the current school or the new school you want to join.</small>
                            </div>

                            <div class="form-group">
                                <label for="board-name">Name Of The Board:</label>
                                <select class="form-control" id="board-name" name="board-name">
                                    <?php if ($array['board_name'] == null) { ?>
                                        <option value="" selected>--Select--</option>
                                    <?php
                                    } else { ?>
                                        <option value="" selected>--Select--</option>
                                        <option hidden selected><?php echo $array['board_name'] ?></option>
                                    <?php }
                                    ?>
                                    <option value="CBSE">CBSE</option>
                                    <option value="ICSE">ICSE</option>
                                    <option value="ISC">ISC</option>
                                    <option value="State Board">State Board</option>
                                </select>
                                <small id="board-name-help" class="form-text text-muted">Please enter the name of the board of education.</small>
                            </div>

                            <div class="form-group">
                                <label for="medium">Medium:</label>
                                <select class="form-control" id="medium" name="medium">
                                    <?php if ($array['medium'] == null) { ?>
                                        <option value="" selected>--Select--</option>
                                    <?php
                                    } else { ?>
                                        <option value="" selected>--Select--</option>
                                        <option hidden selected><?php echo $array['medium'] ?></option>
                                    <?php }
                                    ?>
                                    <option value="English">English</option>
                                    <option value="Hindi">Hindi</option>
                                    <option value="Other">Other</option>
                                </select>
                                <small id="medium-help" class="form-text text-muted">Please select the medium of instruction.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="income">Family Monthly Income</label>
                            <input type="number" class="form-control" id="income" name="income" value="<?php echo $array['family_monthly_income'] ?>">
                            <small id="income-help" class="form-text text-muted">Please enter the total monthly income of the student's family.</small>
                        </div>
                        <div class="form-group">
                            <label for="family-members">Total Number of Family Members</label>
                            <input type="number" class="form-control" id="family-members" name="family-members" value="<?php echo $array['total_family_members'] ?>">
                            <small id="family-members-help" class="form-text text-muted">Please enter the total number of members in the student's family.</small>
                        </div>
                        <div class="form-group">
                            <label for="payment-mode">Payment Mode:</label>
                            <select class="form-control" id="payment-mode" name="payment-mode" required>
                                <?php if ($array['payment_mode'] == null) { ?>
                                    <option value="" selected>--Select--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select--</option>
                                    <option hidden selected><?php echo $array['payment_mode'] ?></option>
                                <?php }
                                ?>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                            <small id="payment-mode-help" class="form-text text-muted">Please select the payment mode for the admission fee.</small>
                        </div>

                        <div class="form-group" id="cash-authentication-code">
                            <label for="c-authentication-code">C-Authentication Code:</label>
                            <input type="text" class="form-control" id="c-authentication-code" name="c-authentication-code" placeholder="Enter C-Authentication code" value="<?php echo $array['c_authentication_code'] ?>">
                            <small id="c-authentication-code-help" class="form-text text-muted">Please enter the C-Authentication code if you are paying by cash.</small>
                        </div>

                        <div class="form-group" id="online-declaration">

                            <p>Click <a href="https://paytm.me/7Nu-Znk" target="_blank">https://paytm.me/7Nu-Znk</a> to complete the payment. Please note that if you submit the form without completing the payment or with an incorrect transaction ID, your application will be placed on hold for the next two business days. After this period, if the admission fee has not been received, your submission will be cancelled.</p>

                            <div class="form-group">
                                <label for="transaction-id">Transaction ID:</label>
                                <input type="text" class="form-control" id="transaction-id" name="transaction-id" value="<?php echo $array['transaction_id'] ?>">
                                <small id="online-declaration-help" class="form-text text-muted">Please enter the transaction ID if you have paid the admission fee online.</small>
                            </div>
                        </div>
                        <hr>
                        <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">Admin Part</h2>
                        <div class="form-group">
                            <label for="module">Module</label>
                            <select class="form-control" id="module" name="module" required>
                                <?php if ($array['module'] == null) { ?>
                                    <option value="" selected>--Select Module--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Module--</option>
                                    <option hidden selected><?php echo $array['module'] ?></option>
                                <?php }
                                ?>
                                <option value="National">National</option>
                                <option value="State">State</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <?php if ($array['category'] == null) { ?>
                                    <option value="" selected>--Select Category--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Category--</option>
                                    <option hidden selected><?php echo $array['category'] ?></option>
                                <?php }
                                ?>
                                <option value="">Select Category</option>
                                <option value="LG2-A">LG2-A</option>
                                <option value="LG2-B">LG2-B</option>
                                <option value="LG2-C">LG2-C</option>
                                <option value="LG3">LG3</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" class="form-control" id="age" name="age" placeholder="Enter Age" value="<?php $today = new DateTime();
                                                                                                                            echo $today->diff(new DateTime($array['date_of_birth']))->y ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="photo-url">Photo URL</label>
                            <input type="url" class="form-control" id="photo-url" name="photo-url" placeholder="Enter Photo URL" value="<?php echo $array['photo_url'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="id-card-issued">ID Card Issued</label>
                            <select class="form-control" id="id-card-issued" name="id-card-issued" required>
                                <?php if ($array['id_card_issued'] == null) { ?>
                                    <option value="" selected>--Select Option--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Option--</option>
                                    <option hidden selected><?php echo $array['id_card_issued'] ?></option>
                                <?php }
                                ?>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <?php if ($array['status'] == null) { ?>
                                    <option value="" selected>--Select Option--</option>
                                <?php
                                } else { ?>
                                    <option value="" selected>--Select Option--</option>
                                    <option hidden selected><?php echo $array['status'] ?></option>
                                <?php }
                                ?>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="effectivefrom">Effective From</label>
                            <input type="date" class="form-control" id="effectivefrom" name="effectivefrom" value="<?php echo $array['effective_from'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo $array['remarks'] ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="scode">Scode</label>
                            <input type="text" class="form-control" id="scode" name="scode" placeholder="Enter Scode" value="<?php echo $array['scode'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="updatedby">Updated By</label>
                            <input type="text" class="form-control" id="updatedby" name="updatedby" placeholder="Enter Exit Interview" value="<?php echo $associatenumber ?>" readonly>
                        </div>
                        <button type="submit" id="submitBtn" class="btn btn-danger">Update</button>
                        <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Last updated on <?php echo $array['updated_on'] ?> by <?php echo $array['updated_by'] ?></p>
                </form>
                </fieldset>
            <?php } ?>
        <?php
        } else if ($student_id == null) {
        ?>
            <p>Please enter the Student ID.</p>
        <?php
        } else {
        ?>
            <p>We could not find any records matching the entered Student ID.</p>
        <?php } ?>

    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('input[required], select[required], textarea[required]').each(function() {
                $(this).closest('.form-group').find('label').append(' <span style="color: red">*</span>');
            });
        });
    </script>
    <script>
        var form = document.getElementById('admission_admin'), // select form by ID
            btn1 = document.querySelectorAll('button')[0];

        btn1.addEventListener('click', lockForm);

        function lockForm() {
            if (form.classList.toggle('locked')) {
                // Form is now locked
                btn1.textContent = 'Unlock Form';
                [].slice.call(form.elements).forEach(function(item) {
                    item.disabled = true;
                });
            } else {
                // Form is now unlocked
                btn1.textContent = 'Lock Form';
                [].slice.call(form.elements).forEach(function(item) {
                    item.disabled = false;
                });
            }
        }
        // Lock the form when the page is loaded
        lockForm();
    </script>

</body>

</html>