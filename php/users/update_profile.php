<?php
// php/users/update_profile.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/session.php';

// ✅ Must be logged in
require_login();
$u = current_user();
$conn = db();

$name = trim($_POST['name'] ?? '');

// ---------------------------
// Profile Image Upload
// ---------------------------
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $imageDir = __DIR__ . '/../../uploads/profile_images/';
    if (!is_dir($imageDir)) {
        mkdir($imageDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    $fileName = 'profile_' . $u['id'] . '.' . $ext;
    $fullPath = $imageDir . $fileName;

    // Validate image
    if (!getimagesize($_FILES['profile_image']['tmp_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid image file']);
        exit;
    }

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $fullPath)) {
        $relPath = '/uploads/profile_images/' . $fileName;
        $stmt = $conn->prepare("UPDATE users SET profile_image_path=? WHERE id=?");
        $stmt->bind_param('si', $relPath, $u['id']);
        $stmt->execute();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload profile image']);
        exit;
    }
}

// ---------------------------
// Role-based Updates
// ---------------------------
if ($u['role'] === 'student') {
    $phone    = trim($_POST['phone'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $branch   = trim($_POST['branch'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github   = trim($_POST['github'] ?? '');

    if ($name) {
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->bind_param('si', $name, $u['id']);
        $stmt->execute();
    }

    $stmt = $conn->prepare("UPDATE students SET phone=?, year=?, branch=?, linkedin=?, github=? WHERE user_id=?");
    $stmt->bind_param('sssssi', $phone, $year, $branch, $linkedin, $github, $u['id']);
    $stmt->execute();

    // ✅ Resume Upload
    if (!empty($_FILES['resume']['name'])) {
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            echo json_encode(['status' => 'error', 'message' => 'Only PDF resumes allowed']);
            exit;
        }

        $resumeDir = __DIR__ . '/../../uploads/resumes/';
        if (!is_dir($resumeDir)) mkdir($resumeDir, 0777, true);

        $fname = 'resume_' . $u['id'] . '_' . time() . '.pdf';
        $dest = $resumeDir . $fname;

        if (move_uploaded_file($_FILES['resume']['tmp_name'], $dest)) {
            $rel = '/uploads/resumes/' . $fname;
            $stmt = $conn->prepare("UPDATE users SET resume_path=? WHERE id=?");
            $stmt->bind_param('si', $rel, $u['id']);
            $stmt->execute();
        }
    }

} elseif ($u['role'] === 'faculty') {
    $department = trim($_POST['department'] ?? '');
    $contact    = trim($_POST['contact_info'] ?? '');

    if ($name) {
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->bind_param('si', $name, $u['id']);
        $stmt->execute();
    }

    $stmt = $conn->prepare("UPDATE faculty SET department=?, contact_info=? WHERE user_id=?");
    $stmt->bind_param('ssi', $department, $contact, $u['id']);
    $stmt->execute();

} elseif ($u['role'] === 'company') {
    $website = trim($_POST['website'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');

    if ($name) {
        $stmt = $conn->prepare("UPDATE companies SET company_name=? WHERE user_id=?");
        $stmt->bind_param('si', $name, $u['id']);
        $stmt->execute();
    }

    $stmt = $conn->prepare("UPDATE companies SET website=?, contact_info=? WHERE user_id=?");
    $stmt->bind_param('ssi', $website, $contact, $u['id']);
    $stmt->execute();
}

echo json_encode(['status' => 'success', 'message' => 'Profile updated']);
