<?php
require_once __DIR__.'/../config.php';
$email=trim($_POST['email']??''); $pass=$_POST['password']??'';
if(!$email||!$pass){ echo json_encode(['status'=>'error','message'=>'Email and password are required.']); exit; }
$conn=db(); $stmt=$conn->prepare("SELECT id,name,email,password_hash,role FROM users WHERE email=?"); $stmt->bind_param('s',$email); $stmt->execute(); $res=$stmt->get_result(); $u=$res->fetch_assoc();
if(!$u||!password_verify($pass,$u['password_hash'])){ echo json_encode(['status'=>'error','message'=>'Invalid credentials']); exit; }
$_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
echo json_encode(['status'=>'success','role'=>$u['role']]);
?>