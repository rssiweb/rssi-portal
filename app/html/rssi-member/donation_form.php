<?php require_once __DIR__ . "/../../bootstrap.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI NGO - Donation Form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/css/bootstrap.min.css">

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
    <div class="container">
        <h1 class="mt-5">RSSI NGO Donation Form</h1>

        <!-- Part 1: Donation Information -->
        <form action="donation_form.php" method="POST" id="donationInfoForm">
            <h2>Donation Information</h2>
            <div class="mb-3">
                <label for="donationType" class="form-label">Have you donated earlier?</label>
                <select class="form-select" id="donationType" name="donationType" onchange="toggleDonorSections()" required>
                    <option value="">Select</option>
                    <option value="new">No, I am a first-time donor</option>
                    <option value="existing">Yes, I have donated earlier</option>
                </select>
            </div>

            <div id="existingDonorInfo" style="display: none;">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="contactnumber_verify" name="contactnumber_verify" placeholder="Enter your contact number">
                    <button type="submit" class="btn btn-primary" id="verifybutton">Find your details</button>
                </div>
            </div>

            <!-- Get user data -->
            <div id="verificationResult" style="display: none;">
                <div class="mb-3">
                    <label for="donorName" class="form-label">Donor Name</label>
                    <input type="text" id="donorName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label for="donorEmail" class="form-label">Email Address</label>
                    <input type="email" id="donorEmail" class="form-control" readonly>
                </div>
            </div>


            <!-- New Donor Information -->
            <div id="newDonorInfo" style="display: none;">
                <h2>Donor Details</h2>
                <div class="mb-3">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullName" name="fullName" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="contactNumberNew" class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" id="contactNumberNew" name="contactNumberNew" required>
                </div>
                <div class="mb-3">
                    <label for="documentType" class="form-label">National Identifier Type</label>
                    <select class="form-select" id="documentType" name="documentType">
                        <option value="" disabled selected>Select</option>
                        <option value="pan">Permanent Account Number (PAN)</option>
                        <option value="aadhaar">Aadhaar Number</option>
                        <option value="taxIdentification">Tax Identification Number</option>
                        <option value="passport">Passport number</option>
                        <option value="voterId">Elector's photo identity number</option>
                        <option value="drivingLicense">Driving License number</option>
                        <option value="rationCard">Ration card number</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="nationalId" class="form-label">National Identifier Number</label>
                    <input type="text" class="form-control" id="nationalId" name="nationalId">
                </div>

                <div class="mb-3">
                    <label for="postalAddress" class="form-label">Postal Address</label>
                    <textarea class="form-control" id="postalAddress" name="postalAddress" rows="4"></textarea>
                </div>
            </div>

            <!-- Donation Amount -->
            <div class="mb-3">
                <label for="donationAmount" class="form-label">Donation Amount</label>
                <div class="input-group">
                    <select class="form-select" id="currency" name="currency" required>
                        <option value="" disabled selected>Select Currency</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="INR">INR</option>
                    </select>
                    <input type="number" class="form-control" id="donationAmount" name="donationAmount" min="500" step="any" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Message (Optional)</label>
                <textarea class="form-control" id="message" name="message" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="donateButton" disabled>Donate Now</button>
        </form>

        <form name="get_details_form" id="get_details_form" action="#" method="POST">
            <input type="hidden" name="form-type" value="get_details">
            <input type="hidden" name="contactnumber_verify_input">
        </form>

        <script>
            var contactNumberInput = document.getElementById("contactnumber_verify");
            var contactNumberVerifyInput = document.getElementsByName("contactnumber_verify_input")[0];
            var donorNameInput = document.getElementById("donorName");
            var donorEmailInput = document.getElementById("donorEmail");
            var verificationResultDiv = document.getElementById("verificationResult");

            contactNumberInput.addEventListener("input", function(event) {
                contactNumberVerifyInput.value = this.value;
            });

            const scriptURL = 'payment-api.php';

            // Add an event listener to the submit button with id "verifybutton"
            document.getElementById("verifybutton").addEventListener("click", function(event) {
                event.preventDefault(); // prevent default form submission

                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['get_details_form'])
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            donorNameInput.value = data.data.fullname;
                            donorEmailInput.value = data.data.email;
                            verificationResultDiv.style.display = "block"; // Show the result div
                            alert("User data fetched successfully!");
                        } else if (data.status === 'no_records') {
                            verificationResultDiv.style.display = "none"; // Hide the result div
                            donorNameInput.value = "";
                            donorEmailInput.value = "";
                            alert("No records found in the database. Donate as a new user.");
                        } else {
                            console.log('Error:', data.message);
                            alert("Error retrieving user data. Please try again later or contact support.");
                        }
                    })
                    .catch(error => {
                        console.log('Error:', error);
                        alert("Error fetching user data. Please try again later or contact support.");
                    });
            });
        </script>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript function to toggle donor sections based on donation type
        function toggleDonorSections() {
            const donationType = document.getElementById('donationType').value;
            const existingDonorInfo = document.getElementById('existingDonorInfo');
            const newDonorInfo = document.getElementById('newDonorInfo');
            const verificationResult = document.getElementById('verificationResult');
            const donateButton = document.getElementById('donateButton');

            if (donationType === 'existing') {
                existingDonorInfo.style.display = 'block';
                newDonorInfo.style.display = 'none';
                verificationResult.style.display = 'block';
                donateButton.disabled = true;
            } else if (donationType === 'new') {
                existingDonorInfo.style.display = 'none';
                newDonorInfo.style.display = 'block';
                verificationResult.style.display = 'none';
                donateButton.disabled = false;
            } else {
                existingDonorInfo.style.display = 'none';
                newDonorInfo.style.display = 'none';
                verificationResult.style.display = 'none';
                donateButton.disabled = true;
            }
        }
    </script>
</body>

</html>