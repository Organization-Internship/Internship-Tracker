<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'internship_tracker';


function db() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    static $c;

    if (!$c) {
        $c = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, 3306, '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

        if ($c->connect_error) {
            return false; // Don't die here; handle in the calling script
        }
    }
    return $c;
}
