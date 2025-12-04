<?php
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function isDefaultPassword($password) {
    // Check if password matches default pattern
    $defaultPattern = '/^[A-Z]{2}[0-9]{6}$/';
    return preg_match($defaultPattern, $password);
}
?>