<?php
require_once __DIR__.'/../config.php';
$name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??''; $role=$_POST['role']??'';
if(!$name||!$email||!$pass||!in_array($role,['student','faculty','company'])){ echo json_encode(['status'=>'error','message'=>'All fields required']); exit; }
$conn=db(); $stmt=$conn->prepare("SELECT id FROM users WHERE email=?"); $stmt->bind_param('s',$email); $stmt->execute(); $stmt->store_result();
if($stmt->num_rows>0){ echo json_encode(['status'=>'error','message'=>'Email already exists']); exit; }
$hash=password_hash($pass,PASSWORD_BCRYPT);
$stmt=$conn->prepare("INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,?)");
$stmt->bind_param('ssss',$name,$email,$hash,$role); $stmt->execute(); $uid=$stmt->insert_id;
if($role==='student'){ $conn->query("INSERT INTO students(user_id) VALUES ($uid)"); }
elseif($role==='faculty'){ $conn->query("INSERT INTO faculty(user_id) VALUES ($uid)"); }
else { $conn->query("INSERT INTO companies(user_id) VALUES ($uid)"); }
echo json_encode(['status'=>'success','message'=>'Registered successfully']);
?>