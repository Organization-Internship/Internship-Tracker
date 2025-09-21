<?php
session_start();
require_once __DIR__ . '/../config.php';
$conn = db();

if (empty($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$company_id = (int) $_SESSION['user']['id'];

$sql = "
    SELECT 
        a.id AS application_id,
        a.status,
        a.applied_at,
        u.name AS student_name,
        u.email AS student_email,
        u.percentage,
        i.title AS internship_title,
        a.resume_path
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN internships i ON a.internship_id = i.id
    WHERE i.posted_by_user_id = ?
    ORDER BY a.applied_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$res = $stmt->get_result();

$applications = [];
while ($row = $res->fetch_assoc()) {
    // Eligibility check (>= 75%)
    $row['eligible'] = ($row['percentage'] >= 75) ? true : false;
    $applications[] = $row;
}

echo json_encode(['status'=>'success','applications'=>$applications]);
