<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user(); $conn=db();
$mine=isset($_GET['mine']); $myApps=isset($_GET['myApplications']);
if($myApps && $u['role']==='student'){
  $q="SELECT a.id, a.status, i.title FROM applications a JOIN internships i ON a.internship_id=i.id WHERE a.user_id=".$u['id']." ORDER BY a.created_at DESC";
  $res=$conn->query($q); $apps=[]; while($row=$res->fetch_assoc()) $apps[]=$row; echo json_encode(['status'=>'success','applications'=>$apps]); exit;
}
$sql="SELECT i.id,i.title,i.kind,i.stipend,i.duration,i.skills_required,u.name AS posted_by FROM internships i JOIN users u ON i.posted_by_user_id=u.id";
if($mine) $sql.=" WHERE i.posted_by_user_id=".$u['id'];
$sql.=" ORDER BY i.created_at DESC";
$res=$conn->query($sql); $list=[]; while($row=$res->fetch_assoc()){ $c=$conn->query("SELECT COUNT(*) c FROM applications WHERE internship_id=".$row['id'])->fetch_assoc()['c']; $row['applicant_count']=intval($c); $list[]=$row; }
echo json_encode(['status'=>'success','internships'=>$list]);
?>