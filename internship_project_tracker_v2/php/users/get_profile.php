<?php
session_start();
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';
require_login();

$u = current_user();
$conn = db();

$user = $conn->query("SELECT id, name, email, role, resume_path, profile_image_path FROM users WHERE id=".$u['id'])->fetch_assoc();

if ($user['role'] === 'student') {
    $row = $conn->query("SELECT phone, year, branch, linkedin, github FROM students WHERE user_id=".$u['id'])->fetch_assoc();
    $user = array_merge($user, $row ?: []);
} elseif ($user['role'] === 'faculty') {
    $row = $conn->query("SELECT department, contact_info FROM faculty WHERE user_id=".$u['id'])->fetch_assoc();
    $user = array_merge($user, $row ?: []);
} else {
    $row = $conn->query("SELECT company_name as name_override, website, contact_info FROM companies WHERE user_id=".$u['id'])->fetch_assoc();
    if ($row) {
        if ($row['name_override']) $user['name'] = $row['name_override'];
        $user['website'] = $row['website'];
        $user['contact_info'] = $row['contact_info'];
    }
}

echo json_encode(['status' => 'success', 'user' => $user]);
