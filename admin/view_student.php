<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['flash'] = "Student not found.";
    header("Location: students.php");
    exit;
}

$pageTitle = "View Student";
include '../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-eye"></i> Student Details</h2>

<div class="card shadow">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center mb-3">
                <img src="../assets/uploads/<?php echo htmlspecialchars($student['profile_image']); ?>" 
                     onerror="this.src='../assets/uploads/default.png'" class="rounded-circle border" width="150" height="150" alt="profile">
                <h5 class="mt-3"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                <span class="badge bg-<?php echo $student['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($student['status']); ?>
                </span>
            </div>
            <div class="col-md-9">
                <table class="table table-borderless">
                    <tr>
                        <th width="200">Student ID</th>
                        <td><?php echo $student['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    </tr>
                    <tr>
                        <th>Course</th>
                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td><?php echo date('d M Y', strtotime($student['dob'])); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo nl2br(htmlspecialchars($student['address'] ?: 'N/A')); ?></td>
                    </tr>
                    <tr>
                        <th>Registered On</th>
                        <td><?php echo date('d M Y, h:i A', strtotime($student['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
            <a href="students.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
