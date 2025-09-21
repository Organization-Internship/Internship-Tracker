<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user(); $conn=db();
$mine=isset($_GET['mine']);
$sql=$mine? "SELECT id,title,tech_stack,start_date,end_date,status,project_link FROM projects WHERE user_id=".$u['id']." ORDER BY created_at DESC"
          : "SELECT id,title,tech_stack,start_date,end_date,status,project_link FROM projects ORDER BY created_at DESC";
$res=$conn->query($sql); $rows=[]; while($row=$res->fetch_assoc()) $rows[]=$row; echo json_encode(['status'=>'success','projects'=>$rows]);
?>