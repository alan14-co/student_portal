<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

$errors = [];
$title = $content = $type = $priority = $expires_at = '';
$type     = 'public';
$priority = 'normal';
$student_id = '';

// Fetch all students for specific notice dropdown
$students = $conn->query("SELECT id, full_name, email FROM students WHERE status='Active' ORDER BY full_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']      ?? '');
    $content    = trim($_POST['content']    ?? '');
    $type       = $_POST['type']            ?? 'public';
    $priority   = $_POST['priority']        ?? 'normal';
    $student_id = $_POST['student_id']      ?? '';
    $expires_at = trim($_POST['expires_at'] ?? '');
    $is_active  = 1;

    if ($title   === '') $errors[] = "Title is required.";
    if ($content === '') $errors[] = "Content is required.";
    if (!in_array($type, ['public','specific']))                    $errors[] = "Invalid type.";
    if (!in_array($priority, ['normal','important','urgent']))      $errors[] = "Invalid priority.";
    if ($type === 'specific' && ($student_id === '' || !is_numeric($student_id))) $errors[] = "Please select a student.";

    $sid        = ($type === 'specific' && $student_id !== '') ? (int)$student_id : null;
    $expires_db = ($expires_at !== '') ? $expires_at . ' 23:59:59' : null;
    $admin_id   = $_SESSION['admin_id'];

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO notices (title, content, type, student_id, priority, is_active, created_by, expires_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)"
        );
        $stmt->bind_param("sssisis", $title, $content, $type, $sid, $priority, $admin_id, $expires_db);
        if ($stmt->execute()) {
            $_SESSION['flash'] = "Notice added successfully.";
            header("Location: notices.php");
            exit;
        } else {
            $errors[] = "Failed to add notice.";
        }
        $stmt->close();
    }
}

$pageTitle = "Add Notice";
include '../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-megaphone-fill text-warning"></i> Add Notice / Announcement</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Notice Title *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Priority *</label>
                    <select name="priority" class="form-select" required>
                        <option value="normal"    <?php echo $priority==='normal'   ?'selected':''; ?>>Normal</option>
                        <option value="important" <?php echo $priority==='important'?'selected':''; ?>>Important</option>
                        <option value="urgent"    <?php echo $priority==='urgent'   ?'selected':''; ?>>Urgent</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Content *</label>
                    <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($content); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notice Type *</label>
                    <select name="type" class="form-select" id="noticeType" required>
                        <option value="public"   <?php echo $type==='public'  ?'selected':''; ?>>🌐 Public (All Students)</option>
                        <option value="specific" <?php echo $type==='specific'?'selected':''; ?>>👤 Specific Student</option>
                    </select>
                </div>
                <div class="col-md-4" id="studentSelectBox" style="display:<?php echo $type==='specific'?'block':'none'; ?>">
                    <label class="form-label">Select Student *</label>
                    <select name="student_id" class="form-select" id="studentSelect">
                        <option value="">-- Select Student --</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $student_id==$s['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($s['full_name']) . ' (' . htmlspecialchars($s['email']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date <small class="text-muted">(optional)</small></label>
                    <input type="date" name="expires_at" class="form-control" value="<?php echo htmlspecialchars($expires_at); ?>" min="<?php echo date('Y-m-d'); ?>">
                    <small class="text-muted">Leave blank for no expiry.</small>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Add Notice</button>
                <a href="notices.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('noticeType').addEventListener('change', function() {
    const box = document.getElementById('studentSelectBox');
    const sel = document.getElementById('studentSelect');
    if (this.value === 'specific') {
        box.style.display = 'block';
        sel.setAttribute('required','required');
    } else {
        box.style.display = 'none';
        sel.removeAttribute('required');
        sel.value = '';
    }
});
</script>
<?php include '../includes/footer.php'; ?>
