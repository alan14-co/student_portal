<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM notices WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$notice) { $_SESSION['flash']="Notice not found."; header("Location: notices.php"); exit; }

$errors = [];
$students = $conn->query("SELECT id, full_name, email FROM students WHERE status='Active' ORDER BY full_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']   ?? '');
    $content    = trim($_POST['content'] ?? '');
    $type       = $_POST['type']         ?? 'public';
    $priority   = $_POST['priority']     ?? 'normal';
    $student_id = $_POST['student_id']   ?? '';
    $expires_at = trim($_POST['expires_at'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if ($title   === '') $errors[] = "Title is required.";
    if ($content === '') $errors[] = "Content is required.";
    if ($type === 'specific' && ($student_id === '' || !is_numeric($student_id))) $errors[] = "Please select a student.";

    $sid        = ($type === 'specific' && $student_id !== '') ? (int)$student_id : null;
    $expires_db = ($expires_at !== '') ? $expires_at . ' 23:59:59' : null;

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "UPDATE notices SET title=?, content=?, type=?, student_id=?, priority=?, is_active=?, expires_at=? WHERE id=?"
        );
        $stmt->bind_param("sssisisi", $title, $content, $type, $sid, $priority, $is_active, $expires_db, $id);
        if ($stmt->execute()) {
            $_SESSION['flash'] = "Notice updated successfully.";
            header("Location: notices.php");
            exit;
        } else { $errors[] = "Failed to update notice."; }
        $stmt->close();
    }
    $notice = array_merge($notice, compact('title','content','type','priority','is_active') + ['student_id'=>$student_id,'expires_at'=>$expires_at]);
}

$exp = $notice['expires_at'] ? date('Y-m-d', strtotime($notice['expires_at'])) : '';
$pageTitle = "Edit Notice";
include '../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Notice</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Notice Title *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($notice['title']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Priority *</label>
                    <select name="priority" class="form-select">
                        <option value="normal"    <?php echo $notice['priority']==='normal'   ?'selected':''; ?>>Normal</option>
                        <option value="important" <?php echo $notice['priority']==='important'?'selected':''; ?>>Important</option>
                        <option value="urgent"    <?php echo $notice['priority']==='urgent'   ?'selected':''; ?>>Urgent</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Content *</label>
                    <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($notice['content']); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notice Type *</label>
                    <select name="type" class="form-select" id="noticeType">
                        <option value="public"   <?php echo $notice['type']==='public'  ?'selected':''; ?>>🌐 Public (All Students)</option>
                        <option value="specific" <?php echo $notice['type']==='specific'?'selected':''; ?>>👤 Specific Student</option>
                    </select>
                </div>
                <div class="col-md-4" id="studentSelectBox" style="display:<?php echo $notice['type']==='specific'?'block':'none'; ?>">
                    <label class="form-label">Select Student</label>
                    <select name="student_id" class="form-select" id="studentSelect">
                        <option value="">-- Select Student --</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $notice['student_id']==$s['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($s['full_name']) . ' (' . htmlspecialchars($s['email']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expires_at" class="form-control" value="<?php echo htmlspecialchars($exp); ?>" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo $notice['is_active']?'checked':''; ?>>
                        <label class="form-check-label" for="isActive">Active (visible to students)</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Notice</button>
                <a href="notices.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('noticeType').addEventListener('change', function() {
    const box = document.getElementById('studentSelectBox');
    box.style.display = this.value === 'specific' ? 'block' : 'none';
});
</script>
<?php include '../includes/footer.php'; ?>
