<?php
require_once __DIR__ . "/../../bootstrap.php";

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

// Validate inputs
if (!in_array($type, ['health', 'period', 'pad'])) {
    die('Invalid request');
}

// Fetch record with student details
// $record = pg_fetch_assoc(pg_query($con, "
//     SELECT r.*, s.studentname, s.class, s.student_id as admissionnumber,
//            a.fullname as recorded_by, a.position as designation, s.photourl,
//            -- Age calculation (as of record date)
//             EXTRACT(YEAR FROM AGE(" . getRecordDateColumn($type) . "::date, s.dateofbirth::date))::integer AS age_at_record
//     FROM " . getTableName($type) . " r
//     JOIN rssimyprofile_student s ON r." . getStudentColumn($type) . " = s.student_id
//     JOIN rssimyaccount_members a ON r." . getRecordedByColumn($type) . " = a.associatenumber
//     WHERE r." . getIdColumn($type) . "= '$id'
// "));

$record = pg_fetch_assoc(pg_query($con, "
    SELECT r.*, 
           COALESCE(s.studentname, p.name) AS studentname,
           COALESCE(s.class, 'N/A') AS class,
           COALESCE(s.student_id, p.id::varchar) AS admissionnumber,
           a.fullname as recorded_by, 
           a.position as designation, 
           COALESCE(s.photourl, p.profile_photo) AS photourl,
           EXTRACT(YEAR FROM AGE(" . getRecordDateColumn($type) . "::date, 
               COALESCE(s.dateofbirth::date, p.date_of_birth)::date))::integer AS age_at_record
    FROM " . getTableName($type) . " r
    LEFT JOIN rssimyprofile_student s 
        ON r." . getStudentColumn($type) . " = s.student_id
    LEFT JOIN public_health_records p 
        ON r." . getStudentColumn($type) . " = p.id::varchar
    JOIN rssimyaccount_members a 
        ON r." . getRecordedByColumn($type) . " = a.associatenumber
    WHERE r." . getIdColumn($type) . " = '$id'
"));

if (!$record) {
    die('Record not found');
}

// Function to calculate health statuses
function calculateHealthStatuses($age, $bmi, $bp, $vision)
{
    $statuses = [];

    // BMI Status (Ages 4-15) - Aligned with CDC Percentiles & WHO Categories
    if ($age >= 4 && $age <= 15) {
        $bmiThresholds = [
            // Age => [Underweight(<5%), Healthy(5-85%), Overweight(85-95%), Obese(>95%)]
            4 => [14.0, 14.0, 16.8, 17.8],
            5 => [13.8, 13.8, 17.2, 18.4],
            6 => [13.6, 13.6, 17.6, 19.2],
            7 => [13.5, 13.5, 18.0, 20.0],
            8 => [13.5, 13.5, 18.5, 21.0],
            9 => [13.8, 13.8, 19.2, 22.0],
            10 => [14.2, 14.2, 20.0, 23.0],
            11 => [14.8, 14.8, 20.8, 24.0],
            12 => [15.5, 15.5, 21.5, 25.0],
            13 => [16.0, 16.0, 22.0, 26.0],
            14 => [16.5, 16.5, 22.5, 26.5],
            15 => [17.0, 17.0, 23.0, 27.0]
        ];

        if (isset($bmiThresholds[$age])) {
            [$severeThin, $healthyMin, $overweightMin, $obeseMin] = $bmiThresholds[$age];

            if ($bmi < $severeThin) {
                $statuses[] = [
                    'type' => 'BMI',
                    'status' => 'Underweight',
                    'class' => 'info',
                    'icon' => 'bi bi-info-circle',
                    'description' => 'Severe thinness for age'
                ];
            } elseif ($bmi < $healthyMin) {
                $statuses[] = [
                    'type' => 'BMI',
                    'status' => 'Underweight',
                    'class' => 'info',
                    'icon' => 'bi bi-info-circle',
                    'description' => 'Moderate thinness for age'
                ];
            } elseif ($bmi >= $obeseMin) {
                $statuses[] = [
                    'type' => 'BMI',
                    'status' => 'Obese',
                    'class' => 'danger',
                    'icon' => 'exclamation-triangle-fill',
                    'description' => 'Obese for age'
                ];
            } elseif ($bmi >= $overweightMin) {
                $statuses[] = [
                    'type' => 'BMI',
                    'status' => 'Overweight',
                    'class' => 'warning',
                    'icon' => 'exclamation-triangle',
                    'description' => 'At risk of overweight'
                ];
            }
            // Normal weight (5-85%) shows no status
        }
    }

    // Blood Pressure Status (India-specific thresholds)
    if (!empty($bp) && preg_match('/^(\d+)\/(\d+)$/', $bp, $matches)) {
        $systolic = (int)$matches[1];
        $diastolic = (int)$matches[2];

        if ($systolic >= 130 || $diastolic >= 85) { // Modified for Indian population
            $statuses[] = [
                'type' => 'BP',
                'status' => 'High BP',
                'class' => 'danger',
                'icon' => 'heart-pulse',
                'description' => '≥130/85 mmHg (Indian standards)'
            ];
        } elseif ($systolic >= 120 && $diastolic < 85) {
            $statuses[] = [
                'type' => 'BP',
                'status' => 'Elevated',
                'class' => 'warning',
                'icon' => 'heart',
                'description' => '120-129/<85 mmHg'
            ];
        }
    }

    // Vision Status (checks both eyes)
    if (!empty($vision) && preg_match('/^(\d+)\/(\d+)\s*\/\s*(\d+)\/(\d+)$/', $vision, $matches)) {
        $leftNumerator = (int)$matches[1];
        $leftDenominator = (int)$matches[2];
        $rightNumerator = (int)$matches[3];
        $rightDenominator = (int)$matches[4];

        $leftRatio = $leftDenominator / $leftNumerator;
        $rightRatio = $rightDenominator / $rightNumerator;

        if ($leftRatio > 2 || $rightRatio > 2) {
            $statuses[] = [
                'type' => 'Vision',
                'status' => 'Vision Concern',
                'class' => 'warning',
                'icon' => 'eye-slash',
                'description' => 'One or both eyes worse than 20/40'
            ];
        }
    }

    return empty($statuses) ? [
        [
            'type' => 'Overall',
            'status' => 'Normal',
            'class' => 'success',
            'icon' => 'check-circle',
            'description' => 'All parameters within normal range'
        ]
    ] : $statuses;
}

// Helper function to find a status by type
function findStatus($statuses, $type)
{
    foreach ($statuses as $status) {
        if ($status['type'] === $type) {
            return $status;
        }
    }
    return null;
}

// Calculate health statuses if this is a health record
$healthStatuses = [];
if ($type === 'health') {
    $bmi = $record['bmi'] ?? null;
    $bp = $record['blood_pressure'] ?? null;
    $vision = ($record['vision_left'] ?? '') . '/' . ($record['vision_right'] ?? '');

    $healthStatuses = calculateHealthStatuses(
        $record['age_at_record'],
        $bmi,
        $bp,
        $vision
    );
}

// Function to get the correct table name
function getTableName($type)
{
    return [
        'health' => 'student_health_records',
        'period' => 'student_period_records',
        'pad' => 'stock_out'
    ][$type];
}

// Function to get the correct student column name
function getStudentColumn($type)
{
    return ($type === 'pad') ? 'distributed_to' : 'student_id';
}

// Function to get the correct recorded_by column name
function getRecordedByColumn($type)
{
    return ($type === 'pad') ? 'distributed_by' : 'recorded_by';
}

// Function to get the correct recorded_by column name
function getIdColumn($type)
{
    return ($type === 'pad') ? 'transaction_out_id' : 'id';
}

// Function to get the correct recorded_by column name
function getRecordDateColumn($type)
{
    return ($type === 'pad') ? 'date' : 'record_date';
}

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>

<head>
    
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .letterhead {
            text-align: center;
            margin-bottom: 30px;
        }

        .letterhead h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .letterhead p {
            margin: 0;
            color: #7f8c8d;
        }

        .divider {
            border-top: 2px solid #3498db;
            margin: 15px 0;
        }

        .record-title {
            text-align: center;
            text-transform: uppercase;
            color: #2c3e50;
            margin: 20px 0;
        }

        .student-info {
            margin-bottom: 20px;
        }

        .record-details {
            margin-bottom: 30px;
        }

        .record-details table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .record-details th,
        .record-details td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .record-details th {
            background-color: #f8f9fa;
            width: 30%;
        }

        .footer {
            margin-top: 50px;
        }

        .signature {
            float: left;
            width: 50%;
        }

        .stamp {
            float: right;
            width: 40%;
            text-align: right;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin: 40px 0 5px;
        }

        .stamp-box {
            display: inline-block;
            width: 150px;
            height: 80px;
            border: 1px dashed #333;
            margin-top: 20px;
        }

        .status-badge {
            margin-left: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .bg-success {
            background-color: #28a745;
            color: white;
        }

        .bg-danger {
            background-color: #dc3545;
            color: white;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .bg-info {
            background-color: #17a2b8;
            color: white;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .status-badge {
                display: none;
            }

            .print-status {
                display: inline;
                color: #333;
                font-style: italic;
            }
        }

        @media screen {
            .print-status {
                display: none;
            }
        }

        /* Main Content Container */
        .a4-container {
            margin: 0 auto;
            position: relative;
        }

        /* Fixed Footer */
        .confidentiality-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            font-size: 8pt;
            color: #555;
            text-align: justify;
            padding: 6px 10px;
            background-color: #f8f8f8;
            border-top: 1px dashed #d1d3d4;
            box-sizing: border-box;
        }

        /* Print-specific adjustments */
        @media print {
            .a4-container {
                page-break-after: avoid;
            }

            .confidentiality-footer {
                position: fixed;
            }
        }
    </style>
</head>

<body>
    <div class="a4-container">
        <div class="letterhead" style="width: 100%; max-width: 800px; margin: 0 auto; padding-bottom: 5px; border-bottom: 1px solid #d1d3d4; margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div style="width: 15%; text-align: left;"></div>

                <div style="text-align: center; flex: 1;">
                    <div style="flex: 1; text-align: center;">
                        <h1 style="color: #1a5276; font-size: 18px; margin: 0; font-weight: 600; line-height: 1.2;">RINA SHIKSHA SAHAYAK FOUNDATION</h1>
                        <p style="color: #555; font-size: 11px; margin: 2px 0; font-style: italic; line-height: 1.2;">
                            (Comprising RSSI NGO and Kalpana Buds School)
                        </p>
                        <p style="color: #333; font-size: 10px; margin: 2px 0; line-height: 1.2;">
                            <strong>NGO-DARPAN ID:</strong> WB/2021/0282726 |
                            <strong>CIN:</strong> U80101WB2020NPL237900
                        </p>
                        <p style="color: #333; font-size: 10px; margin: 3px 0 0 0; line-height: 1.2;">
                            <strong>Email:</strong> info@rssi.in | <strong>Website:</strong> www.rssi.in
                        </p>
                    </div>
                </div>

                <div style="width: 20%; text-align: right;">
                    <div style="display: inline-block; text-align: center;">
                        <?php
                        $a = 'https://login.rssi.in/rssi-member/print_record.php?';
                        $b = "type=$type&id=$id";
                        $c = $record['photourl'];
                        $url = $a . $b;
                        $url = urlencode($url);
                        ?>
                        <img src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url ?>"
                            style="width: 60px;"
                            alt="QR Code">
                        <?php
                        if (!empty($c)) {
                            if (strpos($c, 'drive.google.com') !== false && preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $c, $matches)) {
                                // Google Drive image
                                $file_id = $matches[1];
                                $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                                echo '<iframe src="' . $preview_url . '" width="60" height="60" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                            } else {
                                // Regular image
                                echo '<img src="' . $c . '" style="width: 60px; height: 60px; object-fit: cover; margin-left: 5px;" alt="Photo">';
                            }
                        } else {
                            echo 'No photo available';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="record-title"><?= strtoupper($type) ?> Record</h3>

        <div class="student-info">
            <table style="width: 100%; text-align: left;">
                <tr>
                    <th style="text-align: left; padding-right: 15px;">Beneficiary Name:</th>
                    <td style="padding-right: 30px;"><?= htmlspecialchars($record['studentname']) ?></td>
                    <th style="text-align: left; padding-right: 15px;">Beneficiary ID:</th>
                    <td><?= htmlspecialchars($record['admissionnumber']) ?></td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-right: 15px;">Class:</th>
                    <td style="padding-right: 30px;"><?= htmlspecialchars($record['class']) ?></td>
                    <th style="text-align: left; padding-right: 15px;">Date Issued:</th>
                    <td><?= date('d/m/Y') ?></td>
                </tr>
                <tr>
                    <th style="text-align: left; padding-right: 15px;">Age (at the time of data recording):</th>
                    <td colspan="3"><?= htmlspecialchars($record['age_at_record']) ?></td>
                </tr>
            </table>
        </div>

        <div class="record-details">
            <table>
                <?php switch ($type):
                    case 'health': ?>
                        <tr>
                            <th>Record Date</th>
                            <td><?= date('d M Y', strtotime($record['record_date'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <th>Height</th>
                            <td><?= htmlspecialchars($record['height_cm'] ?? '') ?> cm</td>
                        </tr>
                        <tr>
                            <th>Weight</th>
                            <td><?= htmlspecialchars($record['weight_kg'] ?? '') ?> kg</td>
                        </tr>
                        <tr>
                            <th>BMI</th>
                            <td>
                                <?= htmlspecialchars($record['bmi'] ?? '') ?>
                                <?php if ($bmi && ($bmiStatus = findStatus($healthStatuses, 'BMI'))): ?>
                                    <span class="status-badge bg-<?= $bmiStatus['class'] ?>">
                                        <i class="bi bi-<?= $bmiStatus['icon'] ?>"></i> <?= $bmiStatus['status'] ?>
                                    </span>
                                    <span class="print-status">(<?= $bmiStatus['status'] ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Blood Pressure</th>
                            <td>
                                <?= htmlspecialchars($record['blood_pressure'] ?? '') ?>
                                <?php if ($bp && ($bpStatus = findStatus($healthStatuses, 'BP'))): ?>
                                    <span class="status-badge bg-<?= $bpStatus['class'] ?>">
                                        <i class="bi bi-<?= $bpStatus['icon'] ?>"></i> <?= $bpStatus['status'] ?>
                                    </span>
                                    <span class="print-status">(<?= $bpStatus['status'] ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Vision</th>
                            <td>
                                <?= htmlspecialchars($record['vision_left'] ?? '') ?>/<?= htmlspecialchars($record['vision_right'] ?? '') ?>
                                <?php if ($vision && ($visionStatus = findStatus($healthStatuses, 'Vision'))): ?>
                                    <span class="status-badge bg-<?= $visionStatus['class'] ?>">
                                        <i class="bi bi-<?= $visionStatus['icon'] ?>"></i> <?= $visionStatus['status'] ?>
                                    </span>
                                    <span class="print-status">(<?= $visionStatus['status'] ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($record['general_health_notes'])): ?>
                            <tr>
                                <th>Notes</th>
                                <td><?= nl2br(htmlspecialchars($record['general_health_notes'])) ?></td>
                            </tr>
                        <?php endif;
                        break;

                    case 'period': ?>
                        <tr>
                            <th>Cycle Start Date</th>
                            <td><?= date('d M Y', strtotime($record['cycle_start_date'])) ?></td>
                        </tr>
                        <tr>
                            <th>Cycle End Date</th>
                            <td><?= $record['cycle_end_date'] ? date('d M Y', strtotime($record['cycle_end_date'])) : 'Ongoing' ?></td>
                        </tr>
                        <?php if (!empty($record['symptoms'])): ?>
                            <tr>
                                <th>Symptoms</th>
                                <td><?= nl2br(htmlspecialchars($record['symptoms'])) ?></td>
                            </tr>
                        <?php endif;
                        if (!empty($record['notes'])): ?>
                            <tr>
                                <th>Notes</th>
                                <td><?= nl2br(htmlspecialchars($record['notes'])) ?></td>
                            </tr>
                        <?php endif;
                        break;

                    case 'pad': ?>
                        <tr>
                            <th>Distribution Date</th>
                            <td><?= date('d M Y', strtotime($record['date'])) ?></td>
                        </tr>
                        <tr>
                            <th>Quantity</th>
                            <td><?= htmlspecialchars($record['quantity_distributed']) ?></td>
                        </tr>
                        <?php if (!empty($record['description'])): ?>
                            <tr>
                                <th>Notes</th>
                                <td><?= nl2br(htmlspecialchars($record['description'])) ?></td>
                            </tr>
                <?php endif;
                endswitch; ?>
            </table>
        </div>

        <div class="footer">
            <div class="signature">
                <p>Recorded By:</p>
                <div class="signature-line"></div>
                <p><?= htmlspecialchars($record['recorded_by']) ?><br>
                    <?= htmlspecialchars($record['designation']) ?></p>
            </div>
            <div class="stamp">
                <p>Authorized Stamp:</p>
                <div class="stamp-box"></div>
            </div>
            <div style="clear:both;"></div>
        </div>
        <div class="confidentiality-footer">
            <p>Important Disclaimer: We make every effort to provide accurate health information. However, Rina Shiksha Sahayak Foundation cannot guarantee its completeness. This record is not a substitute for professional medical advice, and we encourage you to consult a qualified medical professional for any health-related decisions.</p>
        </div>
    </div>
    <script>
        // Auto-print when loaded
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 200);
        };

        // Close after printing (optional)
        window.onafterprint = function() {
            // window.close();
        };
    </script>
</body>

</html>