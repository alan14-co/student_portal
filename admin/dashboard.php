<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

// Total students
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM students");
$totalStudents = $totalResult->fetch_assoc()['total'];

// Active students
$activeResult = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'Active'");
$activeStudents = $activeResult->fetch_assoc()['total'];

// Inactive students
$inactiveResult = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'Inactive'");
$inactiveStudents = $inactiveResult->fetch_assoc()['total'];

// Latest 5 students
$latestResult = $conn->query("SELECT id, full_name, email, course, status, profile_image, created_at FROM students ORDER BY id DESC LIMIT 5");

$pageTitle = "Admin Dashboard";
include '../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary shadow">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Total Students</h6>
                    <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                </div>
                <i class="bi bi-people-fill fs-1"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success shadow">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Active Students</h6>
                    <h2 class="mb-0"><?php echo $activeStudents; ?></h2>
                </div>
                <i class="bi bi-person-check-fill fs-1"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger shadow">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title">Inactive Students</h6>
                    <h2 class="mb-0"><?php echo $inactiveStudents; ?></h2>
                </div>
                <i class="bi bi-person-x-fill fs-1"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Latest 5 Students</h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($latestResult->num_rows > 0): ?>
                    <?php $i = 1; while ($row = $latestResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><img src="../assets/uploads/<?php echo htmlspecialchars($row['profile_image']); ?>" 
                                 onerror="this.src='../assets/uploads/default.png'" class="rounded-circle" width="40" height="40" alt="profile"></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $row['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="view_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
