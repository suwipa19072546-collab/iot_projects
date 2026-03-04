<?php
include 'config.php';

if ($_POST['api_key'] != $API_KEY) {
    die("Wrong API Key");
}

$stmt = $conn->prepare("INSERT INTO sensor_data 
(temperature, humidity, light, motion) 
VALUES (?, ?, ?, ?)");

$stmt->bind_param("ddii",
    $_POST['temperature'],
    $_POST['humidity'],
    $_POST['light'],
    $_POST['motion']
);

$stmt->execute();
echo "OK";

$stmt->close();
$conn->close();
?>