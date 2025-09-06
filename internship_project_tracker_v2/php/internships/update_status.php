<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';

require_login();
$u = current_user();

// ✅ Only faculty or company can update
if (!$u || !in_array($u['role'], ['faculty','company'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden']);
    exit;
}

$app_id = intval($_POST['application_id'] ?? 0);
$status = $_POST['status'] ?? 'submitted';

// ✅ Allowed statuses
$allowed = ['submitted','reviewing','selected','rejected','in-progress','completed'];
if (!$app_id || !in_array($status, $allowed)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid status or application']);
    exit;
}

$conn = db();

// ✅ Update only if this faculty/company owns the internship
$stmt = $conn->prepare("
    UPDATE applications a
    JOIN internships i ON a.internship_id = i.id
    SET a.status = ?
    WHERE a.id = ? AND i.posted_by_user_id = ?
");
$stmt->bind_param('sii', $status, $app_id, $u['id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Custom message for accepted/rejected
    $message = match($status) {
        'selected' => 'Application accepted',
        'rejected' => 'Application rejected',
        default => 'Application status updated'
    };

    echo json_encode(['status'=>'success','message'=>$message,'new_status'=>$status]);
} else {
    http_response_code(404);
    echo json_encode(['status'=>'error','message'=>'No matching application found or already updated']);
}
