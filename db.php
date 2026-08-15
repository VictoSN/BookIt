<?php 
$conn = mysqli_connect("127.0.0.1", "root", "", "bookit");

if(!$conn) {
    die("Database connection failed: " . mysqli_connect_errno());
}

mysqli_set_charset($conn, "utf8mb4");
?>