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
              <span>
                Role : <?php echo $role ?>
                <?php if (count($active_roles) > 1) { ?>
                  <a href="#" data-bs-toggle="modal" data-bs-target="#switchRoleModal" class="ms-2">
                    <i class="bi bi-arrow-repeat text-muted" title="Switch to another role"></i>
                  </a>
                <?php } ?>
              </span>
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
              <a class="dropdown-item d-flex align-items-center" href="setup_2fa.php">
                <i class="bi bi-person-lock"></i>
                <span>Enable 2FA</span>
              </a>
            </li>
            <hr class="dropdown-divider">
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

  <!-- Switch Role Modal -->
  <div class="modal fade" id="switchRoleModal" tabindex="-1">
    <div class="modal-dialog">
      <form id="switchRoleForm" class="modal-content" method="POST" action="switch_role.php">
        <div class="modal-header">
          <h5 class="modal-title">Switch Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <label class="form-label">Select a Role</label>
          <select name="new_role" id="roleDropdown" class="form-select" required>
            <option value="">Loading...</option>
          </select>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button id="updateBtn" class="btn btn-primary" type="submit" disabled>
            <span class="button-text">Update Role</span>
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

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
            <a id="exam-management" href="exam-management.php">
              <span>Exam Management</span>
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
          <li>
            <a id="group_management" href="group_management.php">
              <span>Stock Item Group Management</span>
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
          <li>
            <a id="exception_view" href="exception_view.php">
              <span>Class Days Exceptions</span>
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
          <li>
            <a id="enquiry_portal" href="enquiry_portal.php">
              <span>Enquiry Portal</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#job" data-bs-toggle="collapse" href="#">
          <span>Job Assistance</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="job" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="job_seekers" href="job_seekers.php">
              <span>Job Seeker Records</span>
            </a>
          </li>
          <li>
            <a id="job-admin" href="job-admin.php">
              <span>Job Admin Panel</span>
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
            <a id="concession_list" href="concession_list.php">
              <span>Student Concessions</span>
            </a>
          </li>
          <li>
            <a id="settlement" href="settlement.php">
              <span>Fee Settlement</span>
            </a>
          </li>
          <li>
            <a id="fee_payments_report" href="fee_payments_report.php">
              <span>Fee Payments Report</span>
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
        <a class="nav-link collapsed" data-bs-target="#alliance_portal" data-bs-toggle="collapse" href="#">
          <span>Alliance Portal</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="alliance_portal" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="contact-directory" href="contact-directory.php">
              <span>Contact Directory</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icom" data-bs-toggle="collapse" href="#">
          <span>ID Card Order Management (ICOM)</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icom" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="icom_manage_order" href="icom.php">
              <span>Order Management</span>
            </a>
          </li>
          <li>
            <a id="icom_order_history" href="id_history.php">
              <span>Order History</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#lms" data-bs-toggle="collapse" href="#">
          <span>Leave Management System</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="lms" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="leave_approval" href="leave_approval.php">
              <span>Leave Approval</span>
            </a>
          </li>
          <li>
            <a id="leave_admin" href="leave_admin.php">
              <span>Leave Admin</span>
            </a>
          </li>
        </ul>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#gps" data-bs-toggle="collapse" href="#">
          <span>GPS</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="gps" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a id="my-asset" href="gps.php">
              <span>My Assets</span>
            </a>
          </li>
          <li>
            <a id="asset-management" href="asset-management.php">
              <span>Asset Management</span>
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
            <a id="student_attrition" href="student_attrition.php">
              <span>Student Attrition</span>
            </a>
          </li>
          <li>
            <a id="distribution_analytics" href="distribution_analytics.php">
              <span>Distribution Analytics</span>
            </a>
          </li>
          <li>
            <a id="facility-hygiene-monitoring" href="facility-hygiene-monitoring.php">
              <span>Facility Hygiene Monitoring</span>
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
            <a id="facultyLink" href="faculty.php">
              <span>RSSI Faculty</span>
            </a>
          </li>
          <li>
            <a id="monthly_timesheet" href="monthly_timesheet.php">
              <span>Monthly Timesheet</span>
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
            <a id="admin_role_management" href="admin_role_management.php">
              <span>RMC</span>
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
        id: 'alliance_portal',
        linkId: 'contact-directory',
        url: 'contact-directory.php'
      }, {
        id: 'acadamis',
        linkId: 'append-students',
        url: 'append-students.php'
      }, {
        id: 'acadamis',
        linkId: 'result-scheduler',
        url: 'result-scheduler.php'
      }, {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'exam_create.php'
      },
      {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'progress_curve.php'
      },
      {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'exam_allotment.php'
      },
      {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'exam_marks_upload.php'
      }, {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'exam_summary_report.php'
      }, {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'reexam.php'
      }, {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'reexam_record.php'
      }, {
        id: 'acadamis',
        linkId: 'exam-management',
        url: 'exam-management.php'
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
      }, {
        id: 'myservices',
        linkId: 'documentLink',
        url: 'old_payslip.php'
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
      }, {
        id: 'job',
        linkId: 'job-admin',
        url: 'job-approval.php'
      }, {
        id: 'job',
        linkId: 'job-admin',
        url: 'job-admin.php'
      }, {
        id: 'job',
        linkId: 'job-admin',
        url: 'job_view.php'
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
      },
      {
        id: 'icom',
        linkId: 'icom_manage_order',
        url: 'icom.php'
      }, {
        id: 'work',
        linkId: 'student_attrition',
        url: 'student_attrition.php'
      }, {
        id: 'work',
        linkId: 'distribution_analytics',
        url: 'distribution_analytics.php'
      },
      {
        id: 'icom',
        linkId: 'icom_order_history',
        url: 'id_history.php'
      }, {
        id: 'work',
        linkId: 'facility-hygiene-monitoring',
        url: 'facility-hygiene-monitoring.php'
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
        id: 'gps',
        linkId: 'my-asset',
        url: 'gps.php'
      }, {
        id: 'gps',
        linkId: 'asset-management',
        url: 'scan-asset.php'
      }, {
        id: 'gps',
        linkId: 'asset-management',
        url: 'admin_change_requests.php'
      }, {
        id: 'gps',
        linkId: 'asset-management',
        url: 'get_request_details.php'
      }, {
        id: 'gps',
        linkId: 'asset-management',
        url: 'asset-management.php'
      }, {
        id: 'gps',
        linkId: 'gps',
        url: 'gps_history.php'
      }, {
        id: 'gps',
        linkId: 'asset-management',
        url: 'asset_verification_report.php'
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
        id: 'lms',
        linkId: 'leave_admin',
        url: 'leave_admin.php'
      }, {
        id: 'lms',
        linkId: 'leave_approval',
        url: 'leave_approval.php'
      },
      {
        id: 'myservices',
        linkId: 'leaveLink',
        url: 'leaveallo.php'
      },
      {
        id: 'myservices',
        linkId: 'leaveLink',
        url: 'leaveadjustment.php'
      },
      {
        id: 'work',
        linkId: 'monthly_timesheet',
        url: 'monthly_timesheet.php'
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
      }, {
        id: 'work',
        linkId: 'admin_role_management',
        url: 'admin_role_management.php'
      }, {
        id: 'work',
        linkId: 'admin_role_management',
        url: 'admin_role_audit.php'
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
      }, {
        id: 'performance',
        linkId: 'my_appraisalLink',
        url: 'manager_response.php'
      },
      {
        id: 'performance',
        linkId: 'my_appraisalLink',
        url: 'appraisee_response.php'
      },
      {
        id: 'performance',
        linkId: 'my_appraisalLink',
        url: 'reviewer_response.php'
      }, {
        id: 'performance',
        linkId: 'my_appraisalLink',
        url: 'process.php'
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
      }, {
        id: 'stockmanagement',
        linkId: 'group_management',
        url: 'group_management.php'
      }, {
        id: 'stockmanagement',
        linkId: 'group_management',
        url: 'edit_group.php'
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
      }, {
        id: 'rostermanagement',
        linkId: 'exception_view',
        url: 'exception_view.php'
      }, {
        id: 'rostermanagement',
        linkId: 'exception_view',
        url: 'class_days_exception.php'
      },
      {
        id: 'survey',
        linkId: 'create_survey',
        url: 'survey.php'
      }, {
        id: 'survey',
        linkId: 'appointments',
        url: 'appointments.php'
      }, {
        id: 'survey',
        linkId: 'enquiry_portal',
        url: 'enquiry_portal.php'
      }, {
        id: 'job',
        linkId: 'job_seekers',
        url: 'job_seekers.php'
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
      },
      {
        id: 'feePortal',
        linkId: 'concession_list',
        url: 'concession_list.php'
      }, {
        id: 'feePortal',
        linkId: 'fee_structure_management',
        url: 'fee_structure_management.php'
      }, {
        id: 'feePortal',
        linkId: 'settlement',
        url: 'settlement.php'
      }, {
        id: 'feePortal',
        linkId: 'fee_payments_report',
        url: 'fee_payments_report.php'
      },
      {
        id: 'feePortal',
        linkId: 'fee_lock_management',
        url: 'fee_lock_management.php'
      }, {
        id: 'work',
        linkId: 'studentLink',
        url: 'sps.php'
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
    toggleCollapsedClass('create_event.php', 'homeLink');
    toggleCollapsedClass('edit_event.php', 'homeLink');
    toggleCollapsedClass('polls.php', 'homeLink');
    toggleCollapsedClass('hrms.php', 'hrmsLink');
    toggleCollapsedClass('scan.php', 'attendX');
    toggleCollapsedClass('in_out_tracker.php', 'attendX');
    toggleCollapsedClass('monthly_attd_report.php', 'attendX');
    toggleCollapsedClass('monthly_attd_report_associate.php', 'attendX');
    toggleCollapsedClass('attendance-analytics.php', 'attendX');
    toggleCollapsedClass('remote_attendance.php', 'attendX');
    toggleCollapsedClass('attendx.php', 'attendX');
    toggleCollapsedClass('sas.php', 'attendX');
  </script>
  <script>
    document.getElementById('switchRoleModal').addEventListener('show.bs.modal', function() {

      const dropdown = document.getElementById('roleDropdown');
      const updateBtn = document.getElementById('updateBtn');

      dropdown.innerHTML = "<option value=''>Loading...</option>";
      updateBtn.disabled = true;

      fetch('fetch_roles.php')
        .then(response => response.json())
        .then(data => {

          dropdown.innerHTML = ""; // clear dropdown

          // If NO current role → show "Select Role"
          if (!data.current_role) {
            const defaultOption = document.createElement("option");
            defaultOption.value = "";
            defaultOption.textContent = "Select Role";
            dropdown.appendChild(defaultOption);
          }

          // Populate roles dynamically
          data.roles.forEach(role => {
            const option = document.createElement("option");
            option.value = role.id;
            option.textContent = role.role_name;

            // Auto-select current role by name
            if (role.role_name === data.current_role) {
              option.selected = true;
            }

            dropdown.appendChild(option);
          });

          updateBtn.disabled = false;
        })
        .catch(err => {
          dropdown.innerHTML = "<option>Error loading roles</option>";
          updateBtn.disabled = true;
          console.error("Error fetching roles:", err);
        });

    });
  </script>

  <script>
    document.getElementById("switchRoleForm").addEventListener("submit", function(e) {
      e.preventDefault(); // Prevent page reload

      const updateBtn = document.getElementById("updateBtn");
      const buttonText = updateBtn.querySelector(".button-text");
      const spinner = updateBtn.querySelector(".spinner-border");

      // Show loading state
      updateBtn.disabled = true;
      buttonText.textContent = "Updating...";
      spinner.classList.remove("d-none");

      const formData = new FormData(this);

      fetch("switch_role.php", {
          method: "POST",
          body: formData
        })
        .then(r => r.json())
        .then(res => {
          alert(res.message);

          if (res.status === "success") {
            location.reload(); // Only reload on success
          }
        })
        .catch(() => {
          alert("Something went wrong!");
        })
        .finally(() => {
          // Reset button state (in case of error or if page doesn't reload)
          updateBtn.disabled = false;
          buttonText.textContent = "Update Role";
          spinner.classList.add("d-none");
        });
    });
  </script>
  <script>
    document.addEventListener("click", function(e) {
      const link = e.target.closest("a");
      if (!link) return;

      const currentDomain = window.location.hostname;

      try {
        const linkUrl = new URL(link.href, window.location.origin);

        // Ignore javascript links
        if (link.href.startsWith("javascript:")) return;

        // Only external links
        if (linkUrl.hostname && linkUrl.hostname !== currentDomain) {
          e.preventDefault();

          const confirmLeave = confirm(
            "You are being redirected to an external website. Do you want to continue?"
          );

          if (confirmLeave) {
            window.open(link.href, "_blank");
          }
        }
      } catch (err) {}
    });
  </script>

  <script>
    // Add this script after your existing header scripts
    document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.querySelector('.search-form');
      const searchInput = searchForm.querySelector('input[name="query"]');

      if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
          e.preventDefault();
          performSearch(searchInput.value.trim());
        });

        // Optional: Add real-time search as user types
        searchInput.addEventListener('input', function() {
          if (this.value.length >= 2) {
            highlightMenuItems(this.value);
          } else {
            clearHighlights();
          }
        });
      }

      // Function to highlight menu items matching search
      function highlightMenuItems(searchTerm) {
        clearHighlights();

        if (!searchTerm) return;

        const searchLower = searchTerm.toLowerCase();
        const navItems = document.querySelectorAll('#sidebar-nav a.nav-link, #sidebar-nav .nav-content a');

        navItems.forEach(item => {
          const text = item.textContent.toLowerCase();
          const parentLi = item.closest('li.nav-item');

          if (text.includes(searchLower)) {
            // Highlight the item
            item.style.backgroundColor = '#fff3cd';
            item.style.fontWeight = 'bold';

            // Expand parent collapse if collapsed
            const parentCollapse = item.closest('.collapse');
            if (parentCollapse && !parentCollapse.classList.contains('show')) {
              parentCollapse.classList.add('show');
            }

            // Scroll to item
            if (parentLi) {
              parentLi.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
              });
            }
          }
        });
      }

      function clearHighlights() {
        const navItems = document.querySelectorAll('#sidebar-nav a.nav-link, #sidebar-nav .nav-content a');
        navItems.forEach(item => {
          item.style.backgroundColor = '';
          item.style.fontWeight = '';
        });
      }

      // Function to navigate to first matching page
      function performSearch(searchTerm) {
        if (!searchTerm) return;

        const searchLower = searchTerm.toLowerCase();
        const navLinks = document.querySelectorAll('#sidebar-nav a[href]:not([href="#"])');

        let firstMatch = null;

        navLinks.forEach(link => {
          const text = link.textContent.toLowerCase();
          const href = link.getAttribute('href');

          if (text.includes(searchLower) && href) {
            if (!firstMatch) firstMatch = link;
          }
        });

        if (firstMatch) {
          window.location.href = firstMatch.getAttribute('href');
        } else {
          alert('No matching menu items found.');
        }
      }
    });
  </script>