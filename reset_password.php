<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['reset_student_email']) || !isset($_SESSION['reset_verified'])) {
    header("Location: forgot_password.php");
    exit;
}

$email   = $_SESSION['reset_student_email'];
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6)          $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "UPDATE students SET password=?, password_changed_by_admin=0, temp_password=NULL WHERE email=?"
        );
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            unset($_SESSION['reset_student_email'], $_SESSION['reset_verified']);
            $success = "Password reset successfully!";
        } else {
            $errors[] = "Failed to reset password.";
        }
        $stmt->close();
    }
}

$pageTitle = "Reset Password - Student Portal";
$baseDepth = "";
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="text-center mb-3"><i class="bi bi-key-fill text-primary"></i> Reset Password</h3>
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <span class="badge rounded-pill bg-success px-3 py-2">1 Email ✓</span>
                        <span class="badge rounded-pill bg-success px-3 py-2">2 OTP ✓</span>
                        <span class="badge rounded-pill bg-primary px-3 py-2">3 Reset</span>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php" class="btn btn-primary btn-sm ms-2">Login Now</a></div>
                    <?php else: ?>
                        <p class="text-muted text-center">Setting new password for <strong><?php echo htmlspecialchars($email); ?></strong></p>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control form-control-lg" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control form-control-lg" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-check-circle"></i> Reset Password
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
