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

  <!-- Bootstrap Modal -->
  <div class="modal fade" id="myModal_session" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Uh-oh! It's been a moment since your last move</h5>
        </div>
        <div class="modal-body">
          <p>Your session will expire in <span id="remainingTime"></span>. Are you still working? If you want to continue, click Yes.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="signOutButton">Sign out</button>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="continueButton">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function() {
      let inactiveTime = 0;
      let timerTimeout;
      let lastInteractionTime = Date.now();
      let modalShown = false;
      const sessionDuration = 3600; // Duration of the session in seconds
      const remainingTimeElement = document.getElementById('remainingTime');

      function startTimer() {
        timerTimeout = setTimeout(checkInactiveTime, 1000);
      }

      function stopTimer() {
        clearTimeout(timerTimeout);
      }

      function resetTimer() {
        inactiveTime = 0;
        lastInteractionTime = Date.now();
        modalShown = false;
        stopTimer();
        startTimer();
      }

      function checkInactiveTime() {
        const currentTime = Date.now();
        const elapsedTime = (currentTime - lastInteractionTime) / 1000;
        inactiveTime += elapsedTime;
        lastInteractionTime = currentTime;

        const remainingTime = Math.max(sessionDuration - inactiveTime, 0);
        remainingTimeElement.textContent = formatTime(remainingTime);

        if (remainingTime === 0) {
          alert("Your session has expired, please login again.");
          window.location.href = "logout.php";
        } else if (remainingTime > 0 && remainingTime <= 300 && !modalShown) {
          showModal();
        }

        timerTimeout = setTimeout(checkInactiveTime, 1000);
      }

      function showModal() {
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
          existingBackdrop.remove();
        }

        modalShown = true;
        var myModal = new bootstrap.Modal(document.getElementById('myModal_session'), {
          backdrop: 'static',
          keyboard: false // Prevent modal from closing with keyboard
        });
        myModal.show();
      }

      function formatTime(timeInSeconds) {
        const minutes = Math.floor(timeInSeconds / 60);
        const seconds = Math.floor(timeInSeconds % 60);
        return `${minutes} minute${minutes !== 1 ? 's' : ''} ${seconds} second${seconds !== 1 ? 's' : ''}`;
      }

      function redirectToLogout() {
        window.location.href = "logout.php";
      }

      function resetTimerOnInteraction() {
        resetTimer();
      }

      const debouncedResetTimerOnInteraction = debounce(resetTimerOnInteraction, 100);

      ['mousemove', 'mousedown', 'touchstart', 'click', 'keydown', 'scroll'].forEach(eventType => {
        document.addEventListener(eventType, debouncedResetTimerOnInteraction);
      });

      document.getElementById('signOutButton').addEventListener('click', redirectToLogout);
      document.getElementById('continueButton').addEventListener('click', resetTimer);

      startTimer();

      function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
          const context = this;
          const args = arguments;
          const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
          };
          const callNow = immediate && !timeout;
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
          if (callNow) func.apply(context, args);
        };
      }
    })();
  </script>

  <!-- Bootstrap JS (optional) -->
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script> -->
</body>

</html>