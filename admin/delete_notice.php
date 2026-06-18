<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/admin_check.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM notices WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash'] = "Notice deleted successfully.";
}
header("Location: notices.php");
exit;
