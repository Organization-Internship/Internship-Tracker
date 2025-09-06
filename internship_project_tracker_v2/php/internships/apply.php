<?php
session_start();
require_once __DIR__ . '/../config.php';
$conn = db();

header('Content-Type: application/json');

// ✅ Check if user is logged in
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['status'=>'error','message'=>'You must be logged in as a student']);
    exit;
}

$user_id = (int) $_SESSION['user']['id'];

// ✅ Validate internship_id
$internship_id = (int) ($_POST['internship_id'] ?? 0);
if ($internship_id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid internship ID']);
    exit;
}

// ✅ Check if internship exists
$stmt = $conn->prepare("SELECT id FROM internships WHERE id = ?");
$stmt->bind_param("i", $internship_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['status'=>'error','message'=>'This internship does not exist']);
    exit;
}

// ✅ Check if already applied
$stmt = $conn->prepare("SELECT id FROM applications WHERE user_id=? AND internship_id=?");
$stmt->bind_param("ii", $user_id, $internship_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['status'=>'error','message'=>'You have already applied to this internship']);
    exit;
}

// ✅ Handle resume upload
$resume_path = null;
if (!empty($_FILES['resume']['name'])) {
    $allowed = ['pdf','doc','docx'];
    $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['status'=>'error','message'=>'Invalid file type. Only PDF/DOC/DOCX allowed']);
        exit;
    }

    $upload_dir = __DIR__ . '/../../uploads/resumes/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename = 'resume_'.$user_id.'_'.time().'.'.$ext;
    $target = $upload_dir.$filename;

    if (move_uploaded_file($_FILES['resume']['tmp_name'], $target)) {
        $resume_path = $filename;
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to upload resume']);
        exit;
    }
}

// ✅ Insert application
$stmt = $conn->prepare("INSERT INTO applications (user_id, internship_id, status, applied_at, resume_path) VALUES (?, ?, 'submitted', NOW(), ?)");
$stmt->bind_param("iis", $user_id, $internship_id, $resume_path);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success','message'=>'Application submitted successfully']);
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to submit application']);
}
