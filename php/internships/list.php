<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/session.php';
require_login();

header('Content-Type: application/json');

$conn = db();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$u = current_user();
if (!$u || !isset($u['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$mine = isset($_GET['mine']);             // faculty/company view own postings
$myApps = isset($_GET['myApplications']); // student view own applications
$wantProjects = isset($_GET['projects']); // project listing

try {
    // ✅ Student: view own applications
    if ($myApps && $u['role'] === 'student') {
        $stmt = $conn->prepare("
            SELECT a.id AS application_id, a.status, a.created_at,
                   i.id AS internship_id, i.title, i.kind, i.stipend, i.duration, i.skills_required,
                   u.name AS posted_by_name, u.role AS posted_by_role, u.resume_path, a.ats_score
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            JOIN users u ON i.posted_by_user_id = u.id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->bind_param("i", $u['id']);
        $stmt->execute();
        $res = $stmt->get_result();

        $apps = [];
        while ($row = $res->fetch_assoc()) {
            $c = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE internship_id=" . intval($row['internship_id']))
                      ->fetch_assoc()['c'] ?? 0;
            $row['applicant_count'] = intval($c);
            $row['applied'] = true;
            $row['disableApply'] = ($row['status'] === 'submitted');
            $apps[] = $row;
        }

        echo json_encode(['status' => 'success', 'applications' => $apps]);
        exit;
    }

    // ✅ Project list
    if ($wantProjects) {
        $sql = "SELECT p.*, u.name AS student_name 
                FROM projects p 
                JOIN users u ON p.user_id = u.id";
        if ($mine) {
            $sql .= " WHERE p.user_id = " . intval($u['id']);
        }
        $sql .= " ORDER BY p.created_at DESC";

        $res = $conn->query($sql);
        $list = [];
        while ($row = $res->fetch_assoc()) {
            $list[] = $row;
        }

        echo json_encode(['status' => 'success', 'projects' => $list]);
        exit;
    }

    // ✅ Default: Internship list
    $sql = "
        SELECT i.id, i.title, i.kind, i.stipend, i.duration, i.skills_required,
               u.name AS posted_by, u.role AS poster_role, i.created_at
        FROM internships i
        JOIN users u ON i.posted_by_user_id = u.id
    ";
    if ($mine) {
        $sql .= " WHERE i.posted_by_user_id = " . intval($u['id']);
    }
    $sql .= " ORDER BY i.created_at DESC";

    $res = $conn->query($sql);
    $list = [];

    while ($row = $res->fetch_assoc()) {
        // Applicants count
        $c = $conn->query("SELECT COUNT(*) AS c FROM applications WHERE internship_id=" . intval($row['id']))
                   ->fetch_assoc()['c'] ?? 0;
        $row['applicant_count'] = intval($c);

        // 🔹 Student: check if already applied
        $row['applied'] = false;
        $row['status'] = null;
        $row['atsScore'] = null;
        $row['disableApply'] = false;

        if ($u['role'] === 'student') {
            $stmtApp = $conn->prepare("SELECT status, ats_score FROM applications WHERE internship_id=? AND user_id=? LIMIT 1");
            $stmtApp->bind_param("ii", $row['id'], $u['id']);
            $stmtApp->execute();
            $resApp = $stmtApp->get_result();
            if ($resApp && $resApp->num_rows > 0) {
                $appRow = $resApp->fetch_assoc();
                $row['applied'] = true;
                $row['status'] = $appRow['status'];
                $row['atsScore'] = $appRow['ats_score'];
                $row['disableApply'] = ($appRow['status'] === 'submitted'); // Disable button only if submitted
            }
            $stmtApp->close();
        }

        // 🔹 Faculty & Company → include applications for each posted internship
        if ($mine && in_array($u['role'], ['faculty', 'company'])) {
            $appsRes = $conn->query("
                SELECT a.id AS application_id, a.status, a.created_at,
                       s.name AS student_name, s.email, s.percentage, a.ats_score
                FROM applications a
                JOIN users s ON a.user_id = s.id
                WHERE a.internship_id = " . intval($row['id'])
            );
            $apps = [];
            while ($app = $appsRes->fetch_assoc()) {
                $apps[] = $app;
            }
            $row['applications'] = $apps;
        }

        $list[] = $row;
    }

    echo json_encode(['status' => 'success', 'internships' => $list]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
