<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

// Build filters from GET
$priority = $_GET['priority'] ?? '';
$created_date = $_GET['created_date'] ?? '';
$expires_date = $_GET['expires_date'] ?? '';
$student_id = $_GET['student_id'] ?? '';

// Base query
$sql = "SELECT n.*, s.full_name AS student_name FROM notices n LEFT JOIN students s ON s.id = n.student_id WHERE 1=1";

// Valid priorities
$validPriorities = ['normal', 'important', 'urgent'];

// Add filters to query (date validation ensures safe format)
if ($priority !== '' && in_array($priority, $validPriorities, true)) {
    $sql .= " AND n.priority = '" . $conn->real_escape_string($priority) . "'";
}
if ($created_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $created_date)) {
    $sql .= " AND DATE(n.created_at) = '" . $conn->real_escape_string($created_date) . "'";
}
if ($expires_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires_date)) {
    $sql .= " AND DATE(n.expires_at) = '" . $conn->real_escape_string($expires_date) . "'";
}
if ($student_id !== '' && ctype_digit($student_id)) {
    $sql .= " AND n.student_id = '" . $conn->real_escape_string($student_id) . "'";
}

$sql .= ' ORDER BY n.created_at DESC';

$notices = $conn->query($sql);

// Fetch students for filter dropdown
$students = $conn->query("SELECT id, full_name FROM students ORDER BY full_name");

$pageTitle = "Manage Notices";
include '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2><i class="bi bi-megaphone-fill text-warning"></i> Notices / Announcements</h2>
    <a href="add_notice.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Notice</a>
</div>

<form method="get" action="notices.php" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <select name="priority" class="form-select form-select-sm">
            <option value="">All Priorities</option>
            <option value="normal" <?php echo $priority==='normal'?'selected':''; ?>>Normal</option>
            <option value="important" <?php echo $priority==='important'?'selected':''; ?>>Important</option>
            <option value="urgent" <?php echo $priority==='urgent'?'selected':''; ?>>Urgent</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="student_id" class="form-select form-select-sm">
            <option value="">All Students</option>
            <?php if (isset($students) && $students && $students->num_rows): while ($s = $students->fetch_assoc()): ?>
                <option value="<?php echo $s['id']; ?>" <?php echo ($student_id !== '' && $student_id == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['full_name']); ?></option>
            <?php endwhile; endif; ?>
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="created_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($created_date); ?>">
    </div>
    <div class="col-auto">
        <input type="date" name="expires_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($expires_date); ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="notices.php" class="btn btn-sm btn-secondary">Clear</a>
    </div>
</form>

<?php if ($flash): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>For Student</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Expires</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($notices->num_rows > 0): $i=1; while ($row = $notices->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                    <td>
                        <?php if ($row['type']==='public'): ?>
                            <span class="badge bg-info text-dark"><i class="bi bi-globe"></i> Public</span>
                        <?php else: ?>
                            <span class="badge bg-purple" style="background:#6f42c1"><i class="bi bi-person"></i> Specific</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['type']==='specific' ? htmlspecialchars($row['student_name'] ?? 'N/A') : '<span class="text-muted">All Students</span>'; ?></td>
                    <td>
                        <?php
                        $pc = ['normal'=>'bg-secondary','important'=>'bg-warning text-dark','urgent'=>'bg-danger'];
                        echo "<span class='badge {$pc[$row['priority']]}'>" . ucfirst($row['priority']) . "</span>";
                        ?>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $row['is_active']?'success':'secondary'; ?>">
                            <?php echo $row['is_active']?'Active':'Inactive'; ?>
                        </span>
                    </td>
                    <td><?php echo $row['expires_at'] ? date('d M Y', strtotime($row['expires_at'])) : '<span class="text-muted">No expiry</span>'; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="edit_notice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="toggle_notice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-<?php echo $row['is_active']?'secondary':'success'; ?>" title="<?php echo $row['is_active']?'Deactivate':'Activate'; ?>">
                            <i class="bi bi-<?php echo $row['is_active']?'eye-slash':'eye'; ?>"></i>
                        </a>
                        <a href="delete_notice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete"
                           onclick="return confirm('Delete this notice?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No notices found. <a href="add_notice.php">Add one now</a>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
