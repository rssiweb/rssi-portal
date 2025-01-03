<?php
require_once __DIR__ . "/../../bootstrap.php";

// Fetch data based on the given ID
@$id = $_GET['scode'];
$query = "
    SELECT 
        m.fullname,
        m.associatenumber,
        m.photo,
        m.engagement,
        m.position,
        m.doj,
        m.effectivedate,
        m.filterstatus,
        m.exitinterview,
        a.ipf
    FROM rssimyaccount_members m
    LEFT JOIN (
        SELECT 
            sub1.appraisee_associatenumber,
            sub1.ipf
        FROM appraisee_response sub1
        WHERE sub1.goalsheet_created_on = (
            SELECT MAX(sub2.goalsheet_created_on)
            FROM appraisee_response sub2
            WHERE sub2.appraisee_associatenumber = sub1.appraisee_associatenumber
            AND sub2.ipf IS NOT NULL
        )
    ) a ON a.appraisee_associatenumber = m.associatenumber
    WHERE m.scode = '$id'
";

$result = pg_query($con, $query);

if (!$result) {
    $error = "An error occurred while fetching data.";
    $data = null;
} else {
    $data = pg_fetch_assoc($result);
}

function calculateExperience($doj, $effectivedate = null)
{
    if (empty($doj) || !strtotime($doj)) {
        return "DOJ not available or invalid";
    }

    $dojDate = new DateTime($doj);
    $currentDate = new DateTime();
    $endDate = $effectivedate ? new DateTime($effectivedate) : $currentDate;

    if ($dojDate > $currentDate) {
        return "Not yet commenced";
    }

    $interval = $dojDate->diff($endDate);
    $years = $interval->y;
    $months = $interval->m;
    $days = $interval->d;

    if ($years > 0) {
        return number_format($years + ($months / 12), 2) . " year(s)";
    } elseif ($months > 0) {
        return number_format($months + ($days / 30), 2) . " month(s)";
    } else {
        return number_format($days, 2) . " day(s)";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Report</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <header class="text-center py-3">
        <h1 class="h4">Rina Shiksha Sahayak Foundation</h1>
        <h2 class="h5">Associate Details</h2>
    </header>

    <div class="container my-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($data): ?>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th>Photo</th>
                        <td><img src="<?= htmlspecialchars($data['photo']) ?>" class="img-fluid" alt="Photo" width="100"></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?= htmlspecialchars($data['fullname']) ?></td>
                    </tr>
                    <tr>
                        <th>Associate ID</th>
                        <td><?= htmlspecialchars($data['associatenumber']) ?></td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td><?= htmlspecialchars($data['engagement']) ?></td>
                    </tr>
                    <tr>
                        <th>Designation</th>
                        <td><?= htmlspecialchars($data['position']) ?></td>
                    </tr>
                    <tr>
                        <th>Service Period</th>
                        <td><?= htmlspecialchars($data['doj']) ?> to <?= htmlspecialchars($data['effectivedate'] ?? 'Present') ?></td>
                    </tr>
                    <tr>
                        <th>Years of Service</th>
                        <td><?= htmlspecialchars(calculateExperience($data['doj'], $data['effectivedate'])) ?></td>
                    </tr>
                    <tr>
                        <th>IPF (Individual Performance Factor)</th>
                        <td><?= $data['ipf'] !== null ? htmlspecialchars($data['ipf']) . " / 5" : "N/A" ?></td>
                    </tr>
                    <tr>
                        <th>Current Status</th>
                        <td><?= htmlspecialchars($data['filterstatus']) ?></td>
                    </tr>
                    <tr>
                        <th>Certificate Date</th>
                        <td><?= htmlspecialchars($data['effectivedate'] ?? date('d/m/Y')) ?></td>
                    </tr>
                    <tr>
                        <th>Certifying Authority</th>
                        <td><?= htmlspecialchars($data['exitinterview']) ?></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No data found for the specified ID.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>