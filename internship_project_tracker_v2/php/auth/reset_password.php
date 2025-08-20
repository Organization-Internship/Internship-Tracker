<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
        $email = $row['email'];
        // Update password
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$newPassword, $email]);

        // Remove reset token
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        echo "Password updated. <a href='../public/login.html'>Login</a>";
    } else {
        echo "Invalid or expired token.";
    }
}
?>
