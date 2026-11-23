<?php
session_start();
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'tenis_wozki';

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
	die("Błąd połączenia z bazą: " . $conn->connect_error);
}
?>
