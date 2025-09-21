<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';

header('Content-Type: application/json');
require_login();

$u = current_user();
if (!$u || $u['role'] !== 'company') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden']);
    exit;
}

$conn = db();

// Fetch mentees for internships posted by this company
$sql = "
    SELECT 
        a.id AS application_id,
        u.name AS student_name,
        u.email AS student_email,
        i.title AS internship_title,
        a.percentage,
        a.status,
        a.ats_score,
        a.resume_path
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN internships i ON a.internship_id = i.id
    WHERE i.posted_by_user_id = ?
    ORDER BY a.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $u['id']);
$stmt->execute();
$res = $stmt->get_result();

$mentees = [];
while($row = $res->fetch_assoc()){
    // ✅ Eligibility calculation same as faculty
    $row['eligible'] = ($row['percentage'] >= 75 && $row['ats_score'] >= 50) ? 1 : 0;
    $mentees[] = $row;
}

echo json_encode(['mentees'=>$mentees]);
