<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Student Portal'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropper.js/1.6.1/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropper.js/1.6.1/cropper.min.js"></script>
    <?php $depth = isset($baseDepth) ? $baseDepth : ''; ?>
    <link href="<?php echo $depth; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="<?php echo $depth; ?>index.php">
        <i class="bi bi-mortarboard-fill"></i> Student Portal
    </a>
    <div class="ms-auto d-flex align-items-center">
        <?php if (isset($_SESSION['admin_id'])): ?>
            <span class="text-light me-3">Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="<?php echo $depth; ?>logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        <?php elseif (isset($_SESSION['student_id'])): ?>
            <span class="text-light me-3">Hi, <?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
            <a href="<?php echo $depth; ?>logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        <?php else: ?>
            <a href="<?php echo $depth; ?>login.php"    class="btn btn-outline-light btn-sm me-2">Login</a>
            <a href="<?php echo $depth; ?>register.php" class="btn btn-light btn-sm">Register</a>
        <?php endif; ?>
    </div>
</nav>
<div class="d-flex">
<?php if (isset($_SESSION['admin_id']) || isset($_SESSION['student_id'])): ?>
    <div class="sidebar bg-light border-end" id="sidebar">
        <ul class="nav flex-column p-2">
            <?php if (isset($_SESSION['admin_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>admin/students.php"><i class="bi bi-people"></i> Students</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>admin/add_student.php"><i class="bi bi-person-plus"></i> Add Student</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>admin/notices.php"><i class="bi bi-megaphone-fill text-warning"></i> Notices</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>admin/add_notice.php"><i class="bi bi-plus-circle"></i> Add Notice</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>export_csv.php"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a></li>
            <?php elseif (isset($_SESSION['student_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>student/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $depth; ?>student/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-danger" href="<?php echo $depth; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>
<?php endif; ?>
    <div class="content-area flex-grow-1 p-4">
