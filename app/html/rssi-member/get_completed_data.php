<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    die("Unauthorized access");
}

$selected_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;

$query = "SELECT wc.*, 
        a.fullname as cleaner_name,
        s.fullname as submitted_by_name,
        l1.fullname as level1_approver_name,
        l2.fullname as level2_approver_name,
        l3.fullname as level3_approver_name
        FROM washroom_cleaning wc
        LEFT JOIN rssimyaccount_members a ON wc.cleaner_id = a.associatenumber
        LEFT JOIN rssimyaccount_members s ON wc.submitted_by = s.associatenumber
        LEFT JOIN rssimyaccount_members l1 ON wc.level1_approver = l1.associatenumber
        LEFT JOIN rssimyaccount_members l2 ON wc.level2_approver = l2.associatenumber
        LEFT JOIN rssimyaccount_members l3 ON wc.level3_approver = l3.associatenumber
        WHERE wc.current_status IN ('FINAL_APPROVED', 'REJECTED')";

if ($selected_academic_year) {
    $query .= getAcademicYearCondition($selected_academic_year, 'wc');
}

$query .= " ORDER BY wc.cleaning_date DESC";

$records = pg_query($con, $query);
displayCleaningRecords($records, null);

function getAcademicYearCondition($selected_academic_year, $table_alias = '')
{
    if (empty($selected_academic_year)) {
        return '';
    }

    list($start_year, $end_year) = explode('-', $selected_academic_year);
    $start_year = (int)$start_year;
    $end_year = (int)$end_year;

    $prefix = $table_alias ? "$table_alias." : '';

    return " AND (
        (EXTRACT(MONTH FROM {$prefix}cleaning_date) >= 4 
        AND EXTRACT(YEAR FROM {$prefix}cleaning_date) = $start_year)
        OR
        (EXTRACT(MONTH FROM {$prefix}cleaning_date) < 4 
        AND EXTRACT(YEAR FROM {$prefix}cleaning_date) = $end_year)
    )";
}

function displayCleaningRecords($records, $approval_level)
{
    if (pg_num_rows($records) == 0) {
        echo '<div class="alert alert-info">No records found.</div>';
        return;
    }

    while ($row = pg_fetch_assoc($records)) {
        $status_class = '';
        $status_text = '';

        switch ($row['current_status']) {
            case 'SUBMITTED':
                $status_class = 'status-submitted';
                $status_text = 'Submitted';
                break;
            case 'LEVEL1_APPROVED':
                $status_class = 'status-level1';
                $status_text = 'Level 1 Approved';
                break;
            case 'LEVEL2_APPROVED':
                $status_class = 'status-level2';
                $status_text = 'Level 2 Approved';
                break;
            case 'FINAL_APPROVED':
                $status_class = 'status-approved';
                $status_text = 'Approved';
                break;
            case 'REJECTED':
                $status_class = 'status-rejected';
                $status_text = 'Rejected';
                break;
        }

        echo '<div class="card mb-3 cleaning-card">';
        echo '<div class="card-body">';
        echo '<div class="d-flex justify-content-between align-items-start">';
        echo '<div>';
        echo '<h5 class="card-title">' . htmlspecialchars($row['washroom_location']) . '</h5>';
        echo '<p class="card-text mb-1"><small class="text-muted">Cleaned by: ' . htmlspecialchars($row['cleaner_name']) . '</small></p>';
        echo '<p class="card-text mb-1"><small class="text-muted">Date: ' . date('d M Y H:i', strtotime($row['cleaning_date'])) . '</small></p>';
        echo '</div>';
        echo '<span class="badge ' . $status_class . '">' . $status_text . '</span>';
        echo '</div>';

        // Display cleaning details only if checked
        echo '<div class="row mt-2">';
        echo '<div class="col-md-6">';
        if ($row['is_cleaned'] == 't') {
            echo '<p class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Cleaned</p>';
        }
        if ($row['is_sanitized'] == 't') {
            echo '<p class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Sanitized</p>';
        }
        if ($row['is_restocked'] == 't') {
            echo '<p class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Restocked</p>';
        }
        echo '</div>';
        echo '<div class="col-md-6">';
        if (!empty($row['issues_found'])) {
            echo '<div class="alert alert-warning p-2 mb-0">';
            echo '<strong>Issues:</strong> ' . htmlspecialchars($row['issues_found']);
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';

        // Display approval information if available
        if (!empty($row['level1_approver_name'])) {
            echo '<hr class="my-2">';
            echo '<div class="row">';
            echo '<div class="col">';
            echo '<p class="mb-1 small"><strong>Level 1 Approval:</strong> ' . htmlspecialchars($row['level1_approver_name']) . ' on ' . date('d M Y H:i', strtotime($row['level1_approval_date'])) . '</p>';
            if (!empty($row['level1_comments'])) {
                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($row['level1_comments']) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        if (!empty($row['level2_approver_name'])) {
            echo '<div class="row">';
            echo '<div class="col">';
            echo '<p class="mb-1 small"><strong>Level 2 Approval:</strong> ' . htmlspecialchars($row['level2_approver_name']) . ' on ' . date('d M Y H:i', strtotime($row['level2_approval_date'])) . '</p>';
            if (!empty($row['level2_comments'])) {
                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($row['level2_comments']) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        if (!empty($row['level3_approver_name'])) {
            echo '<div class="row">';
            echo '<div class="col">';
            echo '<p class="mb-1 small"><strong>Final Approval:</strong> ' . htmlspecialchars($row['level3_approver_name']) . ' on ' . date('d M Y H:i', strtotime($row['level3_approval_date'])) . '</p>';
            if (!empty($row['level3_comments'])) {
                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($row['level3_comments']) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }
}
