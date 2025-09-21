<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../utils/session.php';
require __DIR__ . '/../../vendor/autoload.php'; // PDF parser + PHPMailer

use Smalot\PdfParser\Parser;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
require_login();

$conn = db();   // ✅ Initialize DB first
$u = current_user();

// fallback if email missing
if (empty($u['email'])) {
    $stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
    $stmt->bind_param("i", $u['id']);
    $stmt->execute();
    $resUser = $stmt->get_result()->fetch_assoc();
    if ($resUser) {
        $u['email'] = $resUser['email'];
        $u['name']  = $resUser['name'];
        $_SESSION['user']['email'] = $resUser['email']; // update session
    }
}

if (!$u || $u['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden']);
    exit;
}

// Validate input
if (!isset($_POST['internship_id'], $_FILES['resume'], $_POST['percentage'])) {
    echo json_encode(['status'=>'error','message'=>'Missing data']);
    exit;
}

$internshipId = intval($_POST['internship_id']);
$percentage   = floatval($_POST['percentage']);
$resumeFile   = $_FILES['resume'];

// Get required skills + internship title
$stmt = $conn->prepare("SELECT title, skills_required FROM internships WHERE id=?");
$stmt->bind_param("i",$internshipId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows===0) {
    echo json_encode(['status'=>'error','message'=>'Internship not found']);
    exit;
}
$internship = $res->fetch_assoc();
$skills = array_filter(array_map('trim', explode(',', $internship['skills_required'] ?? '')));
$internshipTitle = $internship['title'];

// Upload resume
$filename = 'resume_'.$u['id'].'_'.time().'_'.basename($resumeFile['name']);
$uploadDirServer = __DIR__ . '/../../uploads/resumes/';
$uploadDirWeb    = 'uploads/resumes/';
if (!is_dir($uploadDirServer)) mkdir($uploadDirServer,0755,true);
$resumePathServer = $uploadDirServer.$filename;
$resumePathDb     = $uploadDirWeb.$filename;

if (!move_uploaded_file($resumeFile['tmp_name'], $resumePathServer)) {
    echo json_encode(['status'=>'error','message'=>'Failed to upload resume']);
    exit;
}

// Extract resume text
$resumeText = '';
$ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));

if ($ext==='pdf') {
    $parser = new Parser();
    $pdf = $parser->parseFile($resumePathServer);
    $resumeText = strtolower($pdf->getText());
} elseif ($ext==='txt') {
    $resumeText = strtolower(file_get_contents($resumePathServer));
} elseif (in_array($ext,['doc','docx'])) {
    $zip = new ZipArchive;
    if ($zip->open($resumePathServer)===true) {
        $xml = $zip->getFromName('word/document.xml');
        if ($xml) $resumeText = strtolower(strip_tags($xml));
        $zip->close();
    }
}

// Calculate ATS score
$matched = 0;
foreach($skills as $skill) {
    if ($skill && strpos($resumeText,strtolower($skill))!==false) $matched++;
}
$atsScore = count($skills)? round(($matched/count($skills))*100,2) : 0;

// Determine eligibility correctly
$eligible = ($percentage>=75 && $atsScore>=50) ? 1 : 0;
$status = $eligible ? 'submitted' : 'rejected';

// Insert into applications (include eligible field)
$stmt = $conn->prepare("INSERT INTO applications (internship_id,user_id,resume_path,percentage,status,ats_score,eligible) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param("iisssii",$internshipId,$u['id'],$resumePathDb,$percentage,$status,$atsScore,$eligible);
$stmt->execute();

// ✅ Send email notification to student
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "internshiptracker.noreply@gmail.com"; // replace with your email
    $mail->Password = "qlvc nsvi laab jvui"; // replace with your app password
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;

    $mail->setFrom("internshiptracker.noreply@gmail.com", "Internship Portal");
    $mail->addAddress($u['email'], $u['name']);

    $mail->isHTML(true);
    $mail->Subject = "Application Submitted - $internshipTitle";
    $mail->Body = "
        <p>Dear {$u['name']},</p>
        <p>Thank you for applying to <strong>$internshipTitle</strong>.</p>
        <p>Status: <strong>".ucfirst($status)."</strong></p>
        <p>ATS Score: <strong>{$atsScore}%</strong></p>
        <p>We will notify you once your application is reviewed.</p>
        <br>
        <p>Regards,<br>Internship Portal</p>
    ";

    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

echo json_encode([
    'status'=>'success',
    'message'=>$eligible ? 'Application submitted' : 'Not eligible (auto-rejected)',
    'atsScore'=>$atsScore,
    'eligible'=>$eligible
]);
