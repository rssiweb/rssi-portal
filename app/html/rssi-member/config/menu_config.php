<?php
// config/menu_config.php

class MenuConfig
{
    private static $pageData = null;
    private static $menuStructure = null;
    private static $roleAccessCache = null;
    private static $publicPagesCache = null; // NEW: Cache for pages with no true access for any role
    private static $allPagesInDB = null; // NEW: Cache for all pages that exist in DB

    private static function init()
    {
        if (self::$pageData !== null) return;

        // Define groups by link_id
        $linkIdGroups = [
            // ============ TOP LEVEL PAGES ============
            'homeLink' => [
                'section' => '',
                'remove_collapsed' => true,
                'pages' => [
                    'home.php' => ['title' => 'Home', 'show_in_menu' => true]
                ],
                'descriptions' => [
                    'home.php' => 'Phoenix Portal dashboard overview and quick access.'
                ]
            ],

            'attendX' => [
                'section' => 'Attendance Portal',
                'remove_collapsed' => true,
                'pages' => [
                    'attendx.php' => ['title' => 'Attendance Portal', 'show_in_menu' => true],
                    'scan.php' => ['title' => 'Scan Attendance', 'show_in_menu' => false],
                    'in_out_tracker.php' => ['title' => 'In-Out Tracker', 'show_in_menu' => false],
                    'monthly_attd_report.php' => ['title' => 'Monthly Attendance Report', 'show_in_menu' => false],
                    'monthly_attd_report_associate.php' => ['title' => 'Monthly Associate Report', 'show_in_menu' => false],
                    'attendance-analytics.php' => ['title' => 'Attendance Analytics', 'show_in_menu' => false],
                    'remote_attendance.php' => ['title' => 'Remote Attendance', 'show_in_menu' => false],
                    'sas.php' => ['title' => 'SAS', 'show_in_menu' => false]
                ]
            ],

            'hrmsLink' => [
                'section' => 'Profile',
                'remove_collapsed' => true,
                'pages' => [
                    'hrms.php' => ['title' => 'Profile', 'show_in_menu' => false],
                    'resetpassword.php' => ['title' => 'Reset Password', 'show_in_menu' => false],
                    'setup_2fa.php' => ['title' => 'Enable 2FA', 'show_in_menu' => false]
                ]
            ],

            // ============ ACADEMIC SECTION ============
            'examLink' => [
                'section' => 'Academic',
                'sidebar_id' => 'acadamis',
                'pages' => [
                    'exam.php' => ['title' => 'Examination', 'show_in_menu' => true]
                ]
            ],

            'exam-management' => [
                'section' => 'Academic',
                'sidebar_id' => 'acadamis',
                'pages' => [
                    'exam-management.php' => ['title' => 'Exam Management', 'show_in_menu' => true],
                    'append-students.php' => ['title' => 'Append Students', 'show_in_menu' => false],
                    'result-scheduler.php' => ['title' => 'Result Scheduler', 'show_in_menu' => false],
                    'exam_create.php' => ['title' => 'Create Exam', 'show_in_menu' => false],
                    'progress_curve.php' => ['title' => 'Progress Curve', 'show_in_menu' => false],
                    'exam_allotment.php' => ['title' => 'Exam Allotment', 'show_in_menu' => false],
                    'exam_marks_upload.php' => ['title' => 'Upload Exam Marks', 'show_in_menu' => false],
                    'exam_summary_report.php' => ['title' => 'Exam Summary Report', 'show_in_menu' => false],
                    'reexam.php' => ['title' => 'Re-exam', 'show_in_menu' => false],
                    'reexam_record.php' => ['title' => 'Re-exam Records', 'show_in_menu' => false]
                ]
            ],

            // ============ MY SERVICES SECTION ============
            'leaveLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'leave.php' => ['title' => 'Apply for Leave', 'show_in_menu' => true],
                    'leaveallo.php' => ['title' => 'Leave Allocation', 'show_in_menu' => false],
                    'leaveadjustment.php' => ['title' => 'Leave Adjustment', 'show_in_menu' => false]
                ]
            ],

            'documentLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'document.php' => ['title' => 'My Document', 'show_in_menu' => true],
                    'my_certificate.php' => ['title' => 'My Certificate', 'show_in_menu' => false],
                    'pay_details.php' => ['title' => 'Pay Details', 'show_in_menu' => false],
                    'old_payslip.php' => ['title' => 'Old Payslip', 'show_in_menu' => false],
                    'digital_archive.php' => ['title' => 'Digital Archive', 'show_in_menu' => false],
                    'bankdetails.php' => ['title' => 'Bank Details', 'show_in_menu' => false]
                ]
            ],

            'allocationLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'allocation.php' => ['title' => 'My Allocation', 'show_in_menu' => true]
                ]
            ],

            'policyLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'resourcehub.php' => ['title' => 'Resource Hub', 'show_in_menu' => true]
                ]
            ],

            // ============ EXCEPTION PORTAL ============
            'raiseException' => [
                'section' => 'Exception',
                'sidebar_id' => 'exceptionPortal',
                'pages' => [
                    'exception-portal.php' => ['title' => 'Raise Exception', 'show_in_menu' => true]
                ]
            ],

            'dashboardException' => [
                'section' => 'Exception',
                'sidebar_id' => 'exceptionPortal',
                'pages' => [
                    'exception_admin.php' => ['title' => 'Exception Dashboard', 'show_in_menu' => true]
                ]
            ],

            // ============ IEXPLORE LEARNER ============
            'iexploreLink' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'iexplore.php' => ['title' => 'Courses', 'show_in_menu' => true]
                ]
            ],

            'my_learning' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'my_learning.php' => ['title' => 'My Learnings', 'show_in_menu' => true]
                ]
            ],

            'viscoLink' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'visco.php' => ['title' => 'Visco', 'show_in_menu' => false],
                    'library.php' => ['title' => 'Library', 'show_in_menu' => false],
                    'my_book.php' => ['title' => 'My Book', 'show_in_menu' => false]
                ]
            ],

            'iexplore_admin' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'iexplore_admin.php' => ['title' => 'Modify Course', 'show_in_menu' => true]
                ]
            ],

            'iexplore_defaulters' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'iexplore_defaulters.php' => ['title' => 'Defaulters List', 'show_in_menu' => true]
                ]
            ],

            // ============ IEXPLORE EDGE ============
            'exam_management' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'exam_management.php' => ['title' => 'Exam Management', 'show_in_menu' => true]
                ]
            ],

            'add_question' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'add_question.php' => ['title' => 'Add Question', 'show_in_menu' => true]
                ]
            ],

            'question_dashboard' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'question_dashboard.php' => ['title' => 'Question Dashboard', 'show_in_menu' => true]
                ]
            ],

            'manage_category' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'manage_category.php' => ['title' => 'Manage Category', 'show_in_menu' => true]
                ]
            ],

            // ============ PERFORMANCE MANAGEMENT ============
            'my_appraisalLink' => [
                'section' => 'Performance Management',
                'sidebar_id' => 'performance',
                'pages' => [
                    'my_appraisal.php' => ['title' => 'My Appraisal', 'show_in_menu' => true],
                    'ipf-management.php' => ['title' => 'IPF Management', 'show_in_menu' => false],
                    'manager_response.php' => ['title' => 'Manager Response', 'show_in_menu' => false],
                    'appraisee_response.php' => ['title' => 'Appraisee Response', 'show_in_menu' => false],
                    'reviewer_response.php' => ['title' => 'Reviewer Response', 'show_in_menu' => false],
                    'process.php' => ['title' => 'Process', 'show_in_menu' => false]
                ]
            ],

            // ============ REWARDS & RECOGNITION ============
            'gems-mart' => [
                'section' => 'Rewards & Recognition',
                'sidebar_id' => 'rewards',
                'pages' => [
                    'gems-mart.php' => ['title' => 'Gems Mart', 'show_in_menu' => true]
                ]
            ],

            'my-orders' => [
                'section' => 'Rewards & Recognition',
                'sidebar_id' => 'rewards',
                'pages' => [
                    'my-orders.php' => ['title' => 'My Orders', 'show_in_menu' => true]
                ]
            ],

            // ============ CLAIMS AND ADVANCES ============
            'reimbursementLink' => [
                'section' => 'Claims and Advances',
                'sidebar_id' => 'claims',
                'pages' => [
                    'reimbursement.php' => ['title' => 'Reimbursement', 'show_in_menu' => true],
                    'reimbursementstatus.php' => ['title' => 'Reimbursement Status', 'show_in_menu' => false]
                ]
            ],

            'medimateLink' => [
                'section' => 'Claims and Advances',
                'sidebar_id' => 'claims',
                'pages' => [
                    'medimate.php' => ['title' => 'Medimate', 'show_in_menu' => false]
                ]
            ],

            // ============ SUPPORT 360 ============
            'create_ticket' => [
                'section' => 'Support 360',
                'sidebar_id' => 'support360',
                'pages' => [
                    'create_ticket.php' => ['title' => 'Create Ticket', 'show_in_menu' => true]
                ]
            ],

            'ticket_log' => [
                'section' => 'Support 360',
                'sidebar_id' => 'support360',
                'pages' => [
                    'ticket_log.php' => ['title' => 'Ticket Log', 'show_in_menu' => true],
                    'ticket-dashboard.php' => ['title' => 'Ticket Dashboard', 'show_in_menu' => false]
                ]
            ],

            // ============ STOCK MANAGEMENT ============

            'stockmanagementLink' => [
                'section' => 'Stock Management',
                'remove_collapsed' => true,
                'pages' => [
                    'stock_management.php' => ['title' => 'Stock Management', 'show_in_menu' => true],
                    'stock_add.php' => ['title' => 'Add Stock', 'show_in_menu' => false],
                    'stock_out.php' => ['title' => 'Stock Out', 'show_in_menu' => false],
                    'items_management.php' => ['title' => 'Items Management', 'show_in_menu' => false],
                    'item_prices_management.php' => ['title' => 'Item Prices Management', 'show_in_menu' => false],
                    'units_management.php' => ['title' => 'Units Management', 'show_in_menu' => false],
                    'stock_in.php' => ['title' => 'Stock In', 'show_in_menu' => false],
                    'inventory-insights.php' => ['title' => 'Inventory Insights', 'show_in_menu' => false],
                    'group_management.php' => ['title' => 'Group Management', 'show_in_menu' => false],
                    'edit_group.php' => ['title' => 'Edit Group', 'show_in_menu' => false]
                ]
            ],

            // ============ COMMUNITY SUPPLY ============
            'emart' => [
                'section' => 'Community Supply',
                'sidebar_id' => 'csu',
                'pages' => [
                    'emart.php' => ['title' => 'eMart', 'show_in_menu' => true]
                ]
            ],

            'emart_orders' => [
                'section' => 'Community Supply',
                'sidebar_id' => 'csu',
                'pages' => [
                    'emart_orders.php' => ['title' => 'eMart Orders', 'show_in_menu' => true]
                ]
            ],

            // ============ SCHEDULE HUB ============
            'shift_planner' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'shift_planner.php' => ['title' => 'Shift Planner', 'show_in_menu' => true]
                ]
            ],

            'view_shift' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'view_shift.php' => ['title' => 'View Shift', 'show_in_menu' => true]
                ]
            ],

            'closure_assign' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'closure_assign.php' => ['title' => 'Closing Duty Roster', 'show_in_menu' => true]
                ]
            ],

            'student_class_days' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'student_class_days.php' => ['title' => 'Student Class Days', 'show_in_menu' => true]
                ]
            ],

            'exception_view' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'exception_view.php' => ['title' => 'Class Days Exceptions', 'show_in_menu' => true],
                    'class_days_exception.php' => ['title' => 'Class Days Exception', 'show_in_menu' => false]
                ]
            ],

            // ============ SURVEY ============
            'create_survey' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'survey.php' => ['title' => 'Create Survey', 'show_in_menu' => true]
                ]
            ],

            'view_survey' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'survey_view.php' => ['title' => 'View Survey Results', 'show_in_menu' => true]
                ]
            ],

            'appointments' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'appointments.php' => ['title' => 'View Appointments', 'show_in_menu' => true]
                ]
            ],

            'enquiry_portal' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'enquiry_portal.php' => ['title' => 'Enquiry Portal', 'show_in_menu' => true]
                ]
            ],

            // ============ JOB ASSISTANCE ============
            'job_seekers' => [
                'section' => 'Job Assistance',
                'sidebar_id' => 'job',
                'pages' => [
                    'job_seekers.php' => ['title' => 'Job Seeker Records', 'show_in_menu' => true]
                ]
            ],

            'job-admin' => [
                'section' => 'Job Assistance',
                'sidebar_id' => 'job',
                'pages' => [
                    'job-admin.php' => ['title' => 'Job Admin Panel', 'show_in_menu' => true],
                    'job-approval.php' => ['title' => 'Job Approval', 'show_in_menu' => false],
                    'recruiter-management.php' => ['title' => 'Recruiter Management', 'show_in_menu' => false],
                    'recruiter-add.php' => ['title' => 'Add Recruiter', 'show_in_menu' => false],
                    'job-add.php' => ['title' => 'Add Job', 'show_in_menu' => false],
                    'job_view.php' => ['title' => 'Job View', 'show_in_menu' => false]
                ]
            ],

            // ============ PEOPLE PLUS ============
            'talent_pool' => [
                'section' => 'People Plus',
                'sidebar_id' => 'peoplePlus',
                'pages' => [
                    'talent_pool.php' => ['title' => 'Talent Pool', 'show_in_menu' => true],
                    'applicant_profile.php' => ['title' => 'Applicant Profile', 'show_in_menu' => false]
                ]
            ],

            'interview_central' => [
                'section' => 'People Plus',
                'sidebar_id' => 'peoplePlus',
                'pages' => [
                    'interview_central.php' => ['title' => 'Interview Central', 'show_in_menu' => true],
                    'technical_interview.php' => ['title' => 'Technical Interview', 'show_in_menu' => false],
                    'hr_interview.php' => ['title' => 'HR Interview', 'show_in_menu' => false]
                ]
            ],

            'rtet' => [
                'section' => 'People Plus',
                'sidebar_id' => 'peoplePlus',
                'pages' => [
                    'rtet.php' => ['title' => 'Create RTET', 'show_in_menu' => true]
                ]
            ],

            // ============ FEE PORTAL ============
            'fee_collection' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_collection.php' => ['title' => 'Fee Collection', 'show_in_menu' => true]
                ]
            ],

            'concession_list' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'concession_list.php' => ['title' => 'Student Concessions', 'show_in_menu' => true]
                ]
            ],

            'settlement' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'settlement.php' => ['title' => 'Fee Settlement', 'show_in_menu' => true]
                ]
            ],

            'fee_payments_report' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_payments_report.php' => ['title' => 'Fee Payments Report', 'show_in_menu' => true]
                ]
            ],

            'fee_structure_management' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_structure_management.php' => ['title' => 'Fee Structure Management', 'show_in_menu' => true]
                ]
            ],

            'fee_lock_management' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_lock_management.php' => ['title' => 'Fee Collection Lock Management', 'show_in_menu' => true]
                ]
            ],

            // ============ IRC ============
            'books' => [
                'section' => 'IRC',
                'sidebar_id' => 'irc',
                'pages' => [
                    'books.php' => ['title' => 'Library Dashboard', 'show_in_menu' => true]
                ]
            ],

            'orders' => [
                'section' => 'IRC',
                'sidebar_id' => 'irc',
                'pages' => [
                    'book_orders.php' => ['title' => 'Library Orders Management', 'show_in_menu' => true]
                ]
            ],

            // ============ PAYROLL ============
            'salary_structure' => [
                'section' => 'Payroll',
                'sidebar_id' => 'payroll',
                'pages' => [
                    'salary_structure.php' => ['title' => 'Salary Structure Management', 'show_in_menu' => true],
                    'view_structure.php' => ['title' => 'View Structure', 'show_in_menu' => false]
                ]
            ],

            'payroll_processingLink' => [
                'section' => 'Payroll',
                'sidebar_id' => 'payroll',
                'pages' => [
                    'payroll_processing.php' => ['title' => 'Payroll Processing', 'show_in_menu' => true]
                ]
            ],

            // ============ HEALTH PORTAL ============
            'salary_structure_health' => [
                'section' => 'Health & Wellness Initiatives',
                'sidebar_id' => 'health_portal',
                'pages' => [
                    'health_portal.php' => ['title' => 'Student Health Portal', 'show_in_menu' => true]
                ]
            ],

            'community_care' => [
                'section' => 'Health & Wellness Initiatives',
                'sidebar_id' => 'health_portal',
                'pages' => [
                    'community_care.php' => ['title' => 'Community Care Portal', 'show_in_menu' => true]
                ]
            ],

            // ============ WORKLIST ============
            'hrms_worklist' => [
                'section' => 'Worklist',
                'sidebar_id' => 'worklist',
                'pages' => [
                    'hrms_worklist.php' => ['title' => 'HRMS Worklist', 'show_in_menu' => true]
                ]
            ],

            'post_worklist' => [
                'section' => 'Worklist',
                'sidebar_id' => 'worklist',
                'pages' => [
                    'post_worklist.php' => ['title' => 'Post Worklist', 'show_in_menu' => true]
                ]
            ],

            'iexplore_worklist' => [
                'section' => 'Worklist',
                'sidebar_id' => 'worklist',
                'pages' => [
                    'iexplore_worklist.php' => ['title' => 'iExplore Worklist', 'show_in_menu' => true]
                ]
            ],

            // ============ ALLIANCE PORTAL ============
            'contact-directory' => [
                'section' => 'Alliance Portal',
                'sidebar_id' => 'alliance_portal',
                'pages' => [
                    'contact-directory.php' => ['title' => 'Contact Directory', 'show_in_menu' => true]
                ]
            ],

            // ============ ICOM ============
            'icom_manage_order' => [
                'section' => 'ID Card Order Management',
                'sidebar_id' => 'icom',
                'pages' => [
                    'icom.php' => ['title' => 'Order Management', 'show_in_menu' => true]
                ]
            ],

            'icom_order_history' => [
                'section' => 'ID Card Order Management',
                'sidebar_id' => 'icom',
                'pages' => [
                    'id_history.php' => ['title' => 'Order History', 'show_in_menu' => true]
                ]
            ],

            // ============ LEAVE MANAGEMENT ============
            'leave_approval' => [
                'section' => 'Leave Management System',
                'sidebar_id' => 'lms',
                'pages' => [
                    'leave_approval.php' => ['title' => 'Leave Approval', 'show_in_menu' => true]
                ]
            ],

            'leave_admin' => [
                'section' => 'Leave Management System',
                'sidebar_id' => 'lms',
                'pages' => [
                    'leave_admin.php' => ['title' => 'Leave Admin', 'show_in_menu' => true]
                ]
            ],

            // ============ GPS ============
            'my-asset' => [
                'section' => 'GPS',
                'sidebar_id' => 'gps',
                'pages' => [
                    'gps.php' => ['title' => 'My Assets', 'show_in_menu' => true]
                ]
            ],

            'asset-management' => [
                'section' => 'GPS',
                'sidebar_id' => 'gps',
                'pages' => [
                    'asset-management.php' => ['title' => 'Asset Management', 'show_in_menu' => true],
                    'scan-asset.php' => ['title' => 'Scan Asset', 'show_in_menu' => false],
                    'admin_change_requests.php' => ['title' => 'Admin Change Requests', 'show_in_menu' => false],
                    'get_request_details.php' => ['title' => 'Get Request Details', 'show_in_menu' => false],
                    'gps_history.php' => ['title' => 'GPS History', 'show_in_menu' => false],
                    'asset_verification_report.php' => ['title' => 'Asset Verification Report', 'show_in_menu' => false]
                ]
            ],

            // ============ WORK SECTION ============
            'studentLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'student.php' => ['title' => 'RSSI Student', 'show_in_menu' => true],
                    'admission_admin.php' => ['title' => 'Admission Admin', 'show_in_menu' => false],
                    'fees.php' => ['title' => 'Fees', 'show_in_menu' => false],
                    'sps.php' => ['title' => 'SPS', 'show_in_menu' => false]
                ]
            ],

            'student_attrition' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'student_attrition.php' => ['title' => 'Student Attrition', 'show_in_menu' => true]
                ]
            ],

            'distribution_analytics' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'distribution_analytics.php' => ['title' => 'Distribution Analytics', 'show_in_menu' => true]
                ]
            ],

            'facility-hygiene-monitoring' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'facility-hygiene-monitoring.php' => ['title' => 'Facility Hygiene Monitoring', 'show_in_menu' => true]
                ]
            ],

            'archive_approval' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'archive_approval.php' => ['title' => 'Document Approval', 'show_in_menu' => true]
                ]
            ],

            'bankdetails_admin' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'bankdetails_admin.php' => ['title' => 'HR Banking Records', 'show_in_menu' => true]
                ]
            ],

            'process-hubLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'process-hub.php' => ['title' => 'Process Hub', 'show_in_menu' => true],
                    'onboarding.php' => ['title' => 'Onboarding', 'show_in_menu' => false],
                    'exit.php' => ['title' => 'Exit', 'show_in_menu' => false],
                    'visitor.php' => ['title' => 'Visitor', 'show_in_menu' => false]
                ]
            ],

            'facultyLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'faculty.php' => ['title' => 'RSSI Faculty', 'show_in_menu' => true],
                    'facultyexp.php' => ['title' => 'Faculty Experience', 'show_in_menu' => false]
                ]
            ],

            'monthly_timesheet' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'monthly_timesheet.php' => ['title' => 'Monthly Timesheet', 'show_in_menu' => true]
                ]
            ],

            'medistatusLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'medistatus.php' => ['title' => 'Medimate Approval', 'show_in_menu' => false]
                ]
            ],

            'reimbursementstatusLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'reimbursementstatus.php?source=work' => ['title' => 'Reimbursement Approval', 'show_in_menu' => true]
                ]
            ],

            'donationinfo_adminLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'donationinfo_admin.php' => ['title' => 'Donation', 'show_in_menu' => true]
                ]
            ],

            'pmsLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'pms.php' => ['title' => 'PMS', 'show_in_menu' => true]
                ]
            ],

            'admin_role_management' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'admin_role_management.php' => ['title' => 'RMC', 'show_in_menu' => true],
                    'admin_role_audit.php' => ['title' => 'Admin Role Audit', 'show_in_menu' => false]
                ]
            ],

            'amsLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'ams.php' => ['title' => 'Announcement', 'show_in_menu' => true]
                ]
            ],

            'onexitLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'onexit.php' => ['title' => 'OnExit', 'show_in_menu' => true]
                ]
            ],

            'userlogLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'userlog.php' => ['title' => 'User log', 'show_in_menu' => true]
                ]
            ],

            'access_panel' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'access_panel.php' => ['title' => 'Access Panel', 'show_in_menu' => true]
                ]
            ],

            'maintenance_panel' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'maintenance_panel.php' => ['title' => 'Maintenance Panel', 'show_in_menu' => true]
                ]
            ]
        ];

        // Build the pageData array from groups
        self::$pageData = [];
        foreach ($linkIdGroups as $linkId => $group) {
            foreach ($group['pages'] as $page => $pageInfo) {
                self::$pageData[$page] = [
                    'title' => $pageInfo['title'],
                    'section' => $group['section'],
                    'link_id' => $linkId,
                    'show_in_menu' => $pageInfo['show_in_menu'] ?? true,
                    'description' => $group['descriptions'][$page]
                        ?? $pageInfo['title'] . ' | ' . $group['section'] . ' - Phoenix Portal'
                ];

                if (isset($group['sidebar_id'])) {
                    self::$pageData[$page]['sidebar_id'] = $group['sidebar_id'];
                }

                if (isset($group['remove_collapsed'])) {
                    self::$pageData[$page]['remove_collapsed'] = $group['remove_collapsed'];
                }
            }
        }

        // Add default for unknown pages
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);
        $pageName = basename($path);

        if (empty($pageName) || $pageName === '/' || $pageName === 'index.php') {
            $pageName = 'Dashboard';
        } else {
            $pageName = strtok($pageName, '?');
            $pageName = pathinfo($pageName, PATHINFO_FILENAME);
            $pageName = str_replace(['-', '_'], ' ', $pageName);
            $pageName = ucwords($pageName);
        }

        self::$pageData['default'] = [
            'title' => $pageName,
            'section' => 'Dashboard',
            'show_in_menu' => false,
            'description' => 'Phoenix Portal dashboard and internal management system.'
        ];
    }

    /**
     * Check if current role has access to a specific page
     */
    private static function hasRoleAccess($pageFilename)
    {
        global $role;

        if (empty($role)) {
            return true; // No role, allow access (public)
        }

        if (self::$roleAccessCache === null) {
            self::$roleAccessCache = self::loadRoleAccessCache($role);
        }

        return isset(self::$roleAccessCache[$pageFilename]) && self::$roleAccessCache[$pageFilename] === true;
    }

    /**
     * Check if page exists in database
     */
    private static function pageExistsInDB($pageFilename)
    {
        // Initialize DB pages cache if needed
        if (self::$allPagesInDB === null) {
            self::$allPagesInDB = self::loadAllPagesFromDB();
        }

        return isset(self::$allPagesInDB[$pageFilename]);
    }

    /**
     * Check if page is public (no role has true access OR page not in DB)
     */
    private static function isPublicPage($pageFilename)
    {
        // If page doesn't exist in DB at all, it's public
        if (!self::pageExistsInDB($pageFilename)) {
            return true;
        }

        // Initialize public pages cache if needed
        if (self::$publicPagesCache === null) {
            self::$publicPagesCache = self::loadPublicPagesCache();
        }

        // Check if page is in public pages list (exists in DB but all roles have false)
        return isset(self::$publicPagesCache[$pageFilename]) && self::$publicPagesCache[$pageFilename] === true;
    }

    /**
     * Load all pages that exist in database
     */
    private static function loadAllPagesFromDB()
    {
        $cache = [];

        try {
            global $con;

            if (!$con) {
                error_log("MenuConfig: PostgreSQL connection not available");
                return $cache;
            }

            $connectionStatus = pg_connection_status($con);
            if ($connectionStatus !== PGSQL_CONNECTION_OK) {
                error_log("MenuConfig: PostgreSQL connection is not OK");
                return $cache;
            }

            // Query to get all page names from pages table
            $query = "SELECT page_name FROM pages";

            $result = pg_query($con, $query);
            if (!$result) {
                error_log("MenuConfig: Query failed for all pages - " . pg_last_error($con));
                return $cache;
            }

            while ($row = pg_fetch_assoc($result)) {
                $cache[$row['page_name']] = true;
            }

            pg_free_result($result);
        } catch (Exception $e) {
            error_log("MenuConfig: Error loading all pages from DB - " . $e->getMessage());
        }

        return $cache;
    }

    /**
     * Load role access permissions from database (PostgreSQL version)
     */
    private static function loadRoleAccessCache($roleName)
    {
        $cache = [];

        try {
            global $con;

            if (!$con) {
                return $cache;
            }

            $query = "
                SELECT p.page_name, pr.has_access 
                FROM pages p
                JOIN page_roles pr ON p.id = pr.page_id
                JOIN roles r ON pr.role_id = r.id
                WHERE r.role_name = $1 AND pr.has_access = true
            ";

            $result = pg_prepare($con, "get_role_access", $query);
            if (!$result) {
                return $cache;
            }

            $result = pg_execute($con, "get_role_access", array($roleName));
            if (!$result) {
                return $cache;
            }

            while ($row = pg_fetch_assoc($result)) {
                $cache[$row['page_name']] = $row['has_access'] === 't' || $row['has_access'] === true;
            }

            pg_free_result($result);
        } catch (Exception $e) {
            error_log("MenuConfig: Error loading role access - " . $e->getMessage());
        }

        return $cache;
    }

    /**
     * Load pages that exist in DB but have NO role with has_access = true
     */
    private static function loadPublicPagesCache()
    {
        $cache = [];

        try {
            global $con;

            if (!$con) {
                return $cache;
            }

            // Find pages that exist in DB but all roles have has_access = false
            $query = "
                SELECT p.page_name
                FROM pages p
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM page_roles pr 
                    WHERE pr.page_id = p.id AND pr.has_access = true
                )
            ";

            $result = pg_query($con, $query);
            if (!$result) {
                error_log("MenuConfig: Query failed for public pages - " . pg_last_error($con));
                return $cache;
            }

            while ($row = pg_fetch_assoc($result)) {
                $cache[$row['page_name']] = true;
            }

            pg_free_result($result);
        } catch (Exception $e) {
            error_log("MenuConfig: Error loading public pages - " . $e->getMessage());
        }

        return $cache;
    }

    /**
     * Check if user can see the page in menu
     */
    private static function canSeeInMenu($pageFilename)
    {
        global $role;

        // Rule 1: If page doesn't exist in DB at all, it's public (show to all)
        // Rule 2: If page exists in DB but all roles have false, it's public (show to all)
        if (self::isPublicPage($pageFilename)) {
            return true;
        }

        // Rule 3: If page exists in DB and some roles have true access,
        // check if current role has access
        if (empty($role)) {
            return false; // No role, no access to restricted pages
        }

        return self::hasRoleAccess($pageFilename);
    }

    public static function getPageInfo($page)
    {
        self::init();

        $filename = basename($page);

        if (isset(self::$pageData[$filename])) {
            return self::$pageData[$filename];
        }

        return self::$pageData['default'];
    }

    public static function getAllPages()
    {
        self::init();
        return self::$pageData;
    }

    /**
     * Get menu structure with role-based filtering
     */
    public static function getMenuStructure()
    {
        self::init();

        if (self::$menuStructure === null) {
            $structure = [];

            // Group pages by section and sidebar_id
            foreach (self::$pageData as $page => $info) {
                // FIRST: Check if page is marked to show in menu
                if (!($info['show_in_menu'] ?? true)) {
                    continue;
                }

                // SECOND: Check if user can see this page
                if (!self::canSeeInMenu($page)) {
                    continue;
                }

                $section = $info['section'];
                $sidebarId = $info['sidebar_id'] ?? null;

                if ($sidebarId) {
                    if (!isset($structure[$section])) {
                        $structure[$section] = [
                            'type' => 'collapsible',
                            'sidebar_id' => $sidebarId,
                            'pages' => []
                        ];
                    }
                    $structure[$section]['pages'][$page] = $info;
                } else {
                    if (!isset($structure[$section])) {
                        $structure[$section] = [
                            'type' => 'top_level',
                            'pages' => []
                        ];
                    }
                    $structure[$section]['pages'][$page] = $info;
                }
            }

            self::$menuStructure = $structure;
        }

        return self::$menuStructure;
    }

    public static function getCurrentPageData()
    {
        $currentPage = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? 'home.php';
        return self::getPageInfo($currentPage);
    }

    /**
     * Check if current user can access the requested page
     */
    public static function canAccessPage($page = null)
    {
        global $role;

        if ($page === null) {
            $page = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? 'home.php';
        }

        $filename = basename($page);

        // Public pages are accessible to all (not in DB or all roles false)
        if (self::isPublicPage($filename)) {
            return true;
        }

        // If no role is set, deny access to restricted pages
        if (empty($role)) {
            return false;
        }

        return self::hasRoleAccess($filename);
    }

    /**
     * Debug function to see what pages are considered public/restricted
     */
    public static function debugPageAccess($pageFilename = null)
    {
        if ($pageFilename === null) {
            $pageFilename = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? 'home.php';
        }

        $filename = basename($pageFilename);

        $existsInDB = self::pageExistsInDB($filename);
        $isPublic = self::isPublicPage($filename);
        $hasAccess = self::hasRoleAccess($filename);

        return [
            'page' => $filename,
            'exists_in_db' => $existsInDB,
            'is_public' => $isPublic,
            'has_role_access' => $hasAccess,
            'can_access' => self::canAccessPage($filename)
        ];
    }
}
