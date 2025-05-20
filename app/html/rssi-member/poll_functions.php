<?php
function get_user_id()
{
    global $associatenumber, $student_id;

    if (isset($associatenumber) && !empty($associatenumber)) {
        return ['id' => $associatenumber, 'is_student' => false];
    } elseif (isset($student_id) && !empty($student_id)) {
        return ['id' => $student_id, 'is_student' => true];
    }
    return null;
}

function has_voted($con, $poll_id, $user_id, $is_student)
{
    $query = "SELECT 1 FROM poll_votes WHERE poll_id = $1 AND voter_id = $2 AND is_student = $3";
    $result = pg_query_params($con, $query, [$poll_id, $user_id, $is_student ? 't' : 'f']);
    return (pg_num_rows($result)) > 0;
}

function get_poll_results($con, $poll_id)
{
    $query = "
        SELECT po.option_id, po.option_text, COUNT(pv.vote_id) as vote_count
        FROM poll_options po
        LEFT JOIN poll_votes pv ON po.option_id = pv.option_id
        WHERE po.poll_id = $1
        GROUP BY po.option_id, po.option_text
        ORDER BY po.option_id
    ";
    $result = pg_query_params($con, $query, [$poll_id]);
    return pg_fetch_all($result) ?: [];
}

function is_poll_expired($con, $poll_id)
{
    $query = "SELECT expires_at < NOW() as expired FROM polls WHERE poll_id = $1";
    $result = pg_query_params($con, $query, [$poll_id]);
    $row = pg_fetch_assoc($result);
    return $row['expired'] === 't';
}

function create_poll($con, $question, $expires_at, $options, $creator_id, $is_multiple_choice = false)
{
    pg_query($con, "BEGIN");

    try {
        // Insert poll
        $query = "INSERT INTO polls (question, expires_at, created_by, is_multiple_choice) VALUES ($1, $2, $3, $4) RETURNING poll_id";
        $result = pg_query_params($con, $query, [$question, $expires_at, $creator_id, $is_multiple_choice ? 't' : 'f']);
        $poll_id = pg_fetch_result($result, 0, 0);

        // Insert options
        foreach ($options as $option_text) {
            $option_text = pg_escape_string($con, $option_text);
            $query = "INSERT INTO poll_options (poll_id, option_text) VALUES ($1, $2)";
            pg_query_params($con, $query, [$poll_id, $option_text]);
        }

        pg_query($con, "COMMIT");
        return $poll_id;
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        return false;
    }
}

function record_vote($con, $poll_id, $option_id, $voter_id, $is_student)
{
    $query = "INSERT INTO poll_votes (poll_id, option_id, voter_id, is_student) VALUES ($1, $2, $3, $4)";
    return pg_query_params($con, $query, [$poll_id, $option_id, $voter_id, $is_student ? 't' : 'f']);
}

function get_poll($con, $poll_id)
{
    $user = get_user_id();
    $query = "
        SELECT p.*, 
               CASE 
                   WHEN m.associatenumber IS NOT NULL THEN m.fullname
                   WHEN s.student_id IS NOT NULL THEN s.studentname
                   ELSE 'Unknown'
               END as creator_name,
               (SELECT voted_at FROM poll_votes 
                WHERE poll_id = p.poll_id AND voter_id = $2::varchar AND is_student = $3 LIMIT 1) as voted_at
        FROM polls p
        LEFT JOIN rssimyaccount_members m ON p.created_by::varchar = m.associatenumber AND $3 = false
        LEFT JOIN rssimyprofile_student s ON p.created_by::varchar = s.student_id AND $3 = true
        WHERE p.poll_id = $1
    ";

    $result = pg_query_params($con, $query, [
        $poll_id,
        $user ? $user['id'] : '0',
        $user ? ($user['is_student'] ? 't' : 'f') : 'f'
    ]);

    if (!$result) {
        error_log("Poll query failed: " . pg_last_error($con));
        return false;
    }

    return pg_fetch_assoc($result);
}

function get_all_polls($con)
{
    $user = get_user_id();
    $query = "
        SELECT p.*, 
               CASE 
                   WHEN m.associatenumber IS NOT NULL THEN m.fullname
                   WHEN s.student_id IS NOT NULL THEN s.studentname
                   ELSE 'Unknown'
               END as creator_name,
               (SELECT 1 FROM poll_votes 
                WHERE poll_id = p.poll_id AND voter_id = $1::varchar AND is_student = $2 LIMIT 1) as has_voted
        FROM polls p
        LEFT JOIN rssimyaccount_members m ON p.created_by::varchar = m.associatenumber AND $2 = false
        LEFT JOIN rssimyprofile_student s ON p.created_by::varchar = s.student_id AND $2 = true
        ORDER BY p.created_at DESC
    ";

    $result = pg_query_params($con, $query, [
        $user ? $user['id'] : '0',
        $user ? ($user['is_student'] ? 't' : 'f') : 'f'
    ]);

    if (!$result) {
        error_log("All polls query failed: " . pg_last_error($con));
        return [];
    }

    return pg_fetch_all($result) ?: [];
}
function get_paginated_polls($con, $page = 1, $per_page = 5, $current_user_id = null, $is_student = false) {
    $offset = ($page - 1) * $per_page;
    
    // Prepare parameters for safe query
    $params = [];
    $query = "SELECT p.*, 
               CASE 
                   WHEN m.associatenumber IS NOT NULL THEN m.fullname
                   WHEN s.student_id IS NOT NULL THEN s.studentname
                   ELSE 'Unknown'
               END as creator_name";
    
    // Add has_voted subquery if user is logged in
    if ($current_user_id !== null) {
        $query .= ", (SELECT 1 FROM poll_votes 
                     WHERE poll_id = p.poll_id 
                     AND voter_id = $1::varchar 
                     AND is_student = $2 LIMIT 1) as has_voted";
        $params[] = $current_user_id;
        $params[] = $is_student ? 't' : 'f';
    }
    
    $query .= " FROM polls p
               LEFT JOIN rssimyaccount_members m ON p.created_by::varchar = m.associatenumber AND $2 = false
               LEFT JOIN rssimyprofile_student s ON p.created_by::varchar = s.student_id AND $2 = true
               ORDER BY p.created_at DESC
               LIMIT $per_page OFFSET $offset";
    
    // Execute query with parameters
    $result = pg_query_params($con, $query, $params);
    
    $polls = [];
    while ($row = pg_fetch_assoc($result)) {
        $polls[] = $row;
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM polls";
    $count_result = pg_query($con, $count_query);
    $total = pg_fetch_assoc($count_result)['total'];
    
    return [
        'polls' => $polls,
        'total' => $total,
        'total_pages' => ceil($total / $per_page)
    ];
}
function get_latest_active_poll($con) {
    $query = "SELECT * FROM polls 
              WHERE expires_at > NOW() 
              ORDER BY created_at DESC 
              LIMIT 1";
    $result = pg_query($con, $query);
    return pg_fetch_assoc($result);
}
