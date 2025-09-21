<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';

header('Content-Type: application/json');
require_login();

$u = current_user();
if (!$u || $u['role'] !== 'faculty') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden']);
    exit;
}

$conn = db();

// Fetch mentees assigned to this faculty
$sql = "
    SELECT 
        a.id AS application_id,
        u.name AS student_name,
        u.email AS student_email,
        i.title AS internship_title,
        a.percentage,
        a.status,
        a.ats_score,
        CASE 
            WHEN a.percentage >= 75 AND a.ats_score >= 50 THEN 1
            ELSE 0
        END AS eligible,
        a.resume_path
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN internships i ON a.internship_id = i.id
    where i.posted_by_user_id=?
    ORDER BY a.id DESC
";
$faculty_id = $u['id'];
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$res = $stmt->get_result();

$mentees = [];
while($row = $res->fetch_assoc()){
    $row['ats_result'] = $row['eligible'] ? 'eligible' : 'not eligible';
    $mentees[] = $row;
}

echo json_encode(['mentees'=>$mentees]);
?>
