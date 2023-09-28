<!DOCTYPE html>
<html lang="en">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <title>Registration Form</title>
</head>
<body>

    <div class="container mt-5">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="registrationTab" data-bs-toggle="tab" href="#registrationForm">Registration Form</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" id="verificationTab" data-bs-toggle="tab" href="#verificationForm">Verification Form</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" id="profileTab" data-bs-toggle="tab" href="#profileForm">Profile Update</a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="registrationForm">
                <h1>Registration Form</h1>
                <form>
                    <!-- Registration Form Fields -->
                    <div class="mb-3">
                        <label for="applicationNumber" class="form-label">Application Number:</label>
                        <input type="text" class="form-control" id="applicationNumber">
                    </div>
                    <!-- Rest of the fields -->

                    <button type="button" class="btn btn-primary" onclick="enableVerificationForm()">Next</button>
                </form>
            </div>

            <div class="tab-pane fade" id="verificationForm">
                <h1>Verification Form</h1>
                <form>
                    <!-- Verification Form Fields -->
                    <div class="mb-3">
                        <label for="verificationCode" class="form-label">Verification Code:</label>
                        <input type="text" class="form-control" id="verificationCode">
                    </div>
                    <!-- Rest of the fields -->

                    <button type="button" class="btn btn-primary" onclick="enableProfileUpdateForm()">Next</button>
                </form>
            </div>

            <div class="tab-pane fade" id="profileForm">
                <h1>Profile Update</h1>
                <form>
                    <!-- Profile Update Form Fields -->
                    <div class="mb-3">
                        <label for="profileName" class="form-label">Profile Name:</label>
                        <input type="text" class="form-control" id="profileName">
                    </div>
                    <!-- Rest of the fields -->

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function enableVerificationForm() {
            // Enable the verification tab
            document.getElementById("verificationTab").classList.remove("disabled");
            document.getElementById("verificationTab").setAttribute("data-bs-toggle", "tab");
            document.getElementById("verificationTab").setAttribute("href", "#verificationForm");

            // Show the verification form tab
            document.getElementById("verificationTab").click();
        }

        function enableProfileUpdateForm() {
            // Enable the profile update tab
            document.getElementById("profileTab").classList.remove("disabled");
            document.getElementById("profileTab").setAttribute("data-bs-toggle", "tab");
            document.getElementById("profileTab").setAttribute("href", "#profileForm");

            // Show the profile update form tab
            document.getElementById("profileTab").click();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
