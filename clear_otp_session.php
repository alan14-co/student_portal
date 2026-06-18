<?php
session_start();
unset($_SESSION['otp_step'], $_SESSION['otp_email']);
header("Location: forgot_password.php");
exit;
