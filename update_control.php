<?php
include 'config.php';

$relay = $_GET['relay'];
$buzzer = $_GET['buzzer'];

$conn->query("UPDATE device_control 
SET relay=$relay, buzzer=$buzzer 
WHERE id=1");

header("Location: dashboard.php");
?>