<?php
// =================================================================
// File: logout.php
// Description: Destroys the session and logs the user out.
// =================================================================

require_once 'config.php';

// সেশন আনসেট এবং ধ্বংস করুন
$_SESSION = array();
session_destroy();

// লগইন পেজে পাঠান
header("Location: login.php");
exit();
?>