<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!$email) die("Please provide email.");

    $conn = db();

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) die("No account found with that email.");

    // Generate a single token
    $token = bin2hex(random_bytes(32)); // 64 chars
    $expires = date("Y-m-d H:i:s", time() + 86400); // 24 hours

    // Delete old tokens
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id=?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    // Insert new token
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user['id'], $token, $expires);
    if (!$stmt->execute()) die("Failed to store token: " . $stmt->error);

    // Build reset link
    $resetLink = "http://localhost/intership-tracker/Internship-Tracker/internship_project_tracker_v2/php/auth/reset_password.php?token=$token";

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'internshiptracker.noreply@gmail.com'; 
        $mail->Password   = 'qlvc nsvi laab jvui'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('internshiptracker.noreply@gmail.com', 'Internship Tracker');
        $mail->addAddress($email, $user['name']);

        $mail->isHTML(true);
        $mail->Subject = "Password Reset Request";
        $mail->Body    = "Hi {$user['name']},<br><br>
                          Click the link below to reset your password:<br>
                          <a href='$resetLink'>$resetLink</a><br><br>
                          This link will expire in 24 hours.";

        $mail->send();
        echo "Password reset link sent to your email.";
    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<form method="POST">
  <input type="email" name="email" placeholder="Enter your email" required>
  <button type="submit">Send Reset Link</button>
</form>
