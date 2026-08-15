<?php 
// host, user, password, database. We use '127...' instead of 'localhost' to ensure it does not get blocked by windows
$conn = mysqli_connect("127.0.0.1", "root", "", "bookit");

if(!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>