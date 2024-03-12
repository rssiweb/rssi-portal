<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inactive Timer</title>
  <!-- Bootstrap CSS -->
  <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet"> -->
</head>

<body>
  <!-- <p>Time spent inactive: <span id="inactiveTime">0 seconds</span></p> -->

  <!-- Bootstrap Modal -->
  <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Uh-oh! It's been a moment since your last move</h5>
          <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
        </div>
        <div class="modal-body">
          <p>Your session will expire in <span id="remainingTime"></span>. Are you still working? If you want to continue, click Yes.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="redirectToLogout()">Sign out</button>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="resetTimer()">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let inactiveTime = 0;
    let timerInterval;
    let lastInteractionTime = Date.now();
    let modalShown = false;

    function startTimer() {
      timerInterval = setInterval(() => {
        const currentTime = Date.now();
        inactiveTime += Math.floor((currentTime - lastInteractionTime) / 1000);
        // document.getElementById('inactiveTime').innerText = inactiveTime + " seconds";
        lastInteractionTime = currentTime;
        checkInactiveTime();
      }, 1000);
    }

    function stopTimer() {
      clearInterval(timerInterval);
    }

    function resetTimer() {
      inactiveTime = 0;
      // document.getElementById('inactiveTime').innerText = inactiveTime + " seconds";
      lastInteractionTime = Date.now();
      modalShown = false; // Reset modalShown flag
    }

    function checkInactiveTime() {
      const sessionDuration = 1800; // Duration of the session in seconds
      const remainingTime = sessionDuration - inactiveTime;

      if (remainingTime <= 0) {
        // Display an alert for session expiration
        alert("Your session has expired, please login again.");
        // Redirect to logout.php
        window.location.href = "logout.php";
      } else if (remainingTime > 0 && remainingTime <= 600 && !modalShown) {
        // Remove any existing backdrop
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
          existingBackdrop.remove();
        }

        // Display the modal if remaining time is less than or equal to 25 seconds
        var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
          backdrop: 'static'
        });

        // Update modal body dynamically with countdown timer
        document.getElementById('remainingTime').innerText = remainingTime;

        // Update remainingTime every second until the modal is shown
        let countdownInterval = setInterval(() => {
          const updatedRemainingTime = sessionDuration - inactiveTime;

          // Calculate minutes and seconds
          const minutes = Math.floor(updatedRemainingTime / 60);
          const seconds = updatedRemainingTime % 60;

          // Format the remaining time
          let formattedTime = '';
          if (minutes > 0) {
            formattedTime += `${minutes} minute${minutes !== 1 ? 's' : ''}`;
            if (seconds > 0) {
              formattedTime += ` ${seconds} second${seconds !== 1 ? 's' : ''}`;
            }
          } else {
            formattedTime = `${seconds} second${seconds !== 1 ? 's' : ''}`;
          }

          document.getElementById('remainingTime').innerText = formattedTime;

          if (updatedRemainingTime <= 0) {
            clearInterval(countdownInterval);
          }
        }, 1000);


        myModal.show();
        modalShown = true;
      }
    }

    // Redirect to logout.php
    function redirectToLogout() {
      window.location.href = "logout.php";
    }

    // Event listeners for page visibility change
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'visible') {
        stopTimer();
        resetTimer();
      } else {
        startTimer();
      }
    });

    // Event listeners for various user interactions to reset the timer
    document.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onmousedown = resetTimer; // touchscreen presses
    document.ontouchstart = resetTimer;
    document.onclick = resetTimer; // touchpad clicks
    document.onkeydown = resetTimer; // onkeypress is deprecated
    document.addEventListener('scroll', resetTimer, true); // improved; see comments

    // Start the timer when the page is loaded
    startTimer();
  </script>

  <!-- Bootstrap JS (optional) -->
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script> -->
</body>

</html>