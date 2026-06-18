<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/admin_check.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $conn->prepare("UPDATE notices SET is_active = 1 - is_active WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash'] = "Notice status updated.";
}
header("Location: notices.php");
exit;
