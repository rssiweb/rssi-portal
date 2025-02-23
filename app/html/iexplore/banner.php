<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bootstrap Floating Banner</title>
  <!-- Bootstrap 5.3 CSS -->
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
  <style>
    /* Custom animation for sliding down */
    @keyframes slideDown {
      from { top: -100px; }
      to { top: 0; }
    }
    .banner {
      animation: slideDown 0.5s ease-out;
      z-index: 1000; /* Ensure it stays on top */
    }
  </style>
</head>
<body>
  <!-- Full-width Banner -->
  <div class="banner position-fixed top-0 start-0 w-100 bg-white border-bottom py-3" id="floatingBanner">
    <div class="container">
      <div class="row align-items-center">
        <div class="col">
          <p class="mb-0">
            User already have an account with Rina Shiksha Sahayak Foundation? 
            <span class="text-muted">Log in using your credentials of Phinox Portal.</span>
          </p>
        </div>
        <div class="col-auto">
          <!-- Close Button -->
          <button type="button" class="btn-close" aria-label="Close" onclick="closeBanner()"></button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5.3 JS (for close functionality) -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
  <script>
    function closeBanner() {
      const banner = document.getElementById('floatingBanner');
      banner.style.display = 'none'; // Hide the banner
    }
  </script>
</body>
</html>