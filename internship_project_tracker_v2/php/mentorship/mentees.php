<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../utils/session.php'; require_login(); $u=current_user(); if($u['role']!=='faculty'){ echo json_encode(['status'=>'error','message'=>'Faculty only']); exit; }
$conn=db(); $sql="SELECT u.name, u.email FROM mentorship m JOIN users u ON m.student_user_id=u.id WHERE m.faculty_user_id=".$u['id']; $res=$conn->query($sql);
$list=[]; while($row=$res->fetch_assoc()) $list[]=$row; echo json_encode(['status'=>'success','mentees'=>$list]);
?>