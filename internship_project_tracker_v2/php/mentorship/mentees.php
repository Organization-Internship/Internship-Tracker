<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';

require_login();
$u = current_user();
if (!$u || $u['role'] !== 'faculty') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden']);
    exit;
}

$conn = db();

// Fetch mentees assigned to this faculty
$sql = "SELECT 
    a.id AS application_id,
    u.name AS student_name,
    u.email AS student_email,
    u.resume_path AS resume,
    a.status,
    i.title AS internship_title
FROM applications a
JOIN users u ON a.user_id = u.id
JOIN internships i ON a.internship_id = i.id
WHERE i.posted_by_user_id =?
ORDER BY a.id DESC;
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $u['id']);
$stmt->execute();
$res = $stmt->get_result();

$mentees = [];
while($row = $res->fetch_assoc()){
    $mentees[] = $row;
}

echo json_encode(['mentees'=>$mentees]);
