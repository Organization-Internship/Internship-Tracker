<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';
require_login();
$u = current_user();
$conn = db();
$name = trim($_POST['name'] ?? '');

// Handle profile image upload
$profileImagePath = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $imageDir = __DIR__ . '/../../uploads/profile_images/';
    if (!is_dir($imageDir)) {
        mkdir($imageDir, 0777, true);
    }

    // Get file extension and make filename unique
    $imageExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    $imageFile = 'profile_' . $u['id'] . '.' . $imageExtension;
    $imagePath = $imageDir . $imageFile;

    // Check if valid image
    $check = getimagesize($_FILES['profile_image']['tmp_name']);
    if ($check === false) {
        echo json_encode(['status' => 'error', 'message' => 'File is not a valid image.']);
        exit;
    }

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $imagePath)) {
        $profileImagePath = '/uploads/profile_images/' . $imageFile;
        // Update database with image path
        $stmt = $conn->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
        $stmt->bind_param('si', $profileImagePath, $u['id']);
        $stmt->execute();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload profile image.']);
        exit;
    }
}

if ($u['role'] === 'student') {
    $phone = trim($_POST['phone'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');

    if ($name) {
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->bind_param('si', $name, $u['id']);
        $stmt->execute();
    }
    $stmt = $conn->prepare("UPDATE students SET phone=?,year=?,branch=?,linkedin=?,github=? WHERE user_id=?");
    $stmt->bind_param('sssssi', $phone, $year, $branch, $linkedin, $github, $u['id']);
    $stmt->execute();

    // Handle resume upload
    if (!empty($_FILES['resume']['name'])) {
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            echo json_encode(['status' => 'error', 'message' => 'Only PDF allowed']);
            exit;
        }
        $destDir = __DIR__ . '/../../uploads/resumes/';
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        $fname = 'resume_' . $u['id'] . '_' . time() . '.pdf';
        $dest = $destDir . $fname;
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $dest)) {
            $rel = '/uploads/resumes/' . $fname;
            $stmt = $conn->prepare("UPDATE users SET resume_path=? WHERE id=?");
            $stmt->bind_param('si', $rel, $u['id']);
            $stmt->execute();
        }
    }
} elseif ($u['role'] === 'faculty') {
    $department = trim($_POST['department'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');
    if ($name) {
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->bind_param('si', $name, $u['id']);
        $stmt->execute();
    }
    $stmt = $conn->prepare("UPDATE faculty SET department=?, contact_info=? WHERE user_id=?");
    $stmt->bind_param('ssi', $department, $contact, $u['id']);
    $stmt->execute();
} else {
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
?>
