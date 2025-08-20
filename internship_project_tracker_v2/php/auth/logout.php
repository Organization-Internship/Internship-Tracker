<?php
require_once __DIR__ . '/../config.php';
session_start();
session_destroy();

// Redirect to login page in public folder
header("Location: ../../public/login.html");
exit();
?>
