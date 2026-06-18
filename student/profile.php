<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/student_check.php';
require_once '../includes/helpers.php';

$id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$student) { header("Location: ../logout.php"); exit; }

$errors   = [];
$success  = '';
$activeTab = $_GET['tab'] ?? 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Profile update ──────────────────────────────────────────
    if (isset($_POST['update_profile'])) {
        $activeTab = 'profile';
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $phone     = trim($_POST['phone'] ?? '');
        $gender    = $_POST['gender'] ?? '';
        $course    = trim($_POST['course'] ?? '');
        $dob       = $_POST['dob'] ?? '';
        $address   = trim($_POST['address'] ?? '');

        if ($full_name === '') {
            $errors[] = "Full name is required.";
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        if (!in_array($gender, ['Male','Female','Other'])) {
            $errors[] = "Please select a gender.";
        }
        if ($course === '') {
            $errors[] = "Course is required.";
        }
        if ($dob === '') {
            $errors[] = "Date of birth is required.";
        }

        if (empty($errors)) {
            $emailCheck = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
            $emailCheck->bind_param("si", $email, $id);
            $emailCheck->execute();
            $emailCheck->store_result();
            if ($emailCheck->num_rows > 0) {
                $errors[] = "Email is already in use by another account.";
            }
            $emailCheck->close();
        }

        $profile_image = $student['profile_image'];
        $editedImage   = $_POST['profile_image-edited'] ?? '';

        if ($editedImage !== '') {
            $saved = save_base64_image($editedImage, '../assets/uploads');
            if ($saved) {
                if ($profile_image !== 'default.png' && file_exists('../assets/uploads/'.$profile_image)) unlink('../assets/uploads/'.$profile_image);
                $profile_image = $saved;
            } else { $errors[] = "Failed to save edited image."; }
        } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===0) {
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
            $stmt = $conn->prepare("UPDATE students SET full_name=?, email=?, phone=?, gender=?, course=?, dob=?, address=?, profile_image=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $full_name, $email, $phone, $gender, $course, $dob, $address, $profile_image, $id);
            if ($stmt->execute()) {
                $success = "Profile updated successfully.";
                $student['full_name'] = $full_name;
                $student['email'] = $email;
                $student['phone'] = $phone;
                $student['gender'] = $gender;
                $student['course'] = $course;
                $student['dob'] = $dob;
                $student['address'] = $address;
                $student['profile_image'] = $profile_image;
            } else { $errors[] = "Failed to update profile."; }
            $stmt->close();
        }
    }

    // ── Change password ─────────────────────────────────────────
    elseif (isset($_POST['change_password'])) {
        $activeTab       = 'password';
        $current_pass    = $_POST['current_password'] ?? '';
        $new_pass        = $_POST['new_password']     ?? '';
        $confirm_pass    = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_pass, $student['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_pass) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = "New passwords do not match.";
        }

        if (empty($errors)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            // Clear admin-changed flag and temp password
            $stmt = $conn->prepare(
                "UPDATE students SET password=?, password_changed_by_admin=0, temp_password=NULL WHERE id=?"
            );
            $stmt->bind_param("si", $hashed, $id);
            if ($stmt->execute()) {
                $success = "Password changed successfully.";
                $student['password'] = $hashed;
                $student['password_changed_by_admin'] = 0;
                $student['temp_password'] = null;
            } else { $errors[] = "Failed to change password."; }
            $stmt->close();
        }
    }
}

$pageTitle = "My Profile";
include '../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-person-circle"></i> My Profile</h2>

<?php if ($student['password_changed_by_admin']): ?>
<div class="alert alert-warning border-start border-4 border-warning">
    <i class="bi bi-shield-exclamation"></i>
    <strong>Admin has changed your password.</strong>
    <?php if ($student['temp_password']): ?>
        Your temporary password is: <code><?php echo htmlspecialchars($student['temp_password']); ?></code>.
    <?php endif; ?>
    Please change it below using the <strong>Change Password</strong> tab.
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab==='profile'?'active':''; ?>" href="profile.php?tab=profile">
            <i class="bi bi-person"></i> Profile Info
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab==='password'?'active':''; ?> <?php echo $student['password_changed_by_admin']?'text-danger fw-bold':''; ?>" href="profile.php?tab=password">
            <i class="bi bi-key"></i> Change Password <?php echo $student['password_changed_by_admin']?'⚠':''; ?>
        </a>
    </li>
</ul>

<?php if ($activeTab === 'profile'): ?>
<div class="card shadow">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center mb-3">
                <img src="../assets/uploads/<?php echo htmlspecialchars($student['profile_image']); ?>"
                     onerror="this.src='../assets/uploads/default.png'"
                     class="rounded-circle border mb-3" width="140" height="140"
                     id="profile_image-preview" alt="profile">
                <h6><?php echo htmlspecialchars($student['full_name']); ?></h6>
                <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
            </div>
            <div class="col-md-9">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($student['course']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $student['gender']==='Male'?'selected':''; ?>>Male</option>
                                <option value="Female" <?php echo $student['gender']==='Female'?'selected':''; ?>>Female</option>
                                <option value="Other" <?php echo $student['gender']==='Other'?'selected':''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Update Profile Image (jpg/jpeg/png)</label>
                            <input type="file" name="profile_image" id="profile_image" class="form-control" accept=".jpg,.jpeg,.png">
                            <small class="text-muted">Selected image will be previewed and uploaded.</small>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary mt-4">
                        <i class="bi bi-check-circle"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="card shadow">
    <div class="card-body" style="max-width:500px;">
        <h5 class="mb-3"><i class="bi bi-key-fill text-warning"></i> Change Your Password</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
                <?php if ($student['temp_password']): ?>
                    <small class="text-muted">Use your temporary password: <code><?php echo htmlspecialchars($student['temp_password']); ?></code></small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-warning">
                <i class="bi bi-shield-lock"></i> Change Password
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
