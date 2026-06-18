<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['student_id'])) {
    header("Location: " . (isset($baseDepth) ? $baseDepth : '') . "login.php");
    exit;
}
