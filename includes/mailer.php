<?php
/**
 * mailer.php — OTP email sender + domain validator
 * Allowed domains: gmail.com and rayblaze.com
 */

define('ALLOWED_EMAIL_DOMAINS', ['gmail.com', 'rayblaze.com']);
define('MAIL_FROM',      'no-reply@studentportal.com');
define('MAIL_FROM_NAME', 'Student Portal');

define('SMTP_HOST',      'ssl://smtp.gmail.com');
define('SMTP_PORT',      465);
define('SMTP_USER',      'iowonder667@gmail.com');
define('SMTP_PASS',      'gkin tzzs cisc fhvp');
define('SMTP_FROM',      'iowonder667@gmail.com');

function is_allowed_email(string $email): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $domain = strtolower(substr(strrchr($email, '@'), 1));
    return in_array($domain, ALLOWED_EMAIL_DOMAINS, true);
}

function smtp_send_mail(string $to, string $subject, string $body, array $headers = []): bool {
    $socket = stream_socket_client(SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 30);
    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 30);
    $readResponse = function () use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function (string $cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $response = $readResponse();
    if (strpos($response, '220') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand('EHLO ' . gethostname());
    $response = $readResponse();
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand('AUTH LOGIN');
    $response = $readResponse();
    if (strpos($response, '334') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand(base64_encode(SMTP_USER));
    $response = $readResponse();
    if (strpos($response, '334') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand(base64_encode(SMTP_PASS));
    $response = $readResponse();
    if (strpos($response, '235') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand('MAIL FROM:<' . SMTP_FROM . '>');
    $response = $readResponse();
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand('RCPT TO:<' . $to . '>');
    $response = $readResponse();
    if (strpos($response, '250') !== 0 && strpos($response, '251') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand('DATA');
    $response = $readResponse();
    if (strpos($response, '354') !== 0) {
        fclose($socket);
        return false;
    }

    $headers = array_merge([
        'From' => MAIL_FROM_NAME . ' <' . SMTP_FROM . '>',
        'To' => $to,
        'Subject' => $subject,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ], $headers);

    foreach ($headers as $name => $value) {
        $sendCommand($name . ': ' . $value);
    }

    $sendCommand('');
    foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $body)) as $line) {
        if (strpos($line, '.') === 0) {
            $line = '.' . $line;
        }
        $sendCommand($line);
    }
    $sendCommand('.');
    $response = $readResponse();
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $sendCommand('QUIT');
    fclose($socket);
    return true;
}

function send_otp(mysqli $conn, string $email): array {
    // Delete old OTPs for this email
    $del = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();
    $del->close();

    $otp     = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $conn->prepare("INSERT INTO otp_codes (email, otp, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $expires);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to generate OTP.'];
    }
    $stmt->close();

    $subject = 'Your Password Reset OTP - Student Portal';
    $body    = "Hi,\n\nYour OTP for password reset is:\n\n    {$otp}\n\nThis code is valid for 10 minutes. Do not share it with anyone.\n\nRegards,\nStudent Portal Team";
    $sent = smtp_send_mail($email, $subject, $body, [
        'Reply-To' => MAIL_FROM,
        'X-Mailer' => 'PHP/' . phpversion(),
    ]);

    if (!$sent) {
        return ['success' => false, 'message' => 'Failed to send OTP email via SMTP.'];
    }

    return ['success' => true, 'message' => 'OTP sent to ' . $email];
}

function verify_otp(mysqli $conn, string $email, string $otp): array {
    $stmt = $conn->prepare(
        "SELECT id, expires_at, used FROM otp_codes WHERE email = ? AND otp = ? ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row)       return ['valid' => false, 'message' => 'Invalid OTP. Please check and try again.'];
    if ($row['used']) return ['valid' => false, 'message' => 'This OTP has already been used.'];

    if ((new DateTime()) > (new DateTime($row['expires_at'])))
        return ['valid' => false, 'message' => 'OTP has expired. Please request a new one.'];

    $upd = $conn->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
    $upd->bind_param("i", $row['id']);
    $upd->execute();
    $upd->close();

    return ['valid' => true, 'message' => 'OTP verified successfully.'];
}
