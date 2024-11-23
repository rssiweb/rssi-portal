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
            <?php // Extract file ID using regular expression
            preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $applicant_photo, $matches);
            $file_id = $matches[1]; ?>
            <img src="<?php echo 'https://drive.google.com/thumbnail?id=' . $file_id ?>" alt="Profile" class="rounded-circle" width="30" height="30">
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
        <a class="nav-link collapsed" data-bs-target="#myservices" data-bs-toggle="collapse" href="#">
          <span>NextGen CareerScape</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="myservices" class="nav-content collapse " data-bs-parent="#sidebar-nav">
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
          <li class="nav-item">
            <a class="nav-link collapsed" id="OfficialLetter" href="official_letter.php">
              <span>Official Letter</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-heading">Pages</li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="myprofile.php" target="_blank">
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
      },{
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
    // Check if the current page is home.php
    if (window.location.href.includes('home.php')) {
      // Remove the "collapsed" class from the appropriate element
      document.addEventListener('DOMContentLoaded', function() {
        var homeLink = document.getElementById('homeLink');
        homeLink.classList.remove('collapsed');
      });
    }
  </script>