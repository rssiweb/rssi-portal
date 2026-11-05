<?php

/**
 * Returns ['reporting_time' => 'HH:MM:SS', 'exit_time' => 'HH:MM:SS'] for the
 * given associate on the given date (YYYY-MM-DD), or null if nothing is allocated.
 *
 * Uses the same rules as the monthly attendance report:
 *  - table: associate_schedule_v2
 *  - one row per weekday (Mon, Tue, ...)
 *  - row must cover the date via start_date / end_date (end_date NULL = open-ended)
 *  - latest start_date wins
 */
function getScheduleForDate($con, $associateNumber, $date)
{
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }

    $ts = strtotime($date);
    if ($ts === false) {
        return null;
    }

    $workday = date('D', $ts); // Mon, Tue, Wed, Thu, Fri, Sat, Sun

    $res = pg_query_params(
        $con,
        "SELECT reporting_time, exit_time
           FROM associate_schedule_v2
          WHERE associate_number = $1
            AND workday = $2
            AND start_date <= $3::date
            AND (end_date IS NULL OR end_date >= $3::date)
          ORDER BY start_date DESC
          LIMIT 1",
        [$associateNumber, $workday, $date]
    );

    return ($res && pg_num_rows($res) > 0) ? pg_fetch_assoc($res) : null;
}
