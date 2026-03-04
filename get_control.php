<?php
include 'config.php';

$result = $conn->query("SELECT relay, buzzer FROM device_control WHERE id=1");
$row = $result->fetch_assoc();

echo json_encode($row);

$conn->close();
?>