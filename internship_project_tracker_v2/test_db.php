<?php
require_once __DIR__ . '/config.php';

$conn = db();
if ($conn) {
    echo json_encode(['status' => 'success','message' => 'DB connected']);
} else {
    echo json_encode(['status' => 'error','message' => 'DB connection failed']);
}
