<?php
// exception_view.php — legacy redirect
$qs = $_SERVER['QUERY_STRING'] ?? '';
$target = 'class_days_exception.php';
if (!empty($qs)) {
    // If they came with ?exception_id=X, route to detail view
    parse_str($qs, $params);
    if (isset($params['exception_id'])) {
        $target .= '?action=detail&exception_id=' . (int)$params['exception_id'];
    } else {
        $target .= '?' . $qs;
    }
}
header("Location: $target");
exit;
