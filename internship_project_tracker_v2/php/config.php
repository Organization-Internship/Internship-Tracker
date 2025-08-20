<?php
$DB_HOST='localhost'; $DB_USER='root'; $DB_PASS=''; $DB_NAME='internship_tracker';
function db(){ global $DB_HOST,$DB_USER,$DB_PASS,$DB_NAME; static $c; if(!$c){ $c=new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME); if($c->connect_error){ http_response_code(500); die(json_encode(['status'=>'error','message'=>'DB connect failed'])); } } return $c; }
header('Content-Type: application/json'); session_start();
?>