<?php
// config/menu_config.php

class MenuConfig
{
    private static $pageData = null;

    private static function init()
    {
        if (self::$pageData !== null) return;

        // Define groups by link_id
        $linkIdGroups = [
            // ============ TOP LEVEL PAGES ============
            'homeLink' => [
                'section' => 'Dashboard',
                'remove_collapsed' => true,
                'pages' => [
                    'home.php' => 'Home',
                    'create_event.php' => 'Create Event',
                    'edit_event.php' => 'Edit Event',
                    'polls.php' => 'Polls'
                ],
                'descriptions' => [
                    'home.php' => 'Phoenix Portal dashboard overview and quick access.',
                    'create_event.php' => 'Create and manage events efficiently in Phoenix Portal.'
                ]
            ],

            'attendX' => [
                'section' => 'Attendance Portal',
                'remove_collapsed' => true,
                'pages' => [
                    'attendx.php' => 'Attendance Portal',
                    'scan.php' => 'Scan Attendance',
                    'in_out_tracker.php' => 'In-Out Tracker',
                    'monthly_attd_report.php' => 'Monthly Attendance Report',
                    'monthly_attd_report_associate.php' => 'Monthly Associate Report',
                    'attendance-analytics.php' => 'Attendance Analytics',
                    'remote_attendance.php' => 'Remote Attendance',
                    'sas.php' => 'SAS'
                ]
            ],

            'stockmanagementLink' => [
                'section' => 'Stock Management',
                'remove_collapsed' => true,
                'pages' => [
                    'stock_management.php' => 'Stock Management',
                    'stock_add.php' => 'Add Stock',
                    'stock_out.php' => 'Stock Out',
                    'items_management.php' => 'Items Management',
                    'item_prices_management.php' => 'Item Prices Management',
                    'units_management.php' => 'Units Management',
                    'stock_in.php' => 'Stock In',
                    'inventory-insights.php' => 'Inventory Insights',
                    'group_management.php' => 'Group Management',
                    'edit_group.php' => 'Edit Group'
                ]
            ],

            'hrmsLink' => [
                'section' => 'Profile',
                'remove_collapsed' => true,
                'pages' => [
                    'hrms.php' => 'Profile',
                    'resetpassword.php' => 'Reset Password',
                    'setup_2fa.php' => 'Enable 2FA'
                ]
            ],

            // ============ ACADEMIC SECTION ============
            'examLink' => [
                'section' => 'Academic',
                'sidebar_id' => 'acadamis',
                'pages' => [
                    'exam.php' => 'Examination'
                ]
            ],

            'exam-management' => [
                'section' => 'Academic',
                'sidebar_id' => 'acadamis',
                'pages' => [
                    'exam-management.php' => 'Exam Management',
                    'append-students.php' => 'Append Students',
                    'result-scheduler.php' => 'Result Scheduler',
                    'exam_create.php' => 'Create Exam',
                    'progress_curve.php' => 'Progress Curve',
                    'exam_allotment.php' => 'Exam Allotment',
                    'exam_marks_upload.php' => 'Upload Exam Marks',
                    'exam_summary_report.php' => 'Exam Summary Report',
                    'reexam.php' => 'Re-exam',
                    'reexam_record.php' => 'Re-exam Records'
                ]
            ],

            // ============ MY SERVICES SECTION ============
            'leaveLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'leave.php' => 'Apply for Leave',
                    'leaveallo.php' => 'Leave Allocation',
                    'leaveadjustment.php' => 'Leave Adjustment'
                ]
            ],

            'documentLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'document.php' => 'My Document',
                    'my_certificate.php' => 'My Certificate',
                    'pay_details.php' => 'Pay Details',
                    'old_payslip.php' => 'Old Payslip',
                    'digital_archive.php' => 'Digital Archive',
                    'bankdetails.php' => 'Bank Details'
                ]
            ],

            'allocationLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'allocation.php' => 'My Allocation'
                ]
            ],

            'policyLink' => [
                'section' => 'My Services',
                'sidebar_id' => 'myservices',
                'pages' => [
                    'resourcehub.php' => 'Resource Hub'
                ]
            ],

            // ============ EXCEPTION PORTAL ============
            'raiseException' => [
                'section' => 'Exception',
                'sidebar_id' => 'exceptionPortal',
                'pages' => [
                    'exception-portal.php' => 'Raise Exception'
                ]
            ],

            'dashboardException' => [
                'section' => 'Exception',
                'sidebar_id' => 'exceptionPortal',
                'pages' => [
                    'exception_admin.php' => 'Exception Dashboard'
                ]
            ],

            // ============ IEXPLORE LEARNER ============
            'iexploreLink' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'iexplore.php' => 'Courses'
                ]
            ],

            'my_learning' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'my_learning.php' => 'My Learnings'
                ]
            ],

            'viscoLink' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'visco.php' => 'Visco',
                    'library.php' => 'Library',
                    'my_book.php' => 'My Book'
                ]
            ],

            'iexplore_admin' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'iexplore_admin.php' => 'Modify Course'
                ]
            ],

            'iexplore_defaulters' => [
                'section' => 'iExplore Learner',
                'sidebar_id' => 'learning',
                'pages' => [
                    'iexplore_defaulters.php' => 'Defaulters List'
                ]
            ],

            // ============ IEXPLORE EDGE ============
            'exam_management' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'exam_management.php' => 'Exam Management'
                ]
            ],

            'add_question' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'add_question.php' => 'Add Question'
                ]
            ],

            'question_dashboard' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'question_dashboard.php' => 'Question Dashboard'
                ]
            ],

            'manage_category' => [
                'section' => 'iExplore Edge',
                'sidebar_id' => 'iexploreedge',
                'pages' => [
                    'manage_category.php' => 'Manage Category'
                ]
            ],

            // ============ PERFORMANCE MANAGEMENT ============
            'my_appraisalLink' => [
                'section' => 'Performance Management',
                'sidebar_id' => 'performance',
                'pages' => [
                    'my_appraisal.php' => 'My Appraisal',
                    'ipf-management.php' => 'IPF Management',
                    'manager_response.php' => 'Manager Response',
                    'appraisee_response.php' => 'Appraisee Response',
                    'reviewer_response.php' => 'Reviewer Response',
                    'process.php' => 'Process'
                ]
            ],

            // ============ REWARDS & RECOGNITION ============
            'gems-mart' => [
                'section' => 'Rewards & Recognition',
                'sidebar_id' => 'rewards',
                'pages' => [
                    'gems-mart.php' => 'Gems Mart'
                ]
            ],

            'my-orders' => [
                'section' => 'Rewards & Recognition',
                'sidebar_id' => 'rewards',
                'pages' => [
                    'my-orders.php' => 'My Orders'
                ]
            ],

            // ============ CLAIMS AND ADVANCES ============
            'reimbursementLink' => [
                'section' => 'Claims and Advances',
                'sidebar_id' => 'claims',
                'pages' => [
                    'reimbursement.php' => 'Reimbursement',
                    'reimbursementstatus.php' => 'Reimbursement Status'
                ]
            ],

            'medimateLink' => [
                'section' => 'Claims and Advances',
                'sidebar_id' => 'claims',
                'pages' => [
                    'medimate.php' => 'Medimate'
                ]
            ],

            // ============ SUPPORT 360 ============
            'create_ticket' => [
                'section' => 'Support 360',
                'sidebar_id' => 'support360',
                'pages' => [
                    'create_ticket.php' => 'Create Ticket'
                ]
            ],

            'ticket_log' => [
                'section' => 'Support 360',
                'sidebar_id' => 'support360',
                'pages' => [
                    'ticket_log.php' => 'Ticket Log',
                    'ticket-dashboard.php' => 'Ticket Dashboard'
                ]
            ],

            // ============ COMMUNITY SUPPLY ============
            'emart' => [
                'section' => 'Community Supply',
                'sidebar_id' => 'csu',
                'pages' => [
                    'emart.php' => 'eMart'
                ]
            ],

            'emart_orders' => [
                'section' => 'Community Supply',
                'sidebar_id' => 'csu',
                'pages' => [
                    'emart_orders.php' => 'eMart Orders'
                ]
            ],

            // ============ SCHEDULE HUB ============
            'shift_planner' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'shift_planner.php' => 'Shift Planner'
                ]
            ],

            'view_shift' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'view_shift.php' => 'View Shift'
                ]
            ],

            'closure_assign' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'closure_assign.php' => 'Closing Duty Roster'
                ]
            ],

            'student_class_days' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'student_class_days.php' => 'Student Class Days'
                ]
            ],

            'exception_view' => [
                'section' => 'Schedule Hub',
                'sidebar_id' => 'rostermanagement',
                'pages' => [
                    'exception_view.php' => 'Class Days Exceptions',
                    'class_days_exception.php' => 'Class Days Exception'
                ]
            ],

            // ============ SURVEY ============
            'create_survey' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'survey.php' => 'Create Survey'
                ]
            ],

            'view_survey' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'survey_view.php' => 'View Survey Results'
                ]
            ],

            'appointments' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'appointments.php' => 'View Appointments'
                ]
            ],

            'enquiry_portal' => [
                'section' => 'Survey',
                'sidebar_id' => 'survey',
                'pages' => [
                    'enquiry_portal.php' => 'Enquiry Portal'
                ]
            ],

            // ============ JOB ASSISTANCE ============
            'job_seekers' => [
                'section' => 'Job Assistance',
                'sidebar_id' => 'job',
                'pages' => [
                    'job_seekers.php' => 'Job Seeker Records'
                ]
            ],

            'job-admin' => [
                'section' => 'Job Assistance',
                'sidebar_id' => 'job',
                'pages' => [
                    'job-admin.php' => 'Job Admin Panel',
                    'job-approval.php' => 'Job Approval',
                    'job_view.php' => 'Job View'
                ]
            ],

            // ============ PEOPLE PLUS ============
            'talent_pool' => [
                'section' => 'People Plus',
                'sidebar_id' => 'peoplePlus',
                'pages' => [
                    'talent_pool.php' => 'Talent Pool',
                    'applicant_profile.php' => 'Applicant Profile'
                ]
            ],

            'interview_central' => [
                'section' => 'People Plus',
                'sidebar_id' => 'peoplePlus',
                'pages' => [
                    'interview_central.php' => 'Interview Central',
                    'technical_interview.php' => 'Technical Interview',
                    'hr_interview.php' => 'HR Interview'
                ]
            ],

            'rtet' => [
                'section' => 'People Plus',
                'sidebar_id' => 'peoplePlus',
                'pages' => [
                    'rtet.php' => 'Create RTET'
                ]
            ],

            // ============ FEE PORTAL ============
            'fee_collection' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_collection.php' => 'Fee Collection'
                ]
            ],

            'concession_list' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'concession_list.php' => 'Student Concessions'
                ]
            ],

            'settlement' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'settlement.php' => 'Fee Settlement'
                ]
            ],

            'fee_payments_report' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_payments_report.php' => 'Fee Payments Report'
                ]
            ],

            'fee_structure_management' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_structure_management.php' => 'Fee Structure Management'
                ]
            ],

            'fee_lock_management' => [
                'section' => 'Fee Portal',
                'sidebar_id' => 'feePortal',
                'pages' => [
                    'fee_lock_management.php' => 'Fee Collection Lock Management'
                ]
            ],

            // ============ IRC ============
            'books' => [
                'section' => 'IRC',
                'sidebar_id' => 'irc',
                'pages' => [
                    'books.php' => 'Library Dashboard'
                ]
            ],

            'orders' => [
                'section' => 'IRC',
                'sidebar_id' => 'irc',
                'pages' => [
                    'book_orders.php' => 'Library Orders Management'
                ]
            ],

            // ============ PAYROLL ============
            'salary_structure' => [
                'section' => 'Payroll',
                'sidebar_id' => 'payroll',
                'pages' => [
                    'salary_structure.php' => 'Salary Structure Management',
                    'view_structure.php' => 'View Structure'
                ]
            ],

            'payroll_processingLink' => [
                'section' => 'Payroll',
                'sidebar_id' => 'payroll',
                'pages' => [
                    'payroll_processing.php' => 'Payroll Processing'
                ]
            ],

            // ============ HEALTH PORTAL ============
            'salary_structure_health' => [
                'section' => 'Health & Wellness Initiatives',
                'sidebar_id' => 'health_portal',
                'pages' => [
                    'health_portal.php' => 'Student Health Portal'
                ]
            ],

            'community_care' => [
                'section' => 'Health & Wellness Initiatives',
                'sidebar_id' => 'health_portal',
                'pages' => [
                    'community_care.php' => 'Community Care Portal'
                ]
            ],

            // ============ WORKLIST ============
            'hrms_worklist' => [
                'section' => 'Worklist',
                'sidebar_id' => 'worklist',
                'pages' => [
                    'hrms_worklist.php' => 'HRMS Worklist'
                ]
            ],

            'post_worklist' => [
                'section' => 'Worklist',
                'sidebar_id' => 'worklist',
                'pages' => [
                    'post_worklist.php' => 'Post Worklist'
                ]
            ],

            'iexplore_worklist' => [
                'section' => 'Worklist',
                'sidebar_id' => 'worklist',
                'pages' => [
                    'iexplore_worklist.php' => 'iExplore Worklist'
                ]
            ],

            // ============ ALLIANCE PORTAL ============
            'contact-directory' => [
                'section' => 'Alliance Portal',
                'sidebar_id' => 'alliance_portal',
                'pages' => [
                    'contact-directory.php' => 'Contact Directory'
                ]
            ],

            // ============ ICOM ============
            'icom_manage_order' => [
                'section' => 'ID Card Order Management',
                'sidebar_id' => 'icom',
                'pages' => [
                    'icom.php' => 'Order Management'
                ]
            ],

            'icom_order_history' => [
                'section' => 'ID Card Order Management',
                'sidebar_id' => 'icom',
                'pages' => [
                    'id_history.php' => 'Order History'
                ]
            ],

            // ============ LEAVE MANAGEMENT ============
            'leave_approval' => [
                'section' => 'Leave Management System',
                'sidebar_id' => 'lms',
                'pages' => [
                    'leave_approval.php' => 'Leave Approval'
                ]
            ],

            'leave_admin' => [
                'section' => 'Leave Management System',
                'sidebar_id' => 'lms',
                'pages' => [
                    'leave_admin.php' => 'Leave Admin'
                ]
            ],

            // ============ GPS ============
            'my-asset' => [
                'section' => 'GPS',
                'sidebar_id' => 'gps',
                'pages' => [
                    'gps.php' => 'My Assets'
                ]
            ],

            'asset-management' => [
                'section' => 'GPS',
                'sidebar_id' => 'gps',
                'pages' => [
                    'asset-management.php' => 'Asset Management',
                    'scan-asset.php' => 'Scan Asset',
                    'admin_change_requests.php' => 'Admin Change Requests',
                    'get_request_details.php' => 'Get Request Details',
                    'gps_history.php' => 'GPS History',
                    'asset_verification_report.php' => 'Asset Verification Report'
                ]
            ],

            // ============ WORK SECTION ============
            'studentLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'student.php' => 'RSSI Student',
                    'admission_admin.php' => 'Admission Admin',
                    'fees.php' => 'Fees',
                    'sps.php' => 'SPS'
                ]
            ],

            'student_attrition' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'student_attrition.php' => 'Student Attrition'
                ]
            ],

            'distribution_analytics' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'distribution_analytics.php' => 'Distribution Analytics'
                ]
            ],

            'facility-hygiene-monitoring' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'facility-hygiene-monitoring.php' => 'Facility Hygiene Monitoring'
                ]
            ],

            'archive_approval' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'archive_approval.php' => 'Document Approval'
                ]
            ],

            'bankdetails_admin' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'bankdetails_admin.php' => 'HR Banking Records'
                ]
            ],

            'process-hubLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'process-hub.php' => 'Process Hub',
                    'onboarding.php' => 'Onboarding',
                    'exit.php' => 'Exit',
                    'visitor.php' => 'Visitor'
                ]
            ],

            'facultyLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'faculty.php' => 'RSSI Faculty',
                    'facultyexp.php' => 'Faculty Experience'
                ]
            ],

            'monthly_timesheet' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'monthly_timesheet.php' => 'Monthly Timesheet'
                ]
            ],

            'medistatusLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'medistatus.php' => 'Medimate Approval'
                ]
            ],

            'reimbursementstatusLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'reimbursementstatus.php' => 'Reimbursement Approval'
                ]
            ],

            'donationinfo_adminLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'donationinfo_admin.php' => 'Donation'
                ]
            ],

            'pmsLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'pms.php' => 'PMS'
                ]
            ],

            'admin_role_management' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'admin_role_management.php' => 'RMC',
                    'admin_role_audit.php' => 'Admin Role Audit'
                ]
            ],

            'amsLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'ams.php' => 'Announcement'
                ]
            ],

            'onexitLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'onexit.php' => 'OnExit'
                ]
            ],

            'userlogLink' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'userlog.php' => 'User log'
                ]
            ],

            'access_panel' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'access_panel.php' => 'Access Panel'
                ]
            ],

            'maintenance_panel' => [
                'section' => 'Work',
                'sidebar_id' => 'work',
                'pages' => [
                    'maintenance_panel.php' => 'Maintenance Panel'
                ]
            ]
        ];

        // Build the pageData array from groups
        self::$pageData = [];
        foreach ($linkIdGroups as $linkId => $group) {
            foreach ($group['pages'] as $page => $title) {
                self::$pageData[$page] = [
                    'title' => $title,
                    'section' => $group['section'],
                    'link_id' => $linkId,
                    'description' => $group['descriptions'][$page]
                        ?? $title . ' | ' . $group['section'] . ' - Phoenix Portal'
                ];

                // Add sidebar_id if exists
                if (isset($group['sidebar_id'])) {
                    self::$pageData[$page]['sidebar_id'] = $group['sidebar_id'];
                }

                // Add remove_collapsed if exists
                if (isset($group['remove_collapsed'])) {
                    self::$pageData[$page]['remove_collapsed'] = $group['remove_collapsed'];
                }
            }
        }

        // Add default for unknown pages
        self::$pageData['default'] = [
            'title' => 'Dashboard',
            'section' => 'Dashboard',
            'description' => 'Phoenix Portal dashboard and internal management system.'
        ];
    }

    public static function getPageInfo($page)
    {
        self::init();

        // Extract just filename if it's a path
        $filename = basename($page);

        // Check if page exists
        if (isset(self::$pageData[$filename])) {
            return self::$pageData[$filename];
        }

        // Default
        return self::$pageData['default'];
    }

    public static function getAllPages()
    {
        self::init();
        return self::$pageData;
    }

    public static function getCurrentPageData()
    {
        $currentPage = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? 'home.php';
        return self::getPageInfo($currentPage);
    }
}
