<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation(); // provides $role and $associatenumber

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =====================================================================
 * 1. HIERARCHY
 *    Member -> supervisor chain, same logic as fetchHierarchy():
 *    A reports to B, B to C, C to D  =>  B, C and D can see A's data,
 *    A cannot see B, C or D.
 * ===================================================================== */
$memberMap = [];      // associatenumber => [fullname, position, supervisor]
$childrenMap = [];    // supervisor => [associatenumber, ...]
$resMembers = pg_query($con, "SELECT associatenumber, fullname, position, supervisor FROM rssimyaccount_members");
if ($resMembers) {
    while ($m = pg_fetch_assoc($resMembers)) {
        $k = (string)$m['associatenumber'];
        $memberMap[$k] = $m;
        $sup = trim((string)$m['supervisor']);
        if ($sup !== '') {
            $childrenMap[$sup][] = $k;
        }
    }
}

/** All direct and indirect reports of $root (cycle-safe). */
function getDescendants($root, $childrenMap)
{
    $out = [];
    $seen = [(string)$root => true];
    $queue = [(string)$root];
    while ($queue) {
        $cur = array_shift($queue);
        foreach ($childrenMap[$cur] ?? [] as $child) {
            if (!isset($seen[$child])) {
                $seen[$child] = true;
                $out[] = $child;
                $queue[] = $child;
            }
        }
    }
    return $out;
}

/** Supervisor chain above $aid: [direct supervisor, their supervisor, ...] (cycle-safe). */
function getChainUp($aid, $memberMap)
{
    $chain = [];
    $seen = [(string)$aid => true];
    $cur = trim((string)($memberMap[$aid]['supervisor'] ?? ''));
    while ($cur !== '' && isset($memberMap[$cur]) && !isset($seen[$cur])) {
        $chain[] = $cur;
        $seen[$cur] = true;
        $cur = trim((string)$memberMap[$cur]['supervisor']);
    }
    return $chain;
}

/* =====================================================================
 * 2. FILTERS
 * ===================================================================== */
$month = isset($_GET['get_month']) ? $_GET['get_month'] : date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}
$status = (isset($_GET['get_aid']) && $_GET['get_aid'] === 'Inactive') ? 'Inactive' : 'Active';
$engagementFilter = $_GET['engagement'] ?? '';
$includeSelf = isset($_GET['include_self']) && $_GET['include_self'] === '1';
$teamOf = trim($_GET['team_of'] ?? '');

$startDate = date("Y-m-01", strtotime($month . '-01'));
$endDate   = date("Y-m-t", strtotime($month . '-01'));
$today     = date('Y-m-d');

/* Who can this user see? null = everyone (Admin without team filter) */
$scopeIds = null;
if ($role === 'Admin') {
    if ($teamOf !== '') {
        $scopeIds = getDescendants($teamOf, $childrenMap);
        if ($includeSelf) {
            $scopeIds[] = $teamOf;
        }
    }
} else {
    $scopeIds = getDescendants((string)$associatenumber, $childrenMap);
    if ($includeSelf) {
        $scopeIds[] = (string)$associatenumber;
    }
}

$noTeam = ($scopeIds !== null && count($scopeIds) === 0);

/* =====================================================================
 * 3. ISSUE DEFINITIONS (same rules as the calendar view)
 * ===================================================================== */
$ISSUES = [
    'missout'  => [
        'label' => 'Missing punch-out',
        'color' => '#dc3545',
        'sev' => 'High',
        'action' => 'Ask the associate to raise an exit exception (missed exit) request with the actual exit time. Review and approve or reject it in the exception module.'
    ],
    'absent'   => [
        'label' => 'Absent',
        'color' => '#6c1d24',
        'sev' => 'High',
        'action' => 'Confirm the reason. If on leave, ask the associate to apply for it. If they worked, ask for a missed-entry exception. Otherwise system-enforced leave may apply.'
    ],
    'halfmiss' => [
        'label' => 'Punch missing (half day)',
        'color' => '#c2185b',
        'sev' => 'High',
        'action' => 'Half day leave is applied but no punch is recorded. Ask the associate to raise an entry/exit exception for the hours worked.'
    ],
    'late'     => [
        'label' => 'Late',
        'color' => '#fd7e14',
        'sev' => 'Medium',
        'action' => 'Discuss the reason with the associate. If justified, ask them to raise a late-entry exception; otherwise record it for follow-up.'
    ],
    'early'    => [
        'label' => 'Early exit',
        'color' => '#e0a800',
        'sev' => 'Medium',
        'action' => 'Discuss the reason. If justified, ask the associate to raise an exit exception; otherwise record it for follow-up.'
    ],
];

/** Returns list of issue keys for one day-row (empty = nothing to follow up). */
function classifyDay(array $r, string $today): array
{
    $issues = [];
    if ($r['attendance_status'] === null) return [];            // before joining
    if ($r['attendance_date'] >= $today) return [];             // today/future: day not over
    $hasIn  = !empty($r['punch_in']);
    $hasOut = !empty($r['punch_out']);
    $late   = $r['late_status'];
    $exc    = $r['exception_status'];
    $isHoliday = ($r['is_holiday'] === 't');
    $leave  = ((int)$r['has_full_leave'] === 1) || $late === 'Leave';
    $half   = ($late === 'HF');

    if ($isHoliday && !$hasIn) return [];

    if ($hasIn) {
        if (!$hasOut) $issues[] = 'missout';
        $entryExc = in_array($late, ['Exc.', 'Exc.L'], true) || ($exc !== null && strpos($exc, 'Exc') === 0);
        if ($late === 'L' && !$entryExc) $issues[] = 'late';
        if ($r['exit_status'] === 'EE') $issues[] = 'early';
    } else {
        if ($leave) {
            // approved leave, nothing to follow up
        } elseif ($half) {
            $issues[] = 'halfmiss';
        } elseif (!empty($r['reporting_time'])) {
            $issues[] = 'absent';
        }
    }
    return $issues;
}

/* =====================================================================
 * 4. DATA
 * ===================================================================== */
$byAssociate = [];
$totals = array_fill_keys(array_keys($ISSUES), 0);
$flat = []; // for CSV export
$queryError = '';

if (!$noTeam) {
    $idCondition = "AND m.filterstatus = '" . pg_escape_string($con, $status) . "'";
    $engagementCondition = $engagementFilter !== '' ? "AND m.engagement = '" . pg_escape_string($con, $engagementFilter) . "'" : '';
    $scopeCondition = '';
    if ($scopeIds !== null) {
        $esc = array_map(fn($i) => pg_escape_string($con, (string)$i), $scopeIds);
        $scopeCondition = "AND m.associatenumber IN ('" . implode("','", $esc) . "')";
    }

    $query = "
WITH date_range AS (
    SELECT generate_series('$startDate'::date, '$endDate'::date, '1 day'::interval)::date AS attendance_date
),
holiday_set AS (
    SELECT holiday_date, location FROM holidays WHERE is_flexi = false
),
workday_exc AS (
    SELECT exception_date, location_id FROM workday_exceptions WHERE is_workday = TRUE
),
members AS (
    SELECT m.associatenumber, m.filterstatus, m.fullname, m.engagement, m.position,
           m.phone, m.supervisor, m.effectivedate, m.doj, m.depb
    FROM rssimyaccount_members m
    WHERE (m.filterstatus = 'Active'
           OR (m.filterstatus = 'Inactive'
               AND DATE_TRUNC('month', m.effectivedate)::date
                   >= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date))
      AND DATE_TRUNC('month', m.doj)::date <= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date
      $idCondition
      $engagementCondition
      $scopeCondition
),
punch_agg AS (
    SELECT a.user_id,
           DATE_TRUNC('day', a.punch_in)::date AS punch_date,
           MIN(a.punch_in) AS punch_in,
           CASE WHEN COUNT(*) = 1 THEN NULL ELSE MAX(a.punch_in) END AS punch_out
    FROM attendance a
    WHERE a.punch_in >= '$startDate'::date
      AND a.punch_in <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY a.user_id, DATE_TRUNC('day', a.punch_in)::date
),
entry_exc AS (
    SELECT e.submitted_by, DATE(e.start_date_time) AS exc_date,
           MIN(e.start_date_time) FILTER (WHERE e.sub_exception_type = 'missed-entry') AS missed_entry_time,
           MIN(e.start_date_time) FILTER (WHERE e.sub_exception_type = 'late-entry')   AS late_entry_time
    FROM exception_requests e
    WHERE e.status = 'Approved' AND e.exception_type = 'entry'
      AND e.start_date_time >= '$startDate'::date
      AND e.start_date_time <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY e.submitted_by, DATE(e.start_date_time)
),
exit_exc AS (
    SELECT e.submitted_by, DATE(e.end_date_time) AS exc_date, MIN(e.end_date_time) AS exit_time
    FROM exception_requests e
    WHERE e.status = 'Approved' AND e.exception_type = 'exit'
      AND e.end_date_time >= '$startDate'::date
      AND e.end_date_time <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY e.submitted_by, DATE(e.end_date_time)
),
leave_agg AS (
    SELECT l.applicantid, d.attendance_date,
           MAX(CASE WHEN l.halfday = 0 THEN 1 ELSE 0 END) AS has_full_leave,
           MAX(CASE WHEN l.halfday = 1 THEN 1 ELSE 0 END) AS halfday_count
    FROM leavedb_leavedb l
    JOIN date_range d ON d.attendance_date BETWEEN l.fromdate AND l.todate
    WHERE l.status = 'Approved'
    GROUP BY l.applicantid, d.attendance_date
),
sched AS (
    SELECT DISTINCT ON (s.associate_number, s.workday)
           s.associate_number, s.workday, s.start_date, s.end_date, s.reporting_time, s.exit_time
    FROM associate_schedule_v2 s
    WHERE s.start_date <= '$endDate'::date
      AND (s.end_date IS NULL OR s.end_date >= '$startDate'::date)
    ORDER BY s.associate_number, s.workday, s.start_date DESC
),
base AS (
    SELECT m.associatenumber, m.fullname, m.engagement, m.position, m.phone, m.supervisor, m.doj,
           d.attendance_date, p.punch_in, p.punch_out, sc.reporting_time, sc.exit_time,
           ee.missed_entry_time, ee.late_entry_time, xe.exit_time AS exc_exit_time,
           COALESCE(la.has_full_leave, 0) AS has_full_leave,
           COALESCE(la.halfday_count, 0)  AS halfday_count,
           EXISTS (SELECT 1 FROM holiday_set h
                    WHERE h.holiday_date = d.attendance_date
                      AND (h.location IS NULL OR h.location = m.depb)) AS holiday_match,
           EXISTS (SELECT 1 FROM workday_exc w
                    WHERE w.exception_date = d.attendance_date
                      AND (w.location_id IS NULL OR w.location_id = m.depb)) AS workday_override
    FROM members m
    CROSS JOIN date_range d
    LEFT JOIN punch_agg p ON p.user_id = m.associatenumber AND p.punch_date = d.attendance_date
    LEFT JOIN sched sc
           ON sc.associate_number = m.associatenumber
          AND sc.workday = CASE EXTRACT(DOW FROM d.attendance_date)
                              WHEN 1 THEN 'Mon' WHEN 2 THEN 'Tue' WHEN 3 THEN 'Wed'
                              WHEN 4 THEN 'Thu' WHEN 5 THEN 'Fri' WHEN 6 THEN 'Sat'
                              WHEN 0 THEN 'Sun' END
          AND sc.start_date <= d.attendance_date
          AND (sc.end_date IS NULL OR sc.end_date >= d.attendance_date)
    LEFT JOIN entry_exc ee ON ee.submitted_by = m.associatenumber AND ee.exc_date = d.attendance_date
    LEFT JOIN exit_exc  xe ON xe.submitted_by = m.associatenumber AND xe.exc_date = d.attendance_date
    LEFT JOIN leave_agg la ON la.applicantid  = m.associatenumber AND la.attendance_date = d.attendance_date
),
final AS (
    SELECT b.*,
        COALESCE(b.missed_entry_time, b.punch_in)  AS eff_punch_in,
        COALESCE(b.exc_exit_time,     b.punch_out) AS eff_punch_out,
        (b.holiday_match AND NOT b.workday_override) AS is_holiday,
        CASE WHEN b.punch_in IS NOT NULL THEN 'P'
             WHEN b.doj > b.attendance_date THEN NULL
             ELSE 'A' END AS attendance_status,
        CASE
            WHEN b.has_full_leave = 1 THEN 'Leave'
            WHEN b.halfday_count >= 2 THEN 'Leave'
            WHEN b.halfday_count = 1  THEN 'HF'
            WHEN b.late_entry_time IS NOT NULL THEN
                CASE WHEN b.punch_in IS NOT NULL AND b.punch_in::time <= b.late_entry_time::time THEN 'Exc.'
                     WHEN b.punch_in IS NOT NULL THEN 'Exc.L'
                     ELSE NULL END
            WHEN b.missed_entry_time IS NOT NULL THEN
                CASE WHEN b.missed_entry_time::time > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                     WHEN b.missed_entry_time::time > b.reporting_time
                      AND b.missed_entry_time::time <= b.reporting_time + INTERVAL '10 minutes' THEN 'W'
                     ELSE NULL END
            WHEN b.punch_in IS NOT NULL THEN
                CASE WHEN b.reporting_time IS NULL THEN 'NA'
                     WHEN b.punch_in::time > b.reporting_time + INTERVAL '1 minute'
                      AND b.punch_in::time <= b.reporting_time + INTERVAL '11 minutes' THEN 'W'
                     WHEN b.punch_in::time > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                     ELSE NULL END
            ELSE NULL
        END AS late_status,
        CASE WHEN b.exc_exit_time IS NOT NULL THEN 'Exc.'
             WHEN b.punch_out IS NOT NULL AND b.exit_time IS NOT NULL
              AND b.punch_out::time < b.exit_time AND b.halfday_count = 0 THEN 'EE'
             ELSE NULL END AS exit_status,
        CASE WHEN b.missed_entry_time IS NOT NULL
             THEN CASE WHEN b.reporting_time IS NULL THEN 'Exc.NA' ELSE 'Exc.' END
             ELSE NULL END AS exception_status
    FROM base b
)
SELECT f.associatenumber, f.fullname, f.engagement, f.position, f.phone, f.supervisor,
       f.attendance_date, f.attendance_status,
       f.eff_punch_in AS punch_in, f.eff_punch_out AS punch_out,
       f.reporting_time, f.exit_time AS sched_exit_time,
       f.has_full_leave, f.is_holiday, f.late_status, f.exit_status, f.exception_status
FROM final f
ORDER BY f.associatenumber, f.attendance_date;
";

    $result = pg_query($con, $query);
    if (!$result) {
        $queryError = pg_last_error($con);
    } else {
        $fmt = fn($t) => $t ? date('h:i A', strtotime($t)) : '';
        while ($r = pg_fetch_assoc($result)) {
            $found = classifyDay($r, $today);
            if (!$found) continue;

            $aid = (string)$r['associatenumber'];
            if (!isset($byAssociate[$aid])) {
                $chain = getChainUp($aid, $memberMap);
                $byAssociate[$aid] = [
                    'id'         => $aid,
                    'name'       => $r['fullname'],
                    'engagement' => $r['engagement'],
                    'position'   => $r['position'],
                    'phone'      => $r['phone'],
                    'supervisor' => $r['supervisor'],
                    'chain'      => $chain,
                    'counts'     => array_fill_keys(array_keys($ISSUES), 0),
                    'high'       => 0,
                    'items'      => [],
                ];
            }
            foreach ($found as $key) {
                $byAssociate[$aid]['counts'][$key]++;
                if ($ISSUES[$key]['sev'] === 'High') $byAssociate[$aid]['high']++;
                $totals[$key]++;
                $item = [
                    'date' => $r['attendance_date'],
                    'key'  => $key,
                    'in'   => $fmt($r['punch_in']),
                    'out'  => $fmt($r['punch_out']),
                    'rep'  => $fmt($r['reporting_time']),
                    'exit' => $fmt($r['sched_exit_time']),
                ];
                $byAssociate[$aid]['items'][] = $item;
                $flat[] = [
                    $aid,
                    $r['fullname'],
                    ($memberMap[$r['supervisor']]['fullname'] ?? '') . ($r['supervisor'] ? ' (' . $r['supervisor'] . ')' : ''),
                    $r['attendance_date'],
                    $ISSUES[$key]['label'],
                    $item['in'],
                    $item['out'],
                    $item['rep'],
                    $item['exit'],
                    $ISSUES[$key]['action'],
                ];
            }
        }
        // High severity first, then total issues
        uasort($byAssociate, function ($a, $b) {
            return [$b['high'], count($b['items'])] <=> [$a['high'], count($a['items'])];
        });
    }
}

$issueDays = array_sum($totals);
$assocCount = count($byAssociate);

/** WhatsApp reminder link for one associate */
function waLink($a, $ISSUES)
{
    if (empty($a['phone'])) return '';
    $lines = [];
    foreach ($a['items'] as $it) {
        $lines[] = '- ' . date('d M', strtotime($it['date'])) . ': ' . $ISSUES[$it['key']]['label'];
    }
    $msg = "Dear " . $a['name'] . " (" . $a['id'] . "),\n\n"
        . "The following attendance items need your attention:\n" . implode("\n", $lines) . "\n\n"
        . "Please raise the relevant exception/leave request or update your timesheet at the earliest.\n\n"
        . "--RSSI\n\n**This is a system generated message.";
    return "https://api.whatsapp.com/send?phone=91" . preg_replace('/\D/', '', $a['phone']) . "&text=" . urlencode($msg);
}
?>
<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <style>
        .sum-card {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            border-left-width: 5px;
            padding: 10px 14px;
            cursor: pointer;
            background: #fff;
            height: 100%;
        }

        .sum-card.active {
            box-shadow: 0 0 0 2px #0d6efd;
        }

        .sum-card .num {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .sum-card .lbl {
            font-size: .8rem;
            color: #6c757d;
        }

        .issue-badge {
            display: inline-block;
            font-size: .72rem;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            margin: 0 4px 3px 0;
            white-space: nowrap;
        }

        .detail-row>td {
            background: #f8f9fa;
        }

        .chain {
            font-size: .8rem;
            color: #6c757d;
        }

        .send-link {
            text-decoration: none;
        }

        .toggle-btn {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <p class="text-muted mb-3">
                                Days that need follow-up for you and everyone who reports to you (directly or indirectly).
                                Days with an approved exception, leave or a holiday are not listed.
                            </p>

                            <!-- Filters -->
                            <form method="GET" class="row g-2 align-items-end mb-3">
                                <div class="col-12 col-sm-2">
                                    <input type="month" name="get_month" class="form-control" value="<?= h($month) ?>" required>
                                    <small class="form-text text-muted">Month</small>
                                </div>
                                <div class="col-12 col-sm-2">
                                    <select name="get_aid" class="form-select">
                                        <option <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                    <small class="form-text text-muted">Status</small>
                                </div>
                                <div class="col-12 col-sm-2">
                                    <select name="engagement" class="form-select">
                                        <option value="">All Engagements</option>
                                        <?php foreach (['Employee', 'Intern', 'Member', 'Volunteer'] as $e): ?>
                                            <option value="<?= $e ?>" <?= $engagementFilter === $e ? 'selected' : '' ?>><?= $e ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Engagement</small>
                                </div>
                                <?php if ($role === 'Admin'): ?>
                                    <div class="col-12 col-sm-2">
                                        <input type="text" name="team_of" class="form-control" placeholder="All associates" value="<?= h($teamOf) ?>">
                                        <small class="form-text text-muted">Team of (associate no.)</small>
                                    </div>
                                <?php endif; ?>
                                <div class="col-12 col-sm-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="include_self" value="1" id="incSelf" <?= $includeSelf ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="incSelf">Include own data</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-2">
                                    <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Search</button>
                                </div>
                            </form>

                            <?php if ($queryError): ?>
                                <div class="alert alert-danger">Query failed: <?= h($queryError) ?></div>
                            <?php elseif ($noTeam): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> No one reports to <?= $role === 'Admin' ? h($teamOf) : 'you' ?>, so there is nothing to monitor here.
                                </div>
                            <?php elseif (!$assocCount): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Nothing needs attention for <?= h(date('F Y', strtotime($startDate))) ?>.
                                </div>
                            <?php else: ?>

                                <!-- Summary cards (click to filter) -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6 col-md-2">
                                        <div class="sum-card" data-cat="" style="border-left-color:#0d6efd">
                                            <div class="num"><?= $assocCount ?></div>
                                            <div class="lbl">Associates (<?= $issueDays ?> items)</div>
                                        </div>
                                    </div>
                                    <?php foreach ($ISSUES as $k => $meta): ?>
                                        <div class="col-6 col-md-2">
                                            <div class="sum-card" data-cat="<?= $k ?>" style="border-left-color:<?= $meta['color'] ?>">
                                                <div class="num"><?= $totals[$k] ?></div>
                                                <div class="lbl"><?= h($meta['label']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted">Showing <?= h(date('F Y', strtotime($startDate))) ?> &middot; sorted by most serious first</div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="csvBtn"><i class="bi bi-download"></i> Download CSV</button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle" id="attnTable">
                                        <thead>
                                            <tr>
                                                <th>Associate</th>
                                                <th>Reports to</th>
                                                <th>Needs attention</th>
                                                <th>Items</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($byAssociate as $aid => $a):
                                                $cats = [];
                                                foreach ($a['counts'] as $k => $c) if ($c) $cats[] = $k;
                                                $supName = $memberMap[$a['supervisor']]['fullname'] ?? '';
                                                $wa = waLink($a, $ISSUES);
                                                $rowId = 'd_' . preg_replace('/\W/', '', $aid);
                                            ?>
                                                <tr class="assoc-row" data-cats="<?= h(implode(' ', $cats)) ?>" data-target="<?= $rowId ?>">
                                                    <td>
                                                        <strong><?= h($a['name']) ?></strong>
                                                        <div class="small text-muted"><?= h($aid) ?> &middot; <?= h($a['position']) ?> &middot; <?= h($a['engagement']) ?></div>
                                                    </td>
                                                    <td><?= $supName ? h($supName) . ' <span class="text-muted small">(' . h($a['supervisor']) . ')</span>' : '<span class="text-muted">–</span>' ?></td>
                                                    <td>
                                                        <?php foreach ($a['counts'] as $k => $c): if (!$c) continue; ?>
                                                            <span class="issue-badge" data-cat="<?= $k ?>" style="background:<?= $ISSUES[$k]['color'] ?>"><?= h($ISSUES[$k]['label']) ?> &times; <?= $c ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td><?= count($a['items']) ?></td>
                                                    <td class="text-nowrap">
                                                        <a class="toggle-btn me-2" data-toggle="<?= $rowId ?>"><i class="bi bi-chevron-down"></i> Details</a>
                                                        <?php if ($wa): ?>
                                                            <a class="send-link" href="<?= h($wa) ?>" target="_blank" title="Send WhatsApp reminder to <?= h($a['name']) ?>"><i class="bi bi-whatsapp"></i> Remind</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr class="detail-row d-none" id="<?= $rowId ?>">
                                                    <td colspan="5">
                                                        <?php if ($a['chain']): ?>
                                                            <div class="chain mb-2">
                                                                <i class="bi bi-diagram-3"></i> Escalation chain:
                                                                <?php
                                                                $parts = [];
                                                                foreach ($a['chain'] as $c) {
                                                                    $parts[] = h($memberMap[$c]['fullname'] ?? $c) . ' (' . h($c) . ')';
                                                                }
                                                                echo implode(' &rarr; ', $parts);
                                                                ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <table class="table table-sm table-bordered mb-0 bg-white">
                                                            <thead>
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Issue</th>
                                                                    <th>Punch-in</th>
                                                                    <th>Punch-out</th>
                                                                    <th>Scheduled</th>
                                                                    <th>Course of action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($a['items'] as $it): $meta = $ISSUES[$it['key']]; ?>
                                                                    <tr class="issue-line" data-cat="<?= $it['key'] ?>">
                                                                        <td class="text-nowrap"><?= h(date('D, d M', strtotime($it['date']))) ?></td>
                                                                        <td><span class="issue-badge" style="background:<?= $meta['color'] ?>"><?= h($meta['label']) ?></span></td>
                                                                        <td><?= h($it['in']) ?: '–' ?></td>
                                                                        <td><?= h($it['out']) ?: '–' ?></td>
                                                                        <td class="small"><?= h($it['rep']) ?: '–' ?> &ndash; <?= h($it['exit']) ?: '–' ?></td>
                                                                        <td class="small"><?= h($meta['action']) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        (function() {
            // Expand / collapse details
            document.querySelectorAll('[data-toggle]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById(btn.dataset.toggle).classList.toggle('d-none');
                });
            });

            // Filter by category (summary cards)
            let active = '';
            const cards = document.querySelectorAll('.sum-card');

            function applyFilter() {
                cards.forEach(c => c.classList.toggle('active', c.dataset.cat === active && active !== ''));
                document.querySelectorAll('.assoc-row').forEach(function(row) {
                    const show = !active || row.dataset.cats.split(' ').includes(active);
                    row.classList.toggle('d-none', !show);
                    const detail = document.getElementById(row.dataset.target);
                    if (!show) detail.classList.add('d-none');
                    detail.querySelectorAll('.issue-line').forEach(function(l) {
                        l.classList.toggle('d-none', !!active && l.dataset.cat !== active);
                    });
                });
            }
            cards.forEach(function(c) {
                c.addEventListener('click', function() {
                    active = (active === c.dataset.cat) ? '' : c.dataset.cat;
                    applyFilter();
                });
            });

            // CSV download
            const rows = <?= json_encode($flat, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const head = ['Associate Number', 'Name', 'Reports To', 'Date', 'Issue', 'Punch In', 'Punch Out', 'Scheduled Reporting', 'Scheduled Exit', 'Course of Action'];
            const btn = document.getElementById('csvBtn');
            if (btn) btn.addEventListener('click', function() {
                const q = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
                const csv = [head].concat(rows).map(r => r.map(q).join(',')).join('\r\n');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob(['\ufeff' + csv], {
                    type: 'text/csv;charset=utf-8;'
                }));
                a.download = 'attendance_attention_<?= h($month) ?>.csv';
                a.click();
            });
        })();
    </script>
</body>

</html>