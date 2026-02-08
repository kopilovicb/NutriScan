<?php

session_start();

// Uništavanje svih session podataka
$_SESSION = array();

// Brisanje session cookie-a
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Uništavanje sesije
session_destroy();

// Redirect na login stranicu
header('Location: index.html');
exit;
