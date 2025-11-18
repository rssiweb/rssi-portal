<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    if (!isset($_GET['action']) || empty($_GET['action'])) {
        echo json_encode(['categories' => []]);
        exit;
    }
    
    $action = pg_escape_string($con, $_GET['action']);
    $ticket_id = isset($_GET['ticket_id']) ? pg_escape_string($con, $_GET['ticket_id']) : '';
    
    // Get categories for this action
    $query = "SELECT category_name FROM ticket_categories WHERE category_type = $1 ORDER BY category_name";
    $result = pg_query_params($con, $query, array($action));
    
    if (!$result) {
        throw new Exception('Database query failed');
    }
    
    $categories = [];
    while ($row = pg_fetch_assoc($result)) {
        $categories[] = $row['category_name'];
    }
    
    $response = ['categories' => $categories];
    
    // ALWAYS return current categories from database
    if ($ticket_id) {
        $current_query = "SELECT category FROM support_ticket WHERE ticket_id = $1";
        $current_result = pg_query_params($con, $current_query, array($ticket_id));
        
        if ($current_result && pg_num_rows($current_result) > 0) {
            $current_category_json = pg_fetch_result($current_result, 0, 'category');
            $current_categories = json_decode($current_category_json, true) ?: [];
            $response['current_categories'] = $current_categories;
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in get_categories.php: " . $e->getMessage());
    echo json_encode(['categories' => []]);
}
exit;
?>