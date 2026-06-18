<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/mailer.php';

$step  = $_SESSION['otp_step']  ?? 1;
$email = $_SESSION['otp_email'] ?? '';
$error = '';
$info  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 1 — send OTP
    if (isset($_POST['step1'])) {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (!is_allowed_email($email)) {
            $error = "Only gmail.com and rayblaze.com addresses are accepted.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $found = $stmt->num_rows > 0;
            $stmt->close();

            if (!$found) {
                $error = "No account found with that email address.";
            } else {
                send_otp($conn, $email);
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_step']  = 2;
                $step = 2;

                // LOCALHOST HELPER — shows OTP on screen (remove in production)
                $ds = $conn->prepare("SELECT otp FROM otp_codes WHERE email=? AND used=0 ORDER BY id DESC LIMIT 1");
                $ds->bind_param("s", $email);
                $ds->execute();
                $dr = $ds->get_result()->fetch_assoc();
                $ds->close();
                $info = "OTP sent to <strong>" . htmlspecialchars($email) . "</strong>. Valid for 10 minutes.";
            }
        }
    }

    // STEP 2 — verify OTP
    elseif (isset($_POST['step2'])) {
        $email = $_SESSION['otp_email'] ?? '';
        $otp   = trim($_POST['otp'] ?? '');
        $step  = 2;

        if ($email === '') {
            $error = "Session expired. Please start again.";
            unset($_SESSION['otp_step'], $_SESSION['otp_email']);
            $step = 1;
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            $error = "Please enter a valid 6-digit OTP.";
        } else {
            $result = verify_otp($conn, $email, $otp);
            if ($result['valid']) {
                $_SESSION['reset_student_email'] = $email;
                $_SESSION['reset_verified']      = true;
                unset($_SESSION['otp_step'], $_SESSION['otp_email']);
                if (!headers_sent()) {
                    header("Location: reset_password.php");
                    exit;
                }
                echo '<script>window.location.href = "reset_password.php";</script>';
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }

    // RESEND OTP
    elseif (isset($_POST['resend'])) {
        $email = $_SESSION['otp_email'] ?? '';
        $step  = 2;
        if ($email !== '') {
            send_otp($conn, $email);
            $info = "New OTP sent to <strong>" . htmlspecialchars($email) . "</strong>.";
        } else { $step = 1; }
    }
}

$pageTitle = "Forgot Password - Student Portal";
$baseDepth = "";
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="text-center mb-3"><i class="bi bi-shield-lock-fill text-primary"></i> Forgot Password</h3>

                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <span class="badge rounded-pill <?php echo $step>=1?'bg-primary':'bg-secondary'; ?> px-3 py-2">1 Email</span>
                        <span class="badge rounded-pill <?php echo $step>=2?'bg-primary':'bg-secondary'; ?> px-3 py-2">2 OTP</span>
                        <span class="badge rounded-pill bg-secondary px-3 py-2">3 Reset</span>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                    <?php if ($info):  ?><div class="alert alert-info"><?php echo $info; ?></div><?php endif; ?>

                    <?php if ($step === 1): ?>
                    <p class="text-muted text-center">Enter your registered email to receive a one-time password.</p>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   placeholder="you@gmail.com" required autofocus>
                            <small class="text-muted">Accepted: gmail.com, rayblaze.com</small>
                        </div>
                        <button type="submit" name="step1" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-send"></i> Send OTP
                        </button>
                    </form>

                    <?php else: ?>
                    <p class="text-muted text-center">Enter the 6-digit OTP sent to<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
                    <form method="POST" id="otpForm">
                        <input type="hidden" name="step2" value="1">
                        <div class="mb-3">
                            <label class="form-label">6-Digit OTP</label>
                            <input type="text" name="otp" id="otpInput"
                                   class="form-control form-control-lg text-center fw-bold"
                                   style="font-size:2rem;letter-spacing:.6rem;"
                                   maxlength="6" pattern="\d{6}" placeholder="------"
                                   required autofocus autocomplete="off">
                            <small class="text-muted">Valid for 10 minutes.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg mb-2">
                            <i class="bi bi-check-circle"></i> Verify OTP
                        </button>
                    </form>
                    <form method="POST">
                        <button type="submit" name="resend" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-repeat"></i> Resend OTP
                        </button>
                    </form>
                    <div class="text-center mt-2">
                        <a href="clear_otp_session.php"><small>Use a different email?</small></a>
                    </div>
                    <?php endif; ?>

                    <p class="text-center mt-3 mb-0">
                        <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const otpInput = document.getElementById('otpInput');
if (otpInput) {
    otpInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g,'').slice(0,6);
        if (this.value.length === 6) document.getElementById('otpForm').submit();
    });
}
</script>
<?php include 'includes/footer.php'; ?>
