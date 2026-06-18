<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Gender', 'Course', 'DOB', 'Address', 'Status', 'Created At']);

$result = $conn->query("SELECT id, full_name, email, phone, gender, course, dob, address, status, created_at FROM students ORDER BY id ASC");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['gender'],
        $row['course'],
        $row['dob'],
        $row['address'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
