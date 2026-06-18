<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['admin_id'])) { header("Location: admin/dashboard.php"); exit; }
if (isset($_SESSION['student_id'])) { header("Location: student/dashboard.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'student';
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {
        if ($loginType === 'admin') {
            $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, email, password, status, password_changed_by_admin FROM students WHERE email = ?");
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    if ($row['status'] === 'Inactive') {
                        $error = "Your account is inactive. Please contact admin.";
                    } else {
                        $_SESSION['student_id'] = $row['id'];
                        $_SESSION['student_name'] = $row['full_name'];

                        // If admin had changed the password, clear the notification flag and temp password now that student logged in
                        if (!empty($row['password_changed_by_admin'])) {
                            $upd = $conn->prepare("UPDATE students SET password_changed_by_admin = 0, temp_password = NULL WHERE id = ?");
                            $upd->bind_param("i", $row['id']);
                            $upd->execute();
                            $upd->close();
                        }

                        header("Location: student/dashboard.php");
                        exit;
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        }
    }
}

$pageTitle = "Login - Student Portal";
$baseDepth = "";
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="bi bi-box-arrow-in-right"></i> Login</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <ul class="nav nav-pills nav-justified mb-3" id="loginTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-type="student" type="button">Student</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-type="admin" type="button">Admin</button>
                        </li>
                    </ul>

                    <form method="POST" action="login.php">
                        <input type="hidden" name="login_type" id="login_type" value="student">
                        <div class="mb-3">
                            <label class="form-label" id="identifierLabel">Email Address</label>
                            <input type="text" name="identifier" id="identifier" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        Don't have an account? <a href="register.php">Register here</a>
                    </p>
                    <p class="text-center mt-2 mb-0">
                        <a href="forgot_password.php"><i class="bi bi-shield-lock"></i> Forgot Password?</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('#loginTabs .nav-link').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#loginTabs .nav-link').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const type = this.dataset.type;
        document.getElementById('login_type').value = type;
        document.getElementById('identifierLabel').textContent = type === 'admin' ? 'Username' : 'Email Address';
        document.getElementById('identifier').type = type === 'admin' ? 'text' : 'email';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
