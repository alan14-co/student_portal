<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/mailer.php';

if (isset($_SESSION['admin_id']))   { header("Location: admin/dashboard.php");   exit; }
if (isset($_SESSION['student_id'])) { header("Location: student/dashboard.php"); exit; }

$errors  = [];
$success = '';
$full_name = $email = $phone = $gender = $course = $dob = $address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name']        ?? '');
    $email            = strtolower(trim($_POST['email'] ?? ''));
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';
    $phone            = trim($_POST['phone']            ?? '');
    $gender           = $_POST['gender']                ?? '';
    $course           = trim($_POST['course']           ?? '');
    $dob              = $_POST['dob']                   ?? '';
    $address          = trim($_POST['address']          ?? '');

    if ($full_name === '')  $errors[] = "Full name is required.";
    if ($email === '')      $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = "Valid email format required.";
    elseif (!is_allowed_email($email))                   $errors[] = "Only gmail.com and rayblaze.com email addresses are accepted.";
    if (strlen($password) < 6)          $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (!in_array($gender,['Male','Female','Other'])) $errors[] = "Please select a gender.";
    if ($course === '') $errors[] = "Course is required.";
    if ($dob    === '') $errors[] = "Date of birth is required.";

    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM students WHERE email=?");
        $chk->bind_param("s", $email); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) $errors[] = "Email already registered.";
        $chk->close();
    }

    $profile_image = 'default.png';
    $editedImage   = $_POST['profile_image-edited'] ?? '';
    if (empty($errors) && $editedImage !== '') {
        $saved = save_base64_image($editedImage, 'assets/uploads');
        $saved ? $profile_image = $saved : $errors[] = "Failed to save edited image.";
    } elseif (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error']===0) {
        $ext  = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $tmp  = $_FILES['profile_image']['tmp_name'];
        $size = $_FILES['profile_image']['size'];
        if (!in_array($ext,['jpg','jpeg','png'])) { $errors[] = "Only JPG/PNG allowed."; }
        elseif ($size > 2*1024*1024)              { $errors[] = "Image must be under 2MB."; }
        else {
            $newName = 'student_'.time().'_'.uniqid().'.'.$ext;
            if (move_uploaded_file($tmp,'assets/uploads/'.$newName)) $profile_image = $newName;
            else $errors[] = "Upload failed.";
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO students (full_name,email,password,phone,gender,course,dob,profile_image,address,status)
             VALUES (?,?,?,?,?,?,?,?,?,'Active')"
        );
        $stmt->bind_param("sssssssss", $full_name,$email,$hashed,$phone,$gender,$course,$dob,$profile_image,$address);
        if ($stmt->execute()) {
            $success = "Registration successful! You can now login.";
            $full_name=$email=$phone=$gender=$course=$dob=$address='';
        } else { $errors[] = "Registration failed. Please try again."; }
        $stmt->close();
    }
}

$pageTitle = "Register - Student Portal";
$baseDepth = "";
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="bi bi-person-plus-fill"></i> Student Registration</h3>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Login now</a></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email * <small class="text-muted">(gmail.com or rayblaze.com only)</small></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="you@gmail.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male"   <?php echo $gender==='Male'  ?'selected':''; ?>>Male</option>
                                    <option value="Female" <?php echo $gender==='Female'?'selected':''; ?>>Female</option>
                                    <option value="Other"  <?php echo $gender==='Other' ?'selected':''; ?>>Other</option>
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
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Profile Image (jpg, jpeg, png)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <img id="profile_image-preview" src="assets/uploads/default.png" class="rounded-circle border" width="60" height="60" alt="preview">
                                    <input type="file" name="profile_image" id="profile_image" class="form-control" accept=".jpg,.jpeg,.png">
                                </div>
                                <small class="text-muted">Selected image will be previewed and uploaded.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-4">Register</button>
                    </form>
                    <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- image editing modal removed -->
<?php include 'includes/footer.php'; ?>
