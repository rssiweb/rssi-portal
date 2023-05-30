<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

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

?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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
    </head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-info" style="padding: 5%;">
                    <div class="panel-heading">
                        <h3 class="panel-title">Update your Current Address</h3>
                    </div>
                    <form name="submit-to-google-sheet" action="" method="POST" onSubmit="alert('Your response has been recorded.');">
                        <br><label for="fname">Associate Name:</label><br>
                        <input type="text" readonly id="fname" name="fname" value="<?php echo $fullname ?>"><br><br>
                        <label for="stid">Associate Number:</label><br>
                        <input type="text" readonly id="stid" name="stid" value="<?php echo $associatenumber ?>"><br><br>
                        <label for="address">Current Address:</label><br>
                        <textarea id="address" name="address" cols="40" rows="5"></textarea><br><br>
                        
                        <input onclick='window.location.href="home.php"' type="submit" id="submit" value="Submit">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('#yes').click(function() {
            $('#count1').val('Agree');
        });

        $('#no').click(function() {
            $('#count1').val('NA');
        });
    </script>
    <script>
        const scriptURL = 'https://script.google.com/macros/s/AKfycbzExVHj1fLiSiERCCF5IVI73-Q7qJDaBDGNzdHJvOUuvyUX5Ig/exec'
        const form = document.forms['submit-to-google-sheet']

        form.addEventListener('submit', e => {
            e.preventDefault()
            fetch(scriptURL, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => console.log('Success!', response))
                .catch(error => console.error('Error!', error.message))
        })
    </script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <!-- disable submit button if any required field is blank -->
        <script>
            $(document).ready(function() {
                $('#submit').attr('disabled', true);

                $('#address').keyup(function() {
                    if ($(this).val().length != 0) {
                        $('#submit').attr('disabled', false);
                    } else {
                        $('#submit').attr('disabled', true);
                    }
                })
            });
        </script>
</body>

</html>
