<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user();
if(!in_array($u['role'],['faculty','company'])){ echo json_encode(['status'=>'error','message'=>'Forbidden']); exit; }
$app_id=intval($_POST['application_id']??0); $status=$_POST['status']??'submitted';
$allowed=['submitted','reviewing','selected','rejected','in-progress','completed']; if(!in_array($status,$allowed)){ echo json_encode(['status'=>'error','message'=>'Bad status']); exit; }
$conn=db(); $stmt=$conn->prepare("UPDATE applications a JOIN internships i ON a.internship_id=i.id SET a.status=? WHERE a.id=? AND i.posted_by_user_id=?");
$stmt->bind_param('sii',$status,$app_id,$u['id']); $stmt->execute(); echo json_encode(['status'=>'success','message'=>'Updated']);
?>