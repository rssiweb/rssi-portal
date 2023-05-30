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
            <img src="<?php echo $photo ?>" alt="Profile" class="rounded-circle" width="30" height="30">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $fullname ?></span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo $fullname ?></h6>
              <span>Role :&nbsp;<?php echo $role ?></span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="myprofile.php">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="resetpassword.php">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
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
          <span>Class details</span>
        </a>
      </li><!-- End Contact Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#acadamis" data-bs-toggle="collapse" href="#">
          <span>Acadamis</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="acadamis" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a id="examLink" href="exam.php">
              <span>Examination</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#myservices" data-bs-toggle="collapse" href="#">
          <span>My Services</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="myservices" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="leaveLink" href="leave.php">
              <span>Apply for Leave</span>
            </a>
          </li>
          <li>
            <a id="documentLink" href="document.php">
              <span>My Document</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#learning" data-bs-toggle="collapse" href="#">
          <span>Learning & Collaboration</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="learning" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="iexploreLink" href="iexplore.php">
              <span>iExplore</span>
            </a>
          </li>
          <li>
            <a id="viscoLink" href="visco.php">
              <span>VISCO</span>
            </a>
          </li>
          <li>
            <a id="libraryLink" href="library.php">
              <span>Libary</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#performance" data-bs-toggle="collapse" href="#">
          <span>Performance management </span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="performance" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="my_appraisalLink" href="my_appraisal.php">
              <span>My Appraisal</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#rewards" data-bs-toggle="collapse" href="#">
          <span>Rewards & Recognition</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="rewards" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="redeem_gemsLink" href="redeem_gems.php">
              <span>Gems</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#claims" data-bs-toggle="collapse" href="#">
          <span>Claims and Advances</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="claims" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="reimbursementLink" href="reimbursement.php">
              <span>Reimbursement</span>
            </a>
          </li>
          <li>
            <a id="medimateLink" href="#">
              <span>Medimate</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#work" data-bs-toggle="collapse" href="#">
          <span>Work</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="work" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="studentLink" href="student.php">
              <span>RSSI Student</span>
            </a>
          </li>
          <li>
            <a id="policyLink" href="policy.php">
              <span>HR Policy</span>
            </a>
          </li>
          <li>
            <a id="dashboardLink" href="dashboard.php">
              <span>Process Hub</span>
            </a>
          </li>
          <li>
            <a id="gpsLink" href="gps.php">
              <span>GPS</span>
            </a>
          </li>
          <li>
            <a id="facultyLink" href="faculty.php">
              <span>RSSI Faculty</span>
            </a>
          </li>
          <li>
            <a id="leave_adminLink" href="leave_admin.php">
              <span>Leave Management</span>
            </a>
          </li>
          <li>
            <a id="my_bookLink" href="my_book.php">
              <span>Libary Status</span>
            </a>
          </li>
          <li>
            <a id="medistatusLink" href="#">
              <span>Medimate Approval</span>
            </a>
          </li>
          <li>
            <a id="reimbursementstatusLink" href="reimbursementstatus.php">
              <span>Reimbursement Approval</span>
            </a>
          </li>
          <li>
            <a id="donationinfo_adminLink" href="donationinfo_admin.php">
              <span>Donation</span>
            </a>
          </li>
          <li>
            <a id="pmsLink" href="pms.php">
              <span>PMS</span>
            </a>
          </li>
          <li>
            <a id="amsLink" href="ams.php">
              <span>Announcement</span>
            </a>
          </li>
        </ul>
      </li><!-- End Forms Nav -->

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
    var inactivityTime = function() {
      var time;
      document.onload = resetTimer;
      document.onmousemove = resetTimer;
      document.onmousedown = resetTimer; // touchscreen presses
      document.ontouchstart = resetTimer;
      document.onclick = resetTimer; // touchpad clicks
      document.onkeydown = resetTimer; // onkeypress is deprectaed
      document.addEventListener('scroll', resetTimer, true); // improved; see comments

      function logout() {
        alert("Your session has expired, please login again.")
        location.href = 'logout.php'
        window.close()
      }

      function resetTimer() {
        clearTimeout(time);
        time = setTimeout(logout, 1800000)
        // 1000 milliseconds = 1 second
      }
    };
    window.addEventListener("load", function() {
      inactivityTime();
    }, false);
  </script>

  <script>
    // Get the current page URL
    const currentPage = window.location.href;

    // Define an array of menu items and their corresponding IDs and URLs
    const menuItems = [{
        id: 'acadamis',
        linkId: 'examLink',
        url: 'exam.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'document.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'my_certificate.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'payslip.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'gps.php'
      },
      {
        id: 'myservices',
        linkId: 'leaveLink',
        url: 'leave.php'
      }, {
        id: 'learning',
        linkId: 'iexploreLink',
        url: 'iexplore.php'
      },
      {
        id: 'learning',
        linkId: 'iexploreLink',
        url: 'my_learning.php'
      },
      {
        id: 'learning',
        linkId: 'viscoLink',
        url: 'visco.php'
      },
      {
        id: 'learning',
        linkId: 'libraryLink',
        url: 'library.php'
      },
      {
        id: 'learning',
        linkId: 'libraryLink',
        url: 'my_book.php'
      },
      {
        id: 'rewards',
        linkId: 'redeem_gemsLink',
        url: 'redeem_gems.php'
      },
      {
        id: 'claims',
        linkId: 'reimbursementLink',
        url: 'reimbursementstatus.php'
      },
      {
        id: 'claims',
        linkId: 'reimbursementLink',
        url: 'reimbursement.php'
      },
      {
        id: 'claims',
        linkId: 'medimateLink',
        url: 'medimate.php'
      },
      {
        id: 'work',
        linkId: 'studentLink',
        url: 'student.php'
      },
      {
        id: 'work',
        linkId: 'studentLink',
        url: 'fees.php'
      },
      {
        id: 'work',
        linkId: 'policyLink',
        url: 'policy.php'
      },
      {
        id: 'work',
        linkId: 'dashboardLink',
        url: 'dashboard.php'
      },
      {
        id: 'work',
        linkId: 'gpsLink',
        url: 'gps.php'
      },
      {
        id: 'work',
        linkId: 'facultyLink',
        url: 'faculty.php'
      },
      {
        id: 'work',
        linkId: 'facultyLink',
        url: 'facultyexp.php'
      },
      {
        id: 'work',
        linkId: 'leave_adminLink',
        url: 'leave_admin.php'
      },
      {
        id: 'work',
        linkId: 'leave_adminLink',
        url: 'leaveallo.php'
      },
      {
        id: 'work',
        linkId: 'leave_adminLink',
        url: 'leaveadjustment.php'
      },
      {
        id: 'work',
        linkId: 'my_bookLink',
        url: 'my_book.php'
      },
      {
        id: 'work',
        linkId: 'medistatusLink',
        url: 'medistatus.php'
      },
      {
        id: 'work',
        linkId: 'reimbursementstatusLink',
        url: 'reimbursementstatus.php'
      },
      {
        id: 'work',
        linkId: 'donationinfo_adminLink',
        url: 'donationinfo_admin.php'
      },
      {
        id: 'work',
        linkId: 'pmsLink',
        url: 'pms.php'
      },
      {
        id: 'work',
        linkId: 'amsLink',
        url: 'ams.php'
      },
      {
        id: 'performance',
        linkId: 'my_appraisalLink',
        url: 'my_appraisal.php'
      },
      {
        id: 'performance',
        linkId: 'my_appraisalLink',
        url: 'ipf-management.php'
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
