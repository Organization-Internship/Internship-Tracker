<?php
session_start();
require_once __DIR__ . '/../config.php';
$conn = db();
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['status'=>'error','message'=>'DB connection failed']);
    exit;
}

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'company') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$company_id = (int) $_SESSION['user']['id'];

$sql = "
    SELECT
        a.id AS application_id,
        a.status,
        a.applied_at,
        s.name AS student_name,
        s.email AS student_email,
        i.title AS internship_title,
        s.resume_path
    FROM applications a
    JOIN users s ON a.user_id = s.id
    JOIN internships i ON a.internship_id = i.id
    WHERE i.posted_by_user_id = ?
    ORDER BY a.applied_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$res = $stmt->get_result();

$applications = [];
while($row = $res->fetch_assoc()) {
    $applications[] = $row;
}

echo json_encode(['status'=>'success','applications'=>$applications]);
