<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';
require_once '../includes/helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) { $_SESSION['flash']="Student not found."; header("Location: students.php"); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email']     ?? '');
    $password   = $_POST['password']       ?? '';
    $phone      = trim($_POST['phone']     ?? '');
    $gender     = $_POST['gender']         ?? '';
    $course     = trim($_POST['course']    ?? '');
    $dob        = $_POST['dob']            ?? '';
    $address    = trim($_POST['address']   ?? '');
    $status     = $_POST['status']         ?? 'Active';

    if ($full_name === '') $errors[] = "Full name is required.";
    if ($email==='' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (!in_array($gender, ['Male','Female','Other'])) $errors[] = "Please select a gender.";
    if ($course === '') $errors[] = "Course is required.";
    if ($dob    === '') $errors[] = "Date of birth is required.";
    if (!in_array($status, ['Active','Inactive']))    $errors[] = "Invalid status.";

    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM students WHERE email=? AND id!=?");
        $chk->bind_param("si", $email, $id);
        $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) $errors[] = "Email already used by another student.";
        $chk->close();
    }

    // Image handling
    $profile_image = $student['profile_image'];
    $editedImage   = $_POST['profile_image-edited'] ?? '';
    if (empty($errors) && $editedImage !== '') {
        $saved = save_base64_image($editedImage, '../assets/uploads');
        if ($saved) {
            if ($profile_image !== 'default.png' && file_exists('../assets/uploads/'.$profile_image)) unlink('../assets/uploads/'.$profile_image);
            $profile_image = $saved;
        } else { $errors[] = "Failed to save edited image."; }
    } elseif (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===0) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext,['jpg','jpeg','png'])) { $errors[] = "Only JPG/PNG allowed."; }
        elseif ($_FILES['profile_image']['size'] > 2*1024*1024) { $errors[] = "Image must be under 2MB."; }
        else {
            $newName = 'student_'.time().'_'.uniqid().'.'.$ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], '../assets/uploads/'.$newName)) {
                if ($profile_image !== 'default.png' && file_exists('../assets/uploads/'.$profile_image)) unlink('../assets/uploads/'.$profile_image);
                $profile_image = $newName;
            } else { $errors[] = "Upload failed."; }
        }
    }

    if (empty($errors)) {
        if ($password !== '') {
            if (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                // Mark that admin changed the password + store plain text temporarily so student sees the new password on next login
                $stmt = $conn->prepare(
                    "UPDATE students SET full_name=?,email=?,password=?,phone=?,gender=?,course=?,dob=?,profile_image=?,address=?,status=?,password_changed_by_admin=1,temp_password=? WHERE id=?"
                );
                $stmt->bind_param("sssssssssssi", $full_name,$email,$hashed,$phone,$gender,$course,$dob,$profile_image,$address,$status,$password,$id);
            }
        } else {
            $stmt = $conn->prepare(
                "UPDATE students SET full_name=?,email=?,phone=?,gender=?,course=?,dob=?,profile_image=?,address=?,status=? WHERE id=?"
            );
            $stmt->bind_param("ssssssssssi", $full_name,$email,$phone,$gender,$course,$dob,$profile_image,$address,$status,$id);
        }
        if (empty($errors)) {
            if ($stmt->execute()) {
                $_SESSION['flash'] = "Student updated successfully.";
                header("Location: students.php"); exit;
            } else { $errors[] = "Failed to update student."; }
            $stmt->close();
        }
    }

    $student = array_merge($student, compact('full_name','email','phone','gender','course','dob','address','status','profile_image'));
}

$pageTitle = "Edit Student";
include '../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Student</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="card shadow">
    <div class="card-body">
        <div class="text-center mb-3">
            <img src="../assets/uploads/<?php echo htmlspecialchars($student['profile_image']); ?>"
                 onerror="this.src='../assets/uploads/default.png'"
                 class="rounded-circle border" id="profile_image-preview" width="110" height="110" alt="profile">
        </div>
        <form method="POST" action="edit_student.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="Enter new password to change">
                    <small class="text-info"><i class="bi bi-info-circle"></i> If you set a new password, the student will be notified on their next login.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="Male"   <?php echo $student['gender']==='Male'  ?'selected':''; ?>>Male</option>
                        <option value="Female" <?php echo $student['gender']==='Female'?'selected':''; ?>>Female</option>
                        <option value="Other"  <?php echo $student['gender']==='Other' ?'selected':''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Course *</label>
                    <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($student['course']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="Active"   <?php echo $student['status']==='Active'  ?'selected':''; ?>>Active</option>
                        <option value="Inactive" <?php echo $student['status']==='Inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Profile Image <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="file" name="profile_image" id="profile_image" class="form-control" accept=".jpg,.jpeg,.png">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Student</button>
                <a href="students.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
