<?php
$hash = '$2y$12$QFj2EsKVr7YkhP36jldoruR9CIDh5rlphiWSHHfL1eHi.TCuqeRq.';
$password = 'Admin123!';

if (password_verify($password, $hash)) {
    echo "Password matches!";
} else {
    echo "Password does NOT match!";
}
