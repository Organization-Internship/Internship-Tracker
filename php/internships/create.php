<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user();
$title=trim($_POST['title']??''); $desc=trim($_POST['description']??''); $stipend=trim($_POST['stipend']??''); $duration=trim($_POST['duration']??''); $skills=trim($_POST['skills_required']??'');
if(!$title){ echo json_encode(['status'=>'error','message'=>'Title required']); exit; }
$conn=db(); $stmt=$conn->prepare("INSERT INTO internships(title, description, mentor_id, posted_by_user_id, kind, stipend, duration, skills_required) VALUES (?,?,?,?,?,?,?,?)");
$kind='internship';$mentor_id = $u['id'];  $stmt->bind_param('sssissss',$title,$desc,$mentor_id,$u['id'],$kind,$stipend,$duration,$skills); $stmt->execute();
echo json_encode(['status'=>'success','message'=>'Internship created']);
?>