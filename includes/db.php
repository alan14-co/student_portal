<?php
$host = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'student_portal';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
