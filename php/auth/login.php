<?php
// php/auth/login.php
session_start();
error_log("Session ID: " . session_id());
error_log("User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET'));

require_once __DIR__ . "/../config.php"; // your db() function
require_once __DIR__.'/../utils/session.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email and password required"]);
    exit;
}

$conn = db();
$stmt = $conn->prepare("SELECT id, name, email, password_hash, role, approved FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password_hash'])) {
    // 🚫 Block unapproved non-students
    if ($user['approved'] == 0 && $user['role'] !== 'student') {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Your account is pending admin approval"
        ]);
        exit;
    }

    $_SESSION['user'] = [
        'id'    => $user['id'],
        'role'  => strtolower(trim($user['role'])),
        'email' => $user['email'],
        'name'  => $user['name']
    ];

    echo json_encode([
        "status"  => "success",
        "message" => "Login successful",
        "role"    => $_SESSION['user']['role']
    ]);
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
}
