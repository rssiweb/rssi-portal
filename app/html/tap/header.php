<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index.html" class="logo d-flex align-items-center">
      <!-- <img src="../img/phoenix-36-logo-png-transparent.png" alt=""> -->
      <span class="d-none d-lg-block">Phoenix</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="POST" action="#">
      <input type="text" name="query" placeholder="Search" title="Enter search keyword">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle " href="#">
          <i class="bi bi-search"></i>
        </a>
      </li><!-- End Search Icon-->

      <li class="nav-item dropdown pe-3">

        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <?php
          // Default value for preview_url and initials
          $preview_url = '';
          $initials = '';

          // Check if the photo exists
          if (!empty($applicant_photo)) {
            // Extract file ID using regular expression
            preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $applicant_photo, $matches);

            // Check if a match was found
            if (isset($matches[1])) {
              $file_id = $matches[1];
              // Generate the preview URL for iframe
              $preview_url = "https://drive.google.com/file/d/$file_id/preview";
            }
          } else {
            // If no photo, extract initials from applicant's name
            $name_parts = explode(" ", $applicant_name);
            $initials = strtoupper(substr($name_parts[0], 0, 1)); // First name initial
            if (isset($name_parts[1])) {
              $initials .= strtoupper(substr($name_parts[1], 0, 1)); // Last name initial (if exists)
            }
          }
          ?>

          <div class="profile-photo">
            <?php if (!empty($preview_url)): ?>
              <!-- Show the applicant photo if preview_url is set -->
              <iframe src="<?php echo $preview_url; ?>" class="rounded-circle" width="30" height="30" frameborder="0" allow="autoplay"
                sandbox="allow-scripts allow-same-origin"></iframe>
            <?php else: ?>
              <!-- Show initials if no photo -->
              <div class="profile-initials" style="width: 30px; height: 30px; background-color: #007bff; color: white; text-align: center; line-height: 30px; border-radius: 50%;"><?php echo $initials; ?></div>
            <?php endif; ?>
          </div>

          <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $applicant_name ?></span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?php echo $applicant_name ?></h6>
            <span>Role :&nbsp;User</span>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <!-- <li>
              <a class="dropdown-item d-flex align-items-center" href="myprofile.php" target="_blank">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li> -->
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="resetpassword.php">
              <i class="bi bi-gear"></i>
              <span>Reset Password</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="#">
              <i class="bi bi-question-circle"></i>
              <span>Need Help?</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Sign Out</span>
            </a>
          </li>

        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header><!-- End Header -->

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <li class="nav-item">
      <a class="nav-link collapsed" id="homeLink" href="home.php">
        <span>Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" id="applicationForm" href="application_form.php">
        <span>Application Form</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" id="indentityVerification" href="identity_verification.php">
        <span>Identity Verification</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" id="documentVerification" href="document_verification.php">
        <span>Document Verification</span>
      </a>
    </li>

    <li class="nav-heading">Pages</li>

    <li class="nav-item">
      <a class="nav-link collapsed" href="#">
        <i class="bi bi-person"></i>
        <span>Profile</span>
      </a>
    </li><!-- End Profile Page Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" href="#">
        <i class="bi bi-question-circle"></i>
        <span>F.A.Q</span>
      </a>
    </li><!-- End F.A.Q Page Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" href="#">
        <i class="bi bi-envelope"></i>
        <span>Contact</span>
      </a>
    </li><!-- End Contact Page Nav -->
  </ul>

</aside><!-- End Sidebar-->

<script>
  // Get the current page URL
  const currentPage = window.location.href;

  // Define an array of menu items and their corresponding IDs and URLs
  const menuItems = [{
      id: 'myservices',
      linkId: 'applicationForm',
      url: 'application_form.php'
    }, {
      id: 'myservices',
      linkId: 'indentityVerification',
      url: 'identity_verification.php'
    }
    // Add more menu items in the same format
    // { id: 'menuItemId', linkId: 'menuItemLinkId', url: 'menuItemURL' },
  ];

  // Loop through each menu item
  menuItems.forEach(menuItem => {
    // Check if the current page URL matches the menu item URL
    if (currentPage.includes(menuItem.url)) {
      // Add the 'show' class to the corresponding menu item ID
      document.getElementById(menuItem.id).classList.add('show');
      // Add the 'active' class to the corresponding menu item link ID
      document.getElementById(menuItem.linkId).classList.add('active');
    }
  });
</script>
<script>
  // Function to remove the "collapsed" class from the specified link
  function toggleCollapsedClass(page, linkId) {
    if (window.location.href.includes(page)) {
      document.addEventListener('DOMContentLoaded', function() {
        var link = document.getElementById(linkId);
        if (link) {
          link.classList.remove('collapsed');
        }
      });
    }
  }

  // Call the function for 'home.php' and 'profile.php'
  toggleCollapsedClass('home.php', 'homeLink');
  toggleCollapsedClass('application_form.php', 'applicationForm');
  toggleCollapsedClass('identity_verification.php', 'indentityVerification');
  toggleCollapsedClass('document_verification.php', 'documentVerification');
</script>