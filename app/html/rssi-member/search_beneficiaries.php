<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$gender = isset($_GET['gender']) ? trim($_GET['gender']) : null;
$sources = isset($_GET['sources']) ? explode(',', trim($_GET['sources'])) : ['public_health', 'student', 'member'];

// Debugging output
error_log("Received request with searchTerm: '$searchTerm', gender: '$gender', sources: " . implode(',', $sources));

// Validate gender if provided
if ($gender && !in_array($gender, ['Male', 'Female', 'Other'])) {
    error_log("Invalid gender parameter: $gender");
    echo json_encode(['results' => []]);
    exit;
}

// Validate sources
$validSources = ['public_health', 'student', 'member'];
foreach ($sources as $source) {
    if (!in_array($source, $validSources)) {
        error_log("Invalid source parameter: $source");
        echo json_encode(['results' => []]);
        exit;
    }
}

$results = [];

try {
    // Execute queries only for requested sources
    if (in_array('public_health', $sources)) {
        $phrQuery = "SELECT id, name, contact_number, 'public_health' AS source 
                     FROM public_health_records 
                     WHERE registration_completed = true AND (name ILIKE $1 OR contact_number ILIKE $1)";
        $phrParams = ["%{$searchTerm}%"];
        
        if ($gender) {
            $phrQuery .= " AND gender = $2";
            $phrParams[] = $gender;
        }
        
        $phrResult = pg_query_params($con, $phrQuery, $phrParams);
        if ($phrResult) {
            while ($row = pg_fetch_assoc($phrResult)) {
                $results[] = $row;
            }
        }
    }

    if (in_array('student', $sources)) {
        $studentQuery = "SELECT student_id AS id, studentname AS name, '' AS contact_number, 'student' AS source
                         FROM rssimyprofile_student
                         WHERE filterstatus='Active' AND (studentname ILIKE $1 OR student_id ILIKE $1 OR contact ILIKE $1)";
        $studentParams = ["%{$searchTerm}%"];
        
        if ($gender) {
            $studentQuery .= " AND gender = $2";
            $studentParams[] = $gender;
        }
        
        $studentResult = pg_query_params($con, $studentQuery, $studentParams);
        if ($studentResult) {
            while ($row = pg_fetch_assoc($studentResult)) {
                $results[] = $row;
            }
        }
    }

    if (in_array('member', $sources)) {
        $memberQuery = "SELECT associatenumber AS id, fullname AS name, '' AS contact_number, 'member' AS source
                        FROM rssimyaccount_members
                        WHERE filterstatus='Active' AND (fullname ILIKE $1 OR associatenumber ILIKE $1 OR phone ILIKE $1)";
        $memberParams = ["%{$searchTerm}%"];
        
        if ($gender) {
            $memberQuery .= " AND gender = $2";
            $memberParams[] = $gender;
        }
        
        $memberResult = pg_query_params($con, $memberQuery, $memberParams);
        if ($memberResult) {
            while ($row = pg_fetch_assoc($memberResult)) {
                $results[] = $row;
            }
        }
    }

    // Format results for Select2
    $formattedResults = [];
    foreach ($results as $row) {
        $displayText = $row['name'];

        // Add contact number if available
        if (!empty($row['contact_number'])) {
            $displayText .= ' (' . $row['contact_number'] . ')';
        } else {
            $displayText .= ' (' . $row['id'] . ')';
        }

        $formattedResults[] = [
            'id' => $row['id'],
            'text' => $displayText,
            'source' => $row['source']
        ];
    }

    // Sort results by name
    usort($formattedResults, function ($a, $b) {
        return strcmp($a['text'], $b['text']);
    });

    // Limit to 10 results
    $formattedResults = array_slice($formattedResults, 0, 10);

    error_log("Returning " . count($formattedResults) . " beneficiaries");
    echo json_encode(['results' => $formattedResults]);
} catch (Exception $e) {
    error_log("Error in search_beneficiaries.php: " . $e->getMessage());
    echo json_encode(['results' => []]);
}