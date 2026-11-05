<?php

/**
 * Returns ['reporting_time' => 'HH:MM:SS', 'exit_time' => 'HH:MM:SS'] for the
 * given associate on the given date (YYYY-MM-DD), or null if nothing is allocated.
 *
 * Same rules as the monthly attendance report:
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

/**
 * Validates an exception request against LIVE data.
 * This is the single place where the rules live. It is called:
 *   1. by check_exception_rule.php (every time the user changes the form), and
 *   2. by the POST handler of the exception page (final enforcement).
 *
 * Rules:
 *   - Exception date must be today, in the future, or at most 3 calendar days in the past.
 *   - late-entry : time must be at most 1 hour after that day's reporting_time.
 *   - early-exit : time must be at most 1 hour before that day's exit_time.
 *   - late-entry / early-exit need a schedule for that weekday and date.
 *
 * Returns:
 *   ['valid' => bool, 'code' => string, 'message' => string,
 *    'reporting_time' => ?string, 'exit_time' => ?string]
 *
 * code is one of: '' (valid), incomplete, invalid, window, no_schedule, late, early
 */
function validateExceptionRequest($con, $associateNumber, $exceptionType, $subType, $startDateTime, $endDateTime)
{
    $result = function ($valid, $code = '', $message = '', $sched = null) {
        return [
            'valid'          => $valid,
            'code'           => $code,
            'message'        => $message,
            'reporting_time' => $sched['reporting_time'] ?? null,
            'exit_time'      => $sched['exit_time'] ?? null,
        ];
    };

    $allowed = [
        'entry' => ['late-entry', 'missed-entry'],
        'exit'  => ['early-exit', 'missed-exit'],
    ];
    if (!isset($allowed[$exceptionType]) || !in_array($subType, $allowed[$exceptionType], true)) {
        return $result(false, 'incomplete', 'Please select the exception type and sub exception type.');
    }

    // Entry exceptions use the start date-time, exit exceptions use the end date-time
    $value = ($exceptionType === 'entry') ? $startDateTime : $endDateTime;
    if (empty($value)) {
        return $result(false, 'incomplete', 'Please enter the date and time.');
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return $result(false, 'invalid', 'The date and time entered is not valid.');
    }
    $date = date('Y-m-d', $ts);

    // ---- Rule 1: request window (today, future, or up to 3 calendar days back) ----
    $diff = (new DateTime('today'))->diff(new DateTime($date));
    if ($diff->invert && $diff->days > 3) {
        return $result(
            false,
            'window',
            'The selected date (' . date('d/m/Y', $ts) . ') is outside the allowed exception request window. '
                . 'Exception requests must be submitted within 3 calendar days of the exception date.'
        );
    }

    // ---- Rule 2: schedule based checks (live lookup every time) ----
    if ($subType === 'late-entry') {
        $sched = getScheduleForDate($con, $associateNumber, $date);
        if (!$sched || empty($sched['reporting_time'])) {
            return $result(false, 'no_schedule', 'You have no schedule allocated on ' . date('d/m/Y', $ts) . '. Please contact your supervisor.');
        }
        $limit = strtotime("$date {$sched['reporting_time']}") + 3600;
        if ($ts > $limit) {
            return $result(
                false,
                'late',
                'You are applying for a late-entry exception on ' . date('d/m/Y', $ts) . ' at ' . date('h:i A', $ts)
                    . ', which is more than 1 hour after your reporting time of ' . date('h:i A', strtotime($sched['reporting_time']))
                    . '. Please apply for leave through the leave portal.',
                $sched
            );
        }
        return $result(true, '', '', $sched);
    }

    if ($subType === 'early-exit') {
        $sched = getScheduleForDate($con, $associateNumber, $date);
        if (!$sched || empty($sched['exit_time'])) {
            return $result(false, 'no_schedule', 'You have no schedule allocated on ' . date('d/m/Y', $ts) . '. Please contact your supervisor.');
        }
        $limit = strtotime("$date {$sched['exit_time']}") - 3600;
        if ($ts < $limit) {
            return $result(
                false,
                'early',
                'You are applying for an early-exit exception on ' . date('d/m/Y', $ts) . ' at ' . date('h:i A', $ts)
                    . ', which is more than 1 hour before your scheduled exit time of ' . date('h:i A', strtotime($sched['exit_time']))
                    . '. Please apply for leave through the leave portal.',
                $sched
            );
        }
        return $result(true, '', '', $sched);
    }

    // missed-entry / missed-exit: only the date window rule applies
    return $result(true);
}
