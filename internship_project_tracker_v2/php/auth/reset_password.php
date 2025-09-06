<?php
require_once __DIR__ . '/../config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = db();

$token = $_GET['token'] ?? '';
if (!$token) die("No token provided.");

// Fetch token data
$stmt = $conn->prepare("SELECT user_id, expires_at, used FROM password_resets WHERE token=? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) die("Invalid token.");
if ($row['used']) die("Token already used.");
if (strtotime($row['expires_at']) < time()) die("Token expired.");

$user_id = $row['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);
    if ($password !== $confirm) die("Passwords do not match.");

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Update user password
    $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $stmt->bind_param("si", $hash, $user_id);
    if(!$stmt->execute()){
    die("Password update failed: " . $stmt->error);
    }

    // Mark token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used=1 WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    echo "Password successfully reset. You can now log in.";
}
?>

<form method="POST">
  <input type="password" name="password" placeholder="New Password" required>
  <input type="password" name="confirm" placeholder="Confirm Password" required>
  <button type="submit">Reset Password</button>
</form>
