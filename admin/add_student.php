<?php
$baseDepth = "../";
require_once '../includes/db.php';
require_once '../includes/admin_check.php';
require_once '../includes/helpers.php';

$errors = [];
$success = '';

$full_name = $email = $phone = $gender = $course = $dob = $address = '';
$status = 'Active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $course = trim($_POST['course'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if ($full_name === '') $errors[] = "Full name is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if (!in_array($gender, ['Male','Female','Other'])) $errors[] = "Please select a gender.";
    if ($course === '') $errors[] = "Course is required.";
    if ($dob === '') $errors[] = "Date of birth is required.";
    
    if (!in_array($status, ['Active','Inactive'])) $errors[] = "Invalid status.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists.";
        }
        $stmt->close();
    }

    $profile_image = 'default.png';
    $editedImage = $_POST['profile_image-edited'] ?? '';

    if (empty($errors) && $editedImage !== '') {
        $saved = save_base64_image($editedImage, '../assets/uploads');
        if ($saved) {
            $profile_image = $saved;
        } else {
            $errors[] = "Failed to save edited profile image.";
        }
    } elseif (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $tmp = $_FILES['profile_image']['tmp_name'];
        $size = $_FILES['profile_image']['size'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, JPEG, PNG files are allowed.";
        } elseif ($size > 2 * 1024 * 1024) {
            $errors[] = "Image must be less than 2MB.";
        } else {
            $newName = 'student_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadPath = '../assets/uploads/' . $newName;
            if (move_uploaded_file($tmp, $uploadPath)) {
                $profile_image = $newName;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO students (full_name, email, password, phone, gender, course, dob, profile_image, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $full_name, $email, $hashedPassword, $phone, $gender, $course, $dob, $profile_image, $address, $status);
        if ($stmt->execute()) {
            $_SESSION['flash'] = "Student added successfully.";
            header("Location: students.php");
            exit;
        } else {
            $errors[] = "Failed to add student.";
        }
        $stmt->close();
    }
}

$pageTitle = "Add Student";
include '../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-person-plus"></i> Add Student</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="add_student.php" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo $gender==='Male'?'selected':''; ?>>Male</option>
                        <option value="Female" <?php echo $gender==='Female'?'selected':''; ?>>Female</option>
                        <option value="Other" <?php echo $gender==='Other'?'selected':''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Course *</label>
                    <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($course); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($dob); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="Active" <?php echo $status==='Active'?'selected':''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status==='Inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Profile Image (jpg, jpeg, png)</label>
                        <div class="d-flex align-items-center gap-3">
                        <img id="profile_image-preview" src="../assets/uploads/default.png" class="rounded-circle border" width="60" height="60" alt="preview">
                        <input type="file" name="profile_image" id="profile_image" class="form-control" accept=".jpg,.jpeg,.png">
                    </div>
                    <small class="text-muted">Selected image will be previewed and uploaded.</small>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Add Student</button>
                <a href="students.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- image editing modal removed -->

<?php include '../includes/footer.php'; ?>
