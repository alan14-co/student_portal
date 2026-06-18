<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$searchTerm = "%$search%";

// Count total
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE full_name LIKE ? OR email LIKE ? OR course LIKE ?");
$countStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = max(1, ceil($total / $limit));

$stmt = $conn->prepare("SELECT id, full_name, email, phone, course, status, profile_image FROM students 
                        WHERE full_name LIKE ? OR email LIKE ? OR course LIKE ? 
                        ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Flash message
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$pageTitle = "Manage Students";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2><i class="bi bi-people"></i> Manage Students</h2>
    <a href="add_student.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Student</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>

<div class="card shadow mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email, or course..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <a href="../export_csv.php" class="btn btn-success w-100"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle" id="studentsTable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="studentsBody">
                <?php if ($result->num_rows > 0): ?>
                    <?php $i = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><img src="../assets/uploads/<?php echo htmlspecialchars($row['profile_image']); ?>" 
                                 onerror="this.src='../assets/uploads/default.png'" class="rounded-circle" width="40" height="40" alt="profile"></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $row['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="view_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this student?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <nav id="paginationNav">
            <ul class="pagination justify-content-center" id="paginationList">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p == $page ? 'active' : ''; ?>">
                        <a class="page-link page-link-ajax" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>" data-page="<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
