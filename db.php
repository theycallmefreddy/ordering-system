<?php
$conn = new mysqli("localhost", "root", "", "food_ordering");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>
