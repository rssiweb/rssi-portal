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



    // Retrieve the signature data from the form
    const signatureData = document.getElementById('signature-data').value;

    // Get the canvas and its context
    const canvas_sig = document.getElementById('signature-canvas');
    const ctx_sig = canvas_sig.getContext('2d');

    // Create a new image object
    const signatureImage = new Image();

    // Set the source of the image object to the signature data
    signatureImage.src = signatureData;

    // Once the image is loaded, draw it onto the canvas
    signatureImage.onload = function() {
        ctx_sig.drawImage(signatureImage, 0, 0);
    };
</script>







$item = $entityManager->getRepository('AssociateExit')->find($otp_initiatedfor); //primary key
  if ($item) {
    // Generate a random 6 digit number
    $otp = rand(100000, 999999);
    $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

    $item->setExitGenOtpAssociate($hashedValue);
    $entityManager->persist($item);
    $entityManager->flush();
    echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'exit',
        "otp" => @$otp,
        "receiver" => @$associate_name,
      ), $email, False);
    }
  } else {
    echo "failed";
  }