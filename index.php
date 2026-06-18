<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit;
} elseif (isset($_SESSION['student_id'])) {
    header("Location: student/dashboard.php");
    exit;
}
$pageTitle = "Welcome - Student Portal";
$baseDepth = "";
include 'includes/header.php';
?>
<div class="container text-center py-5">
    <div class="card shadow-lg p-5 mx-auto" style="max-width:600px;">
        <i class="bi bi-mortarboard-fill display-1 text-primary"></i>
        <h1 class="mt-3">Student Management Portal</h1>
        <p class="text-muted">Manage student records efficiently with our complete portal system.</p>
        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="login.php" class="btn btn-primary px-4">Login</a>
            <a href="register.php" class="btn btn-outline-primary px-4">Register</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
