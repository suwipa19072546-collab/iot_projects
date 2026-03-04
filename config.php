<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "iot_project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$API_KEY = "123456789"; // เปลี่ยนเป็นรหัสของตัวเอง
?>