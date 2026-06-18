<?php
session_start();
require_once 'includes/db.php';

// Only admins can search
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$searchTerm = "%$search%";

// Count total
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE full_name LIKE ? OR email LIKE ? OR course LIKE ?");
$countStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$countStmt->execute();
$totalRow = $countStmt->get_result()->fetch_assoc();
$total = $totalRow['total'];
$countStmt->close();

$totalPages = ceil($total / $limit);

$stmt = $conn->prepare("SELECT id, full_name, email, phone, course, status, profile_image FROM students 
                        WHERE full_name LIKE ? OR email LIKE ? OR course LIKE ? 
                        ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

echo json_encode([
    'students' => $students,
    'total' => $total,
    'page' => $page,
    'totalPages' => $totalPages
]);
