<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user();
$title=trim($_POST['title']??''); $tech=trim($_POST['tech_stack']??''); $start=trim($_POST['start_date']??''); $end=trim($_POST['end_date']??''); $status=trim($_POST['status']??'in-progress'); $link=trim($_POST['project_link']??'');
if(!$title||!$tech||!$start){ echo json_encode(['status'=>'error','message'=>'Title, Tech, Start date required']); exit; }
$conn=db(); $stmt=$conn->prepare("INSERT INTO projects(user_id,title,tech_stack,start_date,end_date,status,project_link) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param('issssss',$u['id'],$title,$tech,$start,$end,$status,$link); $stmt->execute(); $pid=$stmt->insert_id;
if(!empty($_FILES['file']['name'])){
  $safe=preg_replace('/[^a-zA-Z0-9_.-]/','_', $_FILES['file']['name']); $destDir=__DIR__.'/../../uploads/projects/'; if(!is_dir($destDir)) mkdir($destDir,0777,true);
  $dest=$destDir.$pid.'_'+$safe;
  if(move_uploaded_file($_FILES['file']['tmp_name'],$dest)){ $rel='/uploads/projects/'.$pid.'_'.$safe; $stmt2=$conn->prepare("INSERT INTO project_files(project_id,file_path) VALUES (?,?)"); $stmt2->bind_param('is',$pid,$rel); $stmt2->execute(); }
}
echo json_encode(['status'=>'success','message'=>'Project saved']);
?>