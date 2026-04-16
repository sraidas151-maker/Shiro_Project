<?php
// db.php - Simple database connection
$host = "localhost";
$user = "root";      // XAMPP default
$pass = "";          // XAMPP default empty
$dbname = "shiro_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>