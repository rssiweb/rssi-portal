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

@$associate_number = @strtoupper($_GET['associate-number']);
$result = pg_query($con, "SELECT fullname,associatenumber,doj,effectivedate,remarks,photo,engagement,position,depb FROM rssimyaccount_members WHERE associatenumber = '$associate_number'");
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
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
    <title>Onboarding form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="container">
        <form method="get" name="a_lookup" id="a_lookup">
            <h3>Associate Information Lookup</h3>
            <hr>
            <div class="mb-3">
                <label for="associate-number" class="form-label">Associate Number:</label>
                <input type="text" class="form-control" id="associate-number" name="associate-number" Value="<?php echo $associate_number ?>" placeholder="Enter associate number">
                <div class="form-text">Enter the associate number to search for their information.</div>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Search</button>
        </form>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>
                    <form method="post" name="a_exit" id="a_exit">

                        <h3>Associate Onboarding Form</h3>
                        <hr>
                        <div class="container">
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <img src="<?php echo $array['photo'] ?>" alt="Profile picture" width="100px">
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h2><?php echo $array['fullname'] ?></h2>
                                            <p><strong>Associate ID:</strong> <?php echo $array['associatenumber'] ?></p>
                                            <p><strong>Joining Date:</strong> <?php echo date('M d, Y', strtotime($array['doj'])) ?></p>
                                            <strong>Last Working Day:</strong> <?php echo ($array['effectivedate'] == null) ? "N/A" : date('M d, Y', strtotime($array['effectivedate'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Engagement:</strong> <?php echo $array['engagement']; ?></p>
                                            <p><strong>Position:</strong> <?php echo implode('-', array_slice(explode('-',  $array['position']), 0, 2)); ?></p>
                                            <p><strong>Deputed Branch:</strong> <?php echo $array['depb']; ?></p>
                                            <!-- Add any additional information you want to display here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="mb-3">
                            <label for="photo" class="form-label">Current Photo</label>
                            <input type="hidden" class="form-control" id="photo" name="photo">
                            <div class="mt-2">
                                <button type="button" class="btn btn-primary" onclick="startCamera()">Start Camera</button>
                                <button type="button" class="btn btn-primary d-none" id="capture-btn" onclick="capturePhoto()">Capture Photo</button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <video id="video-preview" class="img-thumbnail" alt="Preview" width="320" height="240"></video>
                            <canvas id="canvas-preview" class="d-none" width="320" height="240"></canvas>
                            <img id="photo-preview" class="d-none img-thumbnail" alt="Captured Photo" width="320" height="240" src="">
                        </div>

                        <script>
                            let videoPreview, canvasPreview, photoInput, captureBtn, photoPreview;

                            function startCamera() {
                                const constraints = {
                                    video: true,
                                    audio: false
                                };

                                videoPreview = document.getElementById('video-preview');
                                canvasPreview = document.getElementById('canvas-preview');
                                photoInput = document.getElementById('photo');
                                captureBtn = document.getElementById('capture-btn');
                                photoPreview = document.getElementById('photo-preview');

                                navigator.mediaDevices.getUserMedia(constraints)
                                    .then(stream => {
                                        videoPreview.srcObject = stream;
                                        videoPreview.play();
                                        captureBtn.classList.remove('d-none');
                                        photoPreview.classList.add('d-none'); // Hide photo preview on start camera
                                    })
                                    .catch(error => {
                                        console.error('Error accessing camera: ', error);
                                    });

                                videoPreview.addEventListener('canplay', () => {
                                    canvasPreview.width = videoPreview.videoWidth;
                                    canvasPreview.height = videoPreview.videoHeight;
                                    canvasPreview.getContext('2d').drawImage(videoPreview, 0, 0, canvasPreview.width, canvasPreview.height);
                                });
                            }

                            function capturePhoto() {
                                canvasPreview.getContext('2d').drawImage(videoPreview, 0, 0, canvasPreview.width, canvasPreview.height);
                                const photoURL = canvasPreview.toDataURL('image/png');
                                photoInput.value = photoURL;
                                videoPreview.srcObject.getTracks().forEach(track => track.stop());
                                canvasPreview.classList.add('d-none');
                                videoPreview.classList.add('d-none');
                                captureBtn.classList.add('d-none');
                                photoPreview.setAttribute('src', photoURL);
                                photoPreview.classList.remove('d-none'); // Show photo preview after capture
                            }
                        </script>


                        <div class="mb-3">
                            <label for="exit-interview" class="form-label">Exit Interview:</label>
                            <textarea class="form-control" rows="5" id="exit-interview"></textarea>
                            <div class="form-text">Enter any comments or feedback from the associate's exit interview.</div>
                        </div>

                        <div class="mb-3">
                            <label for="exit-form-date" class="form-label">Exit Form Date:</label>
                            <input type="date" class="form-control" id="exit-form-date">
                            <div class="form-text">Enter the date the exit form was completed.</div>
                        </div>

                        <div>
                            <label for="signature-field">Please verify the data entered above and sign below to confirm its accuracy. By signing, you agree that the information provided is complete and correct to the best of your knowledge.</label>
                            <div>
                                <canvas id="signature-canvas" class="border border-1 rounded"></canvas>
                                <input type="hidden" name="signature-data" id="signature-data">
                                <button id="clear-button" class="btn btn-secondary mt-2">Clear Signature</button>
                            </div>

                            <div class="mb-3">
                                <label for="signature" class="form-label">Signature</label>
                                <input type="text" class="form-control" name="signature-name" id="signature-name" placeholder="Please sign above" required>
                            </div>
                        </div>

                        <script>
                            const canvas = document.getElementById('signature-canvas');
                            const signatureDataInput = document.getElementById('signature-data');
                            const signatureNameInput = document.getElementById('signature-name');
                            const clearButton = document.getElementById('clear-button');
                            const ctx = canvas.getContext('2d');
                            let isDrawing = false;
                            let lastX = 0;
                            let lastY = 0;
                            let sigData = '';

                            function startDrawing(e) {
                                isDrawing = true;
                                [lastX, lastY] = [e.offsetX, e.offsetY];
                            }

                            function draw(e) {
                                if (!isDrawing) return;
                                ctx.beginPath();
                                ctx.moveTo(lastX, lastY);
                                ctx.lineTo(e.offsetX, e.offsetY);
                                ctx.stroke();
                                [lastX, lastY] = [e.offsetX, e.offsetY];
                                sigData = canvas.toDataURL();
                            }

                            function endDrawing() {
                                isDrawing = false;
                                signatureDataInput.value = sigData;
                            }

                            function clearCanvas(event) {
                                event.preventDefault();
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                                signatureDataInput.value = '';
                                sigData = '';
                                signatureNameInput.value = '';
                            }

                            clearButton.addEventListener('click', clearCanvas);


                            canvas.addEventListener('mousedown', startDrawing);
                            canvas.addEventListener('mousemove', draw);
                            canvas.addEventListener('mouseup', endDrawing);
                            canvas.addEventListener('mouseleave', endDrawing);
                            clearButton.addEventListener('click', clearCanvas);
                        </script>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                <?php } ?>
            <?php } ?>
            <?php if ($role != 'Admin' && $role != 'Offline Manager') { ?><p>Oops! It looks like you're trying to access the data that doesn't belong to you.</p><?php } ?>
        <?php
        } else if ($associate_number == null) {
        ?>
            <p>Please enter the Associate ID</p>
        <?php
        } else {
        ?>
            <p>We could not find any records matching the entered Goal sheet ID.</p>
        <?php } ?>
    </div>

    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>
</body>

</html>