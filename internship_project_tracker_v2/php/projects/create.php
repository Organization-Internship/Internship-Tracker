<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/session.php';
require_login();

header('Content-Type: application/json');

$conn = db();
$u = current_user();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title       = trim($_POST['title'] ?? '');
        $tech_stack  = trim($_POST['tech_stack'] ?? '');
        $start_date  = $_POST['start_date'] ?? null;
        $end_date    = $_POST['end_date'] ?? null;
        $status      = $_POST['status'] ?? 'in-progress';
        $project_link = trim($_POST['project_link'] ?? '');
        $file_path   = null;

        if (!$title || !$tech_stack || !$start_date) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Missing required fields']);
            exit;
        }

        // ✅ Handle file upload
        if (!empty($_FILES['file']['name'])) {
            $uploadDir = __DIR__ . '/../../uploads/projects/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fname = time() . '_' . basename($_FILES['file']['name']);
            $target = $uploadDir . $fname;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $file_path = 'uploads/projects/' . $fname;
            }
        }

        // ✅ Insert into DB
        $stmt = $conn->prepare("INSERT INTO projects 
            (user_id, title, tech_stack, start_date, end_date, status, project_link, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $u['id'], $title, $tech_stack, $start_date, $end_date, $status, $project_link, $created_at);

        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'Project created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Failed to create project']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
