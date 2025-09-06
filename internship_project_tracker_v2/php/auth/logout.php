<?php
require_once __DIR__ . '/../config.php';
session_start();
$_SESSION = [];  
session_destroy();
setcookie(session_name(), '', time() - 3600); 
// Redirect to login page in public folder
header("Location: ../../public/login.html");
exit();
?>
