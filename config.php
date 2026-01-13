<?php
// =================================================================
// File: config.php
// =================================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'onu_user');
define('DB_PASS', 'Z@reen54221');
define('DB_NAME', 'onu_management_db');

date_default_timezone_set('Asia/Dhaka');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function is_logged_in() { return isset($_SESSION['employee_id']); }
function redirect_if_not_logged_in() { if (!is_logged_in()) { header("Location: login.php"); exit(); } }

// Removed is_admin() and redirect_if_not_admin() functions
?>