<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Fetch profile image to delete the file
    $stmt = $conn->prepare("SELECT profile_image FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if ($student) {
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Delete profile image file if not default
            if ($student['profile_image'] !== 'default.png' && file_exists('../assets/uploads/' . $student['profile_image'])) {
                unlink('../assets/uploads/' . $student['profile_image']);
            }
            $_SESSION['flash'] = "Student deleted successfully.";
        } else {
            $_SESSION['flash'] = "Failed to delete student.";
        }
        $stmt->close();
    } else {
        $_SESSION['flash'] = "Student not found.";
    }
}

header("Location: students.php");
exit;
