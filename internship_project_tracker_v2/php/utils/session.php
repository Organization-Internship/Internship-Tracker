<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'path' => '/',
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
    session_start();
}
function require_login(){ if(!isset($_SESSION['user'])){ http_response_code(401); echo json_encode(['status'=>'error','message'=>'Not logged in']); exit; } }
function current_user(){ return $_SESSION['user'] ?? null; }
?>