<?php
require_once __DIR__ . '/../bootstrap.php';

include(__DIR__ . "/../util/login_util.php");
?>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI NGO Admission form</title>
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
    <div class="container mt-5">
        <!-- <h2 class="text-center mb-4">RSSI Shiksha Admission form</h2> -->
        <!-- <h2 class="text-center mb-4" style="color: #CE1212; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; text-shadow: 1px 1px 0px #FFFFFF;">RSSI Shiksha Admission Form</h2> -->
        <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">RSSI NGO Admission form</h2>


        <p>Unique Id: WB/2021/0282726 (NGO Darpan, NITI Aayog, Government of India)</p>
        <p>The admission fee is ₹100. The admission fee is one-time, non-refundable, and has to be paid at the time of admission.</p>
        <hr>
        <form action="submit-form.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="type-of-admission">Type of Admission:<span style="color: red">*</span></label>
                <select class="form-control" id="type-of-admission" name="type-of-admission" required>
                    <option value="">--Select Type of Admission--</option>
                    <option value="New Admission">New Admission</option>
                    <option value="Transfer Admission">Transfer Admission</option>
                </select>
                <small id="type-of-admission-help" class="form-text text-muted">Please select the type of admission you are applying for.</small>
            </div>
            <div class="form-group">
                <label for="student-name">Student Name:<span style="color: red">*</span></label>
                <input type="text" class="form-control" id="student-name" name="student-name" placeholder="Enter student name" required>
                <small id="student-name-help" class="form-text text-muted">Please enter the name of the student.</small>
            </div>
            <div class="form-group">
                <label for="date-of-birth">Date of Birth:<span style="color: red">*</span></label>
                <input type="date" class="form-control" id="date-of-birth" name="date-of-birth" required>
                <small id="date-of-birth-help" class="form-text text-muted">Please enter the date of birth of the student.</small>
            </div>
            <div class="form-group">
                <label for="gender">Gender:<span style="color: red">*</span></label>
                <select class="form-control" id="gender" name="gender">
                    <option value="">--Select Gender--</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Binary">Binary</option>
                </select>
                <small id="gender-help" class="form-text text-muted">Please select the gender of the student.</small>
            </div>
            <div class="form-group">
                <label for="student-photo">Upload Student Photo:<span style="color: red">*</span></label>
                <input type="file" class="form-control-file" id="student-photo" name="student-photo" required>
                <small id="student-photo-help" class="form-text text-muted">Please upload a recent passport size photograph of the student.</small>
            </div>
            <div class="form-group">
                <label for="aadhar-card">Aadhar Card Available?:<span style="color: red">*</span></label>
                <select class="form-control" id="aadhar-card" name="aadhar-card" required>
                    <option value="">--Select--</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                <small id="aadhar-card-help" class="form-text text-muted">Please select whether you have an Aadhar card or not.</small>
            </div>


            <div id="hidden-panel" style="display: none;">
                <div class="form-group">
                    <label for="aadhar-number">Aadhar of the Student:<span style="color: red">*</span></label>
                    <input type="text" class="form-control" id="aadhar-number" name="aadhar-number" placeholder="Enter Aadhar number">
                    <small id="aadhar-number-help" class="form-text text-muted">Please enter the Aadhar number of the student.</small>
                </div>
                <div class="form-group">
                    <label for="aadhar-card-upload">Upload Aadhar Card:<span style="color: red">*</span></label>
                    <input type="file" class="form-control-file" id="aadhar-card-upload" name="aadhar-card-upload">
                    <small id="aadhar-card-upload-help" class="form-text text-muted">Please upload a scanned copy of the Aadhar card (if available).</small>
                </div>
            </div>
            <div class="form-group">
                <label for="guardian-name">Guardian's Name:<span style="color: red">*</span></label>
                <input type="text" class="form-control" id="guardian-name" name="guardian-name" placeholder="Enter guardian name" required>
                <small id="guardian-name-help" class="form-text text-muted">Please enter the name of the student's guardian.</small>
            </div>

            <div class="form-group">
                <label for="relation">Relation with Student:<span style="color: red">*</span></label>
                <select class="form-control" id="relation" name="relation" required>
                    <option value="">--Select Type of Relation--</option>
                    <option value="Mother">Mother</option>
                    <option value="Father">Father</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Other">Other</option>
                </select>
                <small id="relation-help" class="form-text text-muted">Please enter the relation of the guardian with the student.</small>
            </div>
            <div id="hidden-panel-guardian-aadhar" style="display: none;">
                <div class="form-group">
                    <label for="guardian-aadhar-number">Aadhar of Guardian:<span style="color: red">*</span></label>
                    <input type="text" class="form-control" id="guardian-aadhar-number" name="guardian-aadhar-number" placeholder="Enter Aadhar number">
                    <small id="guardian-aadhar-number-help" class="form-text text-muted">Please enter the Aadhar number of the guardian.</small>
                </div>
            </div>
            <div class="form-group">
                <label for="state">State of Domicile:<span style="color: red">*</span></label>
                <select class="form-control" id="state" name="state" required>
                    <option value="">--Select State--</option>
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
                <label for="postal-address">Postal Address:<span style="color: red">*</span></label>
                <textarea class="form-control" id="postal-address" name="postal-address" rows="3" placeholder="Enter postal address" required></textarea>
                <small id="postal-address-help" class="form-text text-muted">Please enter the complete postal address of the student.</small>
            </div>
            <div class="form-group">
                <label for="telephone">Telephone Number:<span style="color: red">*</span></label>
                <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Enter telephone number" required>
                <small id="telephone-help" class="form-text text-muted">Please enter a valid telephone number.</small>
            </div>
            <div class="form-group">
                <label for="email">Email Address:<span style="color: red">*</span></label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                <small id="email-help" class="form-text text-muted">Please enter a valid email address.</small>
            </div>
            <div class="form-group">
                <label for="branch">Preferred Branch:<span style="color: red">*</span></label>
                <select class="form-control" id="branch" name="branch" required>
                    <option value="">--Select Branch--</option>
                    <option value="Lucknow">Lucknow</option>
                    <option value="West Bengal">West Bengal</option>
                </select>
                <small id="branch-help" class="form-text text-muted">Please select the preferred branch of study.</small>
            </div>

            <div class="form-group">
                <label for="class">Class:<span style="color: red">*</span></label>
                <select class="form-control" id="class" name="class" required>
                    <option value="">--Select Class--</option>
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
                </select>
                <small id="class-help" class="form-text text-muted">Please select the class the student wants to join.</small>
            </div>
            <div class="form-group">
                <label for="school-required">School Admission Required:<span style="color: red">*</span></label>
                <select class="form-control" id="school-required" name="school-required" required>
                    <option value="">--Select--</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                <small id="school-required-help" class="form-text text-muted">Do you require admission in a new school?</small>
            </div>

            <div id="school-details" style="display: none;">
                <div class="form-group">
                    <label for="school-name">Name Of The School:<span style="color: red">*</span></label>
                    <input type="text" class="form-control" id="school-name" name="school-name" placeholder="Enter name of the school" required>
                    <small id="school-name-help" class="form-text text-muted">Please enter the name of the current school or the new school you want to join.</small>
                </div>

                <div class="form-group">
                    <label for="board-name">Name Of The Board:<span style="color: red">*</span></label>
                    <select class="form-control" id="board-name" name="board-name" required>
                        <option value="">--Select--</option>
                        <option value="CBSE">CBSE</option>
                        <option value="ICSE">ICSE</option>
                        <option value="ISC">ISC</option>
                        <option value="State Board">State Board</option>
                    </select>
                    <small id="board-name-help" class="form-text text-muted">Please enter the name of the board of education.</small>
                </div>

                <div class="form-group">
                    <label for="medium">Medium:<span style="color: red">*</span></label>
                    <select class="form-control" id="medium" name="medium" required>
                        <option value="">--Select Medium--</option>
                        <option value="English">English</option>
                        <option value="Hindi">Hindi</option>
                        <option value="Other">Other</option>
                    </select>
                    <small id="medium-help" class="form-text text-muted">Please select the medium of instruction.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="income">Family Monthly Income</label>
                <input type="number" class="form-control" id="income" name="income" required>
                <small id="income-help" class="form-text text-muted">Please enter the total monthly income of the student's family.</small>
            </div>
            <div class="form-group">
                <label for="family-members">Total Number of Family Members</label>
                <input type="number" class="form-control" id="family-members" name="family-members" required>
                <small id="family-members-help" class="form-text text-muted">Please enter the total number of members in the student's family.</small>
            </div>
            <div class="form-group">
                <label for="payment-mode">Payment Mode:<span style="color: red">*</span></label>
                <select class="form-control" id="payment-mode" name="payment-mode" required>
                    <option value="">--Select--</option>
                    <option value="cash">Cash</option>
                    <option value="online">Online</option>
                </select>
                <small id="payment-mode-help" class="form-text text-muted">Please select the payment mode for the admission fee.</small>
            </div>

            <div class="form-group" id="cash-authentication-code" style="display:none;">
                <label for="c-authentication-code">C-Authentication Code:<span style="color: red">*</span></label>
                <input type="text" class="form-control" id="c-authentication-code" name="c-authentication-code" placeholder="Enter C-Authentication code">
                <small id="c-authentication-code-help" class="form-text text-muted">Please enter the C-Authentication code if you are paying by cash.</small>
            </div>

            <div class="form-group" id="online-declaration" style="display:none;">

                <p>Click <a href="https://paytm.me/7Nu-Znk" target="_blank">https://paytm.me/7Nu-Znk</a> to complete the payment. Please note that if you submit the form without completing the payment or with an incorrect transaction ID, your application will be placed on hold for the next two business days. After this period, if the admission fee has not been received, your submission will be cancelled.</p>

                <div class="form-group">
                    <label for="transaction-id">Transaction ID:<span style="color: red">*</span></label>
                    <input type="text" class="form-control" id="transaction-id" name="transaction-id" required>
                    <small id="online-declaration-help" class="form-text text-muted">Please enter the transaction ID if you have paid the admission fee online.</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button><br><br>
        </form>

    </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        const aadharYes = document.getElementById('aadhar-yes');
        const aadharNo = document.getElementById('aadhar-no');
        const hiddenPanel = document.getElementById('hidden-panel');
        const hiddenpanelguardianaadhar = document.getElementById('hidden-panel-guardian-aadhar');
        const aadharNumber = document.getElementById('aadhar-number');
        const aadharCardUpload = document.getElementById('aadhar-card-upload');
        const guardianaadharNumber = document.getElementById('guardian-aadhar-number');

        function handleAadharChange() {
            if (aadharYes.checked) {
                hiddenPanel.style.display = 'block';
                hiddenpanelguardianaadhar.style.display = 'none';
                aadharNumber.required = true;
                aadharCardUpload.required = true;
            } else {
                hiddenPanel.style.display = 'none';
                hiddenpanelguardianaadhar.style.display = 'block';
                aadharNumber.required = false;
                aadharCardUpload.required = false;
                guardianaadharNumber.required = true;
            }
        }

        aadharYes.addEventListener('change', handleAadharChange);
        aadharNo.addEventListener('change', handleAadharChange);
    </script>
    <script>
        $(document).ready(function() {
            $('#school-required').change(function() {
                var selectedValue = $(this).val();
                if (selectedValue === 'No') {
                    $('#school-details').show();
                    $('#school-name').attr('required', 'required');
                    $('#board-name').attr('required', 'required');
                    $('#medium').attr('required', 'required');
                } else {
                    $('#school-details').hide();
                    $('#school-name').removeAttr('required');
                    $('#board-name').removeAttr('required');
                    $('#medium').removeAttr('required');
                }
            });
        });
    </script>

    <script>
        // show/hide payment fields based on payment mode selection
        var paymentModeSelect = document.getElementById("payment-mode");
        paymentModeSelect.addEventListener("change", function() {
            var selectedOption = paymentModeSelect.options[paymentModeSelect.selectedIndex].value;
            if (selectedOption === "cash") {
                document.getElementById("cash-authentication-code").style.display = "block";
                document.getElementById("c-authentication-code").required = true;
                document.getElementById("online-declaration").style.display = "none";
                document.getElementById("transaction-id").required = false;
            } else if (selectedOption === "online") {
                document.getElementById("cash-authentication-code").style.display = "none";
                document.getElementById("c-authentication-code").required = false;
                document.getElementById("online-declaration").style.display = "block";
                document.getElementById("transaction-id").required = true;
            } else {
                document.getElementById("cash-authentication-code").style.display = "none";
                document.getElementById("c-authentication-code").required = false;
                document.getElementById("online-declaration").style.display = "none";
                document.getElementById("transaction-id").required = false;
            }
        });
    </script>



</body>

</html>





</form>
</div>

<!-- Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>