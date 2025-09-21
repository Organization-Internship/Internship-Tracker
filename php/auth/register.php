<?php
// php/auth/register.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

function log_msg($msg) {
    error_log("[REGISTER] " . $msg);
}

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$role  = $_POST['role'] ?? '';

log_msg("Input: name=$name, email=$email, role=$role");

if (!$name || !$email || !$pass || !in_array($role, ['student','faculty','company'])) {
    log_msg("Validation failed");
    echo json_encode(['status'=>'error','message'=>'All fields are required']);
    exit;
}

$conn = db();

// 🔎 check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
if (!$stmt) {
    log_msg("Prepare failed: " . $conn->error);
}
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    log_msg("Email exists: $email");
    echo json_encode(['status'=>'error','message'=>'Email already exists']);
    exit;
}
$stmt->close();

// 🔑 hash password
$hash = password_hash($pass, PASSWORD_BCRYPT);
log_msg("Password hashed");

// 🟢 Students auto-approved, others need admin approval
$approved = ($role === 'student') ? 1 : 0;

// 📝 insert into users
$stmt = $conn->prepare("INSERT INTO users (name,email,password_hash,role,approved) VALUES (?,?,?,?,?)");
if (!$stmt) {
    log_msg("Prepare failed for insert: " . $conn->error);
}
$stmt->bind_param('ssssi', $name, $email, $hash, $role, $approved);

if (!$stmt->execute()) {
    log_msg("Insert failed: " . $stmt->error);
    echo json_encode(['status'=>'error','message'=>'Registration failed']);
    exit;
}

$uid = $stmt->insert_id;
$stmt->close();
log_msg("Inserted user id $uid");

// 👥 insert into role-specific table
if ($role === 'student') {
    if (!$conn->query("INSERT INTO students (user_id) VALUES ($uid)")) {
        log_msg("Insert student failed: " . $conn->error);
    }
} elseif ($role === 'faculty') {
    if (!$conn->query("INSERT INTO faculty (user_id) VALUES ($uid)")) {
        log_msg("Insert faculty failed: " . $conn->error);
    }
} elseif ($role === 'company') {
    if (!$conn->query("INSERT INTO companies (user_id) VALUES ($uid)")) {
        log_msg("Insert company failed: " . $conn->error);
    }
}

// ✅ Start session only for approved users
if ($approved) {
    $_SESSION['user_id'] = $uid;
    $_SESSION['role']    = $role;
    log_msg("Session started, user_id=$uid, role=$role");

    echo json_encode([
        'status'  => 'success',
        'message' => 'Registered and logged in successfully',
        'role'    => $role
    ]);
} else {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Registration successful, pending admin approval',
        'role'    => $role
    ]);
}
