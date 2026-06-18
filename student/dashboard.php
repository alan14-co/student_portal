<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/student_check.php';

$id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) { header("Location: ../logout.php"); exit; }

// Fetch notices: public ones + specific ones for this student
// Only active and not expired
$created_date = $_GET['created_date'] ?? '';
$expires_date = $_GET['expires_date'] ?? '';
$priority = $_GET['priority'] ?? '';

$now = date('Y-m-d H:i:s');
// Build notices query for this student (public + specific)
$sql = "SELECT * FROM notices WHERE is_active = 1";

// If user provided a created_date filter, match exact date
if ($created_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $created_date)) {
    $sql .= " AND DATE(created_at) = '" . $conn->real_escape_string($created_date) . "'";
}

// If user provided an expires_date filter, match exact expiry date
if ($expires_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires_date)) {
    $sql .= " AND DATE(expires_at) = '" . $conn->real_escape_string($expires_date) . "'";
} else {
    // otherwise only show non-expired (or no expiry)
    $sql .= " AND (expires_at IS NULL OR expires_at > '" . $conn->real_escape_string($now) . "')";
}

// student visibility
$sql .= " AND (type = 'public' OR (type = 'specific' AND student_id = '" . $conn->real_escape_string($id) . "'))";

// Priority filter
$validPriorities = ['normal','important','urgent'];
if ($priority !== '' && in_array($priority, $validPriorities, true)) {
    $sql .= " AND priority = '" . $conn->real_escape_string($priority) . "'";
}

$sql .= " ORDER BY FIELD(priority,'urgent','important','normal'), created_at DESC";

$notices = $conn->query($sql);

$pageTitle = "Student Dashboard";
include '../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Student Dashboard</h2>

<?php if ($student['password_changed_by_admin']): ?>
<!-- Admin Password Change Alert -->
<div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning shadow" role="alert">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-shield-exclamation fs-3 text-warning"></i>
        <div>
            <h5 class="mb-1">⚠️ Your Password Was Changed by Admin</h5>
            <p class="mb-1">An administrator has updated your account password.</p>
            <?php if ($student['temp_password']): ?>
                <p class="mb-1">Your new temporary password is: <code class="fs-5 bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($student['temp_password']); ?></code></p>
            <?php endif; ?>
            <p class="mb-2">For your security, please <strong>change your password immediately</strong>.</p>
            <a href="profile.php?tab=password" class="btn btn-warning btn-sm">
                <i class="bi bi-key"></i> Change Password Now
            </a>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Welcome Card -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <img src="../assets/uploads/<?php echo htmlspecialchars($student['profile_image']); ?>"
                     onerror="this.src='../assets/uploads/default.png'"
                     class="rounded-circle border" width="100" height="100" alt="profile">
            </div>
            <div class="col-md-10">
                <h3>Welcome, <?php echo htmlspecialchars($student['full_name']); ?>! 👋</h3>
                <p class="text-muted mb-0">Here's a quick overview of your profile and latest announcements.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Summary -->
    <div class="col-12">
        <div class="card shadow h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person-vcard"></i> My Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0 small">
                    <tr><th width="120">Full Name</th><td><?php echo htmlspecialchars($student['full_name']); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($student['email']); ?></td></tr>
                    <tr><th>Phone</th><td><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Gender</th><td><?php echo htmlspecialchars($student['gender']); ?></td></tr>
                    <tr><th>Course</th><td><?php echo htmlspecialchars($student['course']); ?></td></tr>
                    <tr><th>DOB</th><td><?php echo date('d M Y', strtotime($student['dob'])); ?></td></tr>
                    <tr><th>Status</th><td><span class="badge bg-<?php echo $student['status']==='Active'?'success':'secondary'; ?>"><?php echo $student['status']; ?></span></td></tr>
                    <tr><th>Joined</th><td><?php echo date('d M Y', strtotime($student['created_at'])); ?></td></tr>
                </table>
                <a href="profile.php" class="btn btn-primary btn-sm mt-2"><i class="bi bi-pencil"></i> Edit Profile</a>
            </div>
        </div>
    </div>

    <!-- Notices Panel -->
    <div class="col-12">
        <div class="card shadow h-100">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="bi bi-megaphone-fill text-warning"></i> Notices & Announcements</h5>
                    <span class="badge bg-primary"><?php echo $notices->num_rows; ?></span>
                </div>
                <form method="get" action="dashboard.php" class="row g-2">
                    <div class="col-auto">
                        <select name="priority" class="form-select form-select-sm">
                            <option value="">All Priorities</option>
                            <option value="normal" <?php echo ($priority==='normal')?'selected':''; ?>>Normal</option>
                            <option value="important" <?php echo ($priority==='important')?'selected':''; ?>>Important</option>
                            <option value="urgent" <?php echo ($priority==='urgent')?'selected':''; ?>>Urgent</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="created_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($created_date); ?>" placeholder="Created date">
                    </div>
                    <div class="col-auto">
                        <input type="date" name="expires_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($expires_date); ?>" placeholder="Expiry date">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Filter</button>
                        <a href="dashboard.php" class="btn btn-sm btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <?php if ($notices->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                    <?php while ($n = $notices->fetch_assoc()):
                        $border = ['urgent'=>'border-danger','important'=>'border-warning','normal'=>'border-info'];
                    ?>
                        <div class="list-group-item list-group-item-action border-0 border-start border-2 <?php echo $border[$n['priority']]; ?> py-4 mb-2">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($n['title']); ?></h6>
                                        <div class="d-flex gap-1">
                                            <?php if ($n['priority'] !== 'normal'): ?>
                                                <span class="badge bg-<?php echo $n['priority']==='urgent'?'danger':'warning text-dark'; ?>">
                                                    <?php echo ucfirst($n['priority']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($n['type']==='specific'): ?>
                                                <span class="badge bg-purple" style="background:#6f42c1">Personal</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mb-1 text-muted small"><?php echo nl2br(htmlspecialchars($n['content'])); ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?>
                                        <?php if ($n['expires_at']): ?>
                                            &nbsp;·&nbsp;<i class="bi bi-calendar-x"></i> Expires <?php echo date('d M Y', strtotime($n['expires_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-bell-slash fs-1"></i>
                        <p class="mt-2">No notices at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
