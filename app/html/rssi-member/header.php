  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
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
              <a class="dropdown-item d-flex align-items-center" href="hrms.php">
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
          <span>Home</span>
        </a>
      </li><!-- End Contact Page Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" id="attendX" href="attendx.php">
          <span>Attendance Portal</span>
        </a>
      </li><!-- End Contact Page Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#acadamis" data-bs-toggle="collapse" href="#">
          <span>Academic</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="acadamis" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a id="examLink" href="exam.php">
              <span>Examination</span>
            </a>
          </li>
          <li>
            <a id="createExam" href="exam_create.php">
              <span>Create Exam</span>
            </a>
          </li>
          <li>
            <a id="examAllotment" href="exam_allotment.php">
              <span>Exam Allotment</span>
            </a>
          </li>
          <li>
            <a id="examSummary" href="exam_summary_report.php">
              <span>Exam Summary Report</span>
            </a>
          </li>
          <li>
            <a id="progress_curve" href="progress_curve.php">
              <span>Students' Progress Curve</span>
            </a>
          </li>
          <li>
            <a id="reexam" href="reexam.php">
              <span>Re-examination Form</span>
            </a>
          </li>
          <li>
            <a id="reexam_record" href="reexam_record.php">
              <span>Re-examination Records</span>
            </a>
          </li>
          <!-- <li>
            <a id="uploadMarks" href="exam_marks_upload.php">
              <span>Upload Marks</span>
            </a>
          </li> -->
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
          <li>
            <a id="allocationLink" href="allocation.php">
              <span>My Allocation</span>
            </a>
          </li>
          <li>
            <a id="policyLink" href="resourcehub.php">
              <span>Resource Hub</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#exceptionPortal" data-bs-toggle="collapse" href="#">
          <span>Exception</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="exceptionPortal" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="raiseException" href="exception-portal.php">
              <span>Raise Exception</span>
            </a>
          </li>
          <li>
            <a id="dashboardException" href="exception_admin.php">
              <span>Exception Dashboard</span>
            </a>
          </li>
        </ul>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#learning" data-bs-toggle="collapse" href="#">
          <span>iExplore Learner</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="learning" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="iexploreLink" href="iexplore.php">
              <span>Courses</span>
            </a>
          </li>
          <li>
            <a id="my_learning" href="my_learning.php">
              <span>My Learnings</span>
            </a>
          </li>
          <!-- <li>
            <a id="libraryLink" href="library.php">
              <span>Libary</span>
            </a>
          </li>-->
          <li>
            <a id="iexplore_admin" href="iexplore_admin.php">
              <span>Modify Course</span>
            </a>
          </li>
          <li>
            <a id="iexplore_defaulters" href="iexplore_defaulters.php">
              <span>Defaulters List</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#iexploreedge" data-bs-toggle="collapse" href="#">
          <span>iExplore Edge</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="iexploreedge" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="exam_management" href="exam_management.php">
              <span>Exam Management</span>
            </a>
          </li>
          <li>
            <a id="add_question" href="add_question.php">
              <span>Add Question</span>
            </a>
          </li>
          <li>
            <a id="question_dashboard" href="question_dashboard.php">
              <span>Question Dashboard</span>
            </a>
          </li>
          <li>
            <a id="manage_category" href="manage_category.php">
              <span>Manage Category</span>
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
            <a id="gems-mart" href="gems-mart.php">
              <span>Gems Mart</span>
            </a>
          </li>
          <li>
            <a id="my-orders" href="my-orders.php">
              <span>My Orders</span>
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
        <a class="nav-link collapsed" data-bs-target="#support360" data-bs-toggle="collapse" href="#">
          <span>Support 360</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="support360" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="create_ticket" href="create_ticket.php">
              <span>Create Ticket</span>
            </a>
          </li>
          <li>
            <a id="ticket_log" href="ticket_log.php">
              <span>Ticket Log</span>
            </a>
          </li>
        </ul>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#stockmanagement" data-bs-toggle="collapse" href="#">
          <span>Stock Management</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="stockmanagement" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="stock_add" href="stock_add.php">
              <span>Add Stock</span>
            </a>
          </li>
          <li>
            <a id="stock_out" href="stock_out.php">
              <span>Distribute Stock</span>
            </a>
          </li>
          <li>
            <a id="stock_in" href="stock_in.php">
              <span>In Stock</span>
            </a>
          </li>
          <li>
            <a id="inventory-insights" href="inventory-insights.php">
              <span>Inventory Insights</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#csu" data-bs-toggle="collapse" href="#">
          <span>Community Supply</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="csu" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="emart" href="emart.php">
              <span>eMart</span>
            </a>
          </li>
          <li>
            <a id="emart_orders" href="emart_orders.php">
              <span>eMart Orders</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#rostermanagement" data-bs-toggle="collapse" href="#">
          <span>Schedule Hub</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="rostermanagement" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="shift_planner" href="shift_planner.php">
              <span>Shift Planner</span>
            </a>
          </li>
          <li>
            <a id="view_shift" href="view_shift.php">
              <span>View Shift</span>
            </a>
          </li>
          <li>
            <a id="closure_assign" href="closure_assign.php">
              <span>Closing Duty Roster</span>
            </a>
          </li>
          <li>
            <a id="student_class_days" href="student_class_days.php">
              <span>Student Class Days</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#survey" data-bs-toggle="collapse" href="#">
          <span>Survey</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="survey" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="create_survey" href="survey.php">
              <span>Create Survey</span>
            </a>
          </li>
          <li>
            <a id="view_survey" href="survey_view.php">
              <span>View Survey Results</span>
            </a>
          </li>
          <li>
            <a id="appointments" href="appointments.php">
              <span>View Appointments</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#peoplePlus" data-bs-toggle="collapse" href="#">
          <span>People Plus</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="peoplePlus" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="talent_pool" href="talent_pool.php">
              <span>Talent Pool</span>
            </a>
          </li>
          <li>
            <a id="interview_central" href="interview_central.php">
              <span>Interview Central</span>
            </a>
          </li>
          <li>
            <a id="rtet" href="rtet.php">
              <span>Create RTET</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#feePortal" data-bs-toggle="collapse" href="#">
          <span>Fee Portal</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="feePortal" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="fee_collection" href="fee_collection.php">
              <span>Fee Collection</span>
            </a>
          </li>
          <li>
            <a id="settlement" href="settlement.php">
              <span>Fee Settlement</span>
            </a>
          </li>
          <li>
            <a id="fee_structure_management" href="fee_structure_management.php">
              <span>Fee Structure Management</span>
            </a>
          </li>
          <li>
            <a id="fee_lock_management" href="fee_lock_management.php">
              <span>Fee Collection Lock Management</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#irc" data-bs-toggle="collapse" href="#">
          <span>IRC</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="irc" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="books" href="books.php">
              <span>Library Dashboard</span>
            </a>
          </li>
          <li>
            <a id="orders" href="book_orders.php">
              <span>Library Orders Management</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#payroll" data-bs-toggle="collapse" href="#">
          <span>Payroll</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="payroll" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="salary_structure" href="salary_structure.php">
              <span>Salary Structure Management</span>
            </a>
          </li>
          <li>
            <a id="payroll_processingLink" href="payroll_processing.php">
              <span>Payroll Processing</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#health_portal" data-bs-toggle="collapse" href="#">
          <span>Health & Wellness Initiatives</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="health_portal" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="salary_structure" href="health_portal.php" target="_blank">
              <span>Student Health Portal</span>
            </a>
          </li>
          <li>
            <a id="community_care" href="community_care.php" target="_blank">
              <span>Community Care Portal</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#worklist" data-bs-toggle="collapse" href="#">
          <span>Worklist</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="worklist" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="hrms_worklist" href="hrms_worklist.php">
              <span>HRMS Worklist</span>
            </a>
          </li>
          <li>
            <a id="post_worklist" href="post_worklist.php">
              <span>Post Worklist</span>
            </a>
          </li>
          <li>
            <a id="iexplore_worklist" href="iexplore_worklist.php">
              <span>iExplore Worklist</span>
            </a>
          </li>
        </ul>
      </li>

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
            <a id="archive_approval" href="archive_approval.php">
              <span>Document Approval</span>
            </a>
          </li>
          <li>
            <a id="bankdetails_admin" href="bankdetails_admin.php">
              <span>HR Banking Records</span>
            </a>
          </li>
          <li>
            <a id="process-hubLink" href="process-hub.php">
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
            <a id="monthly_timesheet" href="monthly_timesheet.php">
              <span>Monthly Timesheet</span>
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
          <li>
            <a id="onexitLink" href="onexit.php">
              <span>OnExit</span>
            </a>
          </li>
          <li>
            <a id="userlogLink" href="userlog.php">
              <span>User log</span>
            </a>
          </li>
          <li>
            <a id="access_panel" href="access_panel.php">
              <span>Access Panel</span>
            </a>
          </li>
          <li>
            <a id="maintenance_panel" href="maintenance_panel.php">
              <span>Maintenance Panel</span>
            </a>
          </li>
        </ul>
      </li><!-- End Forms Nav -->

      <li class="nav-heading">Pages</li>

      <li class="nav-item">
        <a class="nav-link collapsed" id="hrmsLink" href="hrms.php">
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
        id: 'acadamis',
        linkId: 'examLink',
        url: 'exam.php'
      }, {
        id: 'acadamis',
        linkId: 'createExam',
        url: 'exam_create.php'
      },
      {
        id: 'acadamis',
        linkId: 'progress_curve',
        url: 'progress_curve.php'
      },
      {
        id: 'acadamis',
        linkId: 'examAllotment',
        url: 'exam_allotment.php'
      },
      {
        id: 'acadamis',
        linkId: 'examAllotment',
        url: 'exam_marks_upload.php'
      }, {
        id: 'acadamis',
        linkId: 'examSummary',
        url: 'exam_summary_report.php'
      }, {
        id: 'acadamis',
        linkId: 'reexam',
        url: 'reexam.php'
      }, {
        id: 'acadamis',
        linkId: 'reexam_record',
        url: 'reexam_record.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'document.php'
      },
      {
        id: 'myservices',
        linkId: 'allocationLink',
        url: 'allocation.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'my_certificate.php'
      },
      {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'pay_details.php'
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
        linkId: 'my_learning',
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
      }, {
        id: 'learning',
        linkId: 'iexplore_admin',
        url: 'iexplore_admin.php'
      }, {
        id: 'learning',
        linkId: 'iexplore_defaulters',
        url: 'iexplore_defaulters.php'
      }, {
        id: 'worklist',
        linkId: 'iexplore_worklist',
        url: 'iexplore_worklist.php'
      },
      {
        id: 'learning',
        linkId: 'libraryLink',
        url: 'my_book.php'
      },
      {
        id: 'rewards',
        linkId: 'my-orders',
        url: 'my-orders.php'
      }, {
        id: 'rewards',
        linkId: 'gems-mart',
        url: 'gems-mart.php'
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
      }, {
        id: 'work',
        linkId: 'studentLink',
        url: 'admission_admin.php'
      },
      {
        id: 'work',
        linkId: 'studentLink',
        url: 'fees.php'
      },
      {
        id: 'myservices',
        linkId: 'policyLink',
        url: 'resourcehub.php'
      },
      {
        id: 'irc',
        linkId: 'books',
        url: 'books.php'
      },
      {
        id: 'irc',
        linkId: 'orders',
        url: 'book_orders.php'
      },
      {
        id: 'work',
        linkId: 'process-hubLink',
        url: 'process-hub.php'
      }, {
        id: 'work',
        linkId: 'process-hubLink',
        url: 'onboarding.php'
      }, {
        id: 'work',
        linkId: 'process-hubLink',
        url: 'exit.php'
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
      }, {
        id: 'worklist',
        linkId: 'hrms_worklist',
        url: 'hrms_worklist.php'
      }, {
        id: 'worklist',
        linkId: 'post_worklist',
        url: 'post_worklist.php'
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
        linkId: 'monthly_timesheet',
        url: 'monthly_timesheet.php'
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
        id: 'payroll',
        linkId: 'salary_structure',
        url: 'salary_structure.php'
      }, {
        id: 'payroll',
        linkId: 'payroll_processingLink',
        url: 'payroll_processing.php'
      }, {
        id: 'payroll',
        linkId: 'salary_structure',
        url: 'view_structure.php'
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
      },
      {
        id: 'work',
        linkId: 'userlogLink',
        url: 'userlog.php'
      },
      {
        id: 'work',
        linkId: 'process-hubLink',
        url: 'visitor.php'
      }, {
        id: 'work',
        linkId: 'onexitLink',
        url: 'onexit.php'
      }, {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'digital_archive.php'
      }, {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'bankdetails.php'
      }, {
        id: 'work',
        linkId: 'maintenance_panel',
        url: 'maintenance_panel.php'
      }, {
        id: 'work',
        linkId: 'archive_approval',
        url: 'archive_approval.php'
      }, {
        id: 'work',
        linkId: 'bankdetails_admin',
        url: 'bankdetails_admin.php'
      }, {
        id: 'support360',
        linkId: 'create_ticket',
        url: 'create_ticket.php'
      }, {
        id: 'support360',
        linkId: 'ticket_log',
        url: 'ticket_log.php'
      }, {
        id: 'support360',
        linkId: 'ticket_log',
        url: 'ticket-dashboard.php'
      }, {
        id: 'stockmanagement',
        linkId: 'stock_add',
        url: 'stock_add.php'
      }, {
        id: 'stockmanagement',
        linkId: 'stock_out',
        url: 'stock_out.php'
      }, {
        id: 'stockmanagement',
        linkId: 'stock_in',
        url: 'stock_in.php'
      }, {
        id: 'stockmanagement',
        linkId: 'inventory-insights',
        url: 'inventory-insights.php'
      },
      {
        id: 'exceptionPortal',
        linkId: 'raiseException',
        url: 'exception-portal.php'
      },
      {
        id: 'exceptionPortal',
        linkId: 'dashboardException',
        url: 'exception_admin.php'
      },
      {
        id: 'rostermanagement',
        linkId: 'shift_planner',
        url: 'shift_planner.php'
      },
      {
        id: 'rostermanagement',
        linkId: 'view_shift',
        url: 'view_shift.php'
      }, {
        id: 'rostermanagement',
        linkId: 'closure_assign',
        url: 'closure_assign.php'
      }, {
        id: 'rostermanagement',
        linkId: 'student_class_days',
        url: 'student_class_days.php'
      },
      {
        id: 'survey',
        linkId: 'create_survey',
        url: 'survey.php'
      }, {
        id: 'survey',
        linkId: 'appointments',
        url: 'appointments.php'
      },
      {
        id: 'survey',
        linkId: 'view_survey',
        url: 'survey_view.php'
      }, {
        id: 'peoplePlus',
        linkId: 'interview_central',
        url: 'interview_central.php'
      }, {
        id: 'peoplePlus',
        linkId: 'interview_central',
        url: 'technical_interview.php'
      }, {
        id: 'peoplePlus',
        linkId: 'interview_central',
        url: 'hr_interview.php'
      }, {
        id: 'peoplePlus',
        linkId: 'talent_pool',
        url: 'talent_pool.php'
      }, {
        id: 'peoplePlus',
        linkId: 'talent_pool',
        url: 'applicant_profile.php'
      }, {
        id: 'peoplePlus',
        linkId: 'rtet',
        url: 'rtet.php'
      },

      {
        id: 'feePortal',
        linkId: 'fee_collection',
        url: 'fee_collection.php'
      }, {
        id: 'feePortal',
        linkId: 'fee_structure_management',
        url: 'fee_structure_management.php'
      }, {
        id: 'feePortal',
        linkId: 'settlement',
        url: 'settlement.php'
      },
      {
        id: 'feePortal',
        linkId: 'fee_lock_management',
        url: 'fee_lock_management.php'
      },
      {
        id: 'work',
        linkId: 'access_panel',
        url: 'access_panel.php'
      }, {
        id: 'iexploreedge',
        linkId: 'add_question',
        url: 'add_question.php'
      }, {
        id: 'iexploreedge',
        linkId: 'question_dashboard',
        url: 'question_dashboard.php'
      }, {
        id: 'iexploreedge',
        linkId: 'exam_management',
        url: 'exam_management.php'
      }, {
        id: 'iexploreedge',
        linkId: 'manage_category',
        url: 'manage_category.php'
      }, {
        id: 'csu',
        linkId: 'emart',
        url: 'emart.php'
      }, {
        id: 'csu',
        linkId: 'emart_orders',
        url: 'emart_orders.php'
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
    toggleCollapsedClass('polls.php', 'homeLink');
    toggleCollapsedClass('hrms.php', 'hrmsLink');
    toggleCollapsedClass('scan.php', 'attendX');
    toggleCollapsedClass('in_out_tracker.php', 'attendX');
    toggleCollapsedClass('monthly_attd_report.php', 'attendX');
    toggleCollapsedClass('monthly_attd_report_associate.php', 'attendX');
    toggleCollapsedClass('attendance-analytics.php', 'attendX');
    toggleCollapsedClass('remote_attendance.php', 'attendX');
    toggleCollapsedClass('attendx.php', 'attendX');
  </script>