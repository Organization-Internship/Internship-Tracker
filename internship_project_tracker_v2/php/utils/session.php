<?php
function require_login(){ if(!isset($_SESSION['user'])){ http_response_code(401); echo json_encode(['status'=>'error','message'=>'Not logged in']); exit; } }
function current_user(){ return $_SESSION['user'] ?? null; }
?>