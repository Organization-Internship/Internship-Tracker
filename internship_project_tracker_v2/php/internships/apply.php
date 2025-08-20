<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user();
if($u['role']!=='student'){ echo json_encode(['status'=>'error','message'=>'Students only']); exit; }
$id=intval($_POST['internship_id']??0); if(!$id){ echo json_encode(['status'=>'error','message'=>'Missing']); exit; }
$conn=db(); $dupe=$conn->query("SELECT id FROM applications WHERE internship_id=$id AND user_id=".$u['id']); if($dupe && $dupe->num_rows>0){ echo json_encode(['status'=>'error','message'=>'Already applied']); exit; }
$stmt=$conn->prepare("INSERT INTO applications(internship_id,user_id,status) VALUES (?,?, 'submitted')"); $stmt->bind_param('ii',$id,$u['id']); $stmt->execute();
echo json_encode(['status'=>'success','message'=>'Application submitted']);
?>