<?php
require_once __DIR__ . "/../../bootstrap.php";

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

// Validate inputs
if (!in_array($type, ['health', 'period', 'pad'])) {
    die('Invalid request');
}

// Fetch record with student details
$record = pg_fetch_assoc(pg_query($con, "
    SELECT r.*, s.studentname, s.class, s.student_id as admissionnumber,
           a.fullname as recorded_by, a.position as designation, s.photourl,
           -- Age calculation (as of record date)
            EXTRACT(YEAR FROM AGE(" . getRecordDateColumn($type) . "::date, s.dateofbirth::date))::integer AS age_at_record
    FROM " . getTableName($type) . " r
    JOIN rssimyprofile_student s ON r." . getStudentColumn($type) . " = s.student_id
    JOIN rssimyaccount_members a ON r." . getRecordedByColumn($type) . " = a.associatenumber
    WHERE r." . getIdColumn($type) . "= '$id'
"));

if (!$record) {
    die('Record not found');
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
    <title>Official Record - <?= ucfirst($type) ?>_<?= $record['admissionnumber'] ?></title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
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

        @media print {
            body {
                padding: 0;
            }

            .no-print {
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
                /* Prevent splitting */
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
                <!-- Left Side - Corporate Seal -->
                <div style="width: 15%; text-align: left;">
                    <!-- <div style="width: 60px; height: 60px; border: 1px solid #1a5276; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1a5276; font-weight: bold; font-size: 10px; line-height: 1.1; text-align: center;">
                OFFICIAL SEAL
            </div> -->
                </div>

                <!-- Center - Organization Info -->
                <div style="text-align: center; flex: 1;">
                    <!-- Center Column - Organization Info -->
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

                <!-- Right Side - Verification -->
                <div style="width: 20%; text-align: right;">
                    <div style="display: inline-block; text-align: center;">
                        <?php
                        $a = 'https://login.rssi.in/rssi-student/verification.php?get_id=';
                        $b = $record['admissionnumber'];
                        $c = $record['photourl'];
                        $url = $a . $b;
                        $url = urlencode($url);
                        ?>
                        <img src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url ?>"
                            style="width: 60px;"
                            alt="QR Code">
                        <img src="<?php echo $c ?>"
                            style="height: 60px; object-fit: cover; margin-left: 5px;"
                            alt="Photo">
                    </div>
                </div>
            </div>
        </div>

        <h3 class="record-title"><?= strtoupper($type) ?> Record</h3>

        <div class="student-info">
            <table style="width: 100%; text-align: left;">
                <tr>
                    <th style="text-align: left; padding-right: 15px;">Student Name:</th>
                    <td style="padding-right: 30px;"><?= htmlspecialchars($record['studentname']) ?></td>
                    <th style="text-align: left; padding-right: 15px;">Student ID:</th>
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
                            <td><?= htmlspecialchars($record['bmi'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Blood Pressure</th>
                            <td><?= htmlspecialchars($record['blood_pressure'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Vision</th>
                            <td><?= htmlspecialchars($record['vision_left'] ?? '') ?>/<?= htmlspecialchars($record['vision_right'] ?? '') ?></td>
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
            <strong>CONFIDENTIALITY NOTICE:</strong> This document contains privileged health information protected under:
            <ul style="margin: 3px 0; padding-left: 15px;">
                <li>Section 72 of the <strong>Information Technology Act, 2000</strong> (Penalty for breach of privacy)</li>
                <li><strong>Indian Medical Council (Professional Conduct) Regulations</strong> (Patient confidentiality)</li>
                <li><strong>Digital Personal Data Protection Act, 2023</strong> (Sensitive personal data)</li>
            </ul>
            Unauthorized access, disclosure, or duplication is prohibited. Only authorized personnel of Rina Shiksha Sahayak Foundation and the patient/guardian may access this record. For verification, scan the QR code.
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