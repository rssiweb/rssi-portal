<?php
function isLoggedIn(string $key) {
    if (! isset($_SESSION[$key]) || !$_SESSION[$key]){
        return false;
    }
    return true;
}
?>