<?php 
require "db.php";

$name = $_POST["name"] ?? "";
$birth_date = $_POST["birth_date"] ?? "";
$booking_date = $_POST["booking_date"] ?? "";
$room_id = $_POST["room_id"] ?? "";
$service_id = $_POST["service_id"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($name);
    $birth_date = trim($birth_date);
    $booking_date = trim($booking_date);
    $room_id = trim($room_id);
    $service_id = trim($service_id);

    if ($name === "" || $birth_date === "" || $booking_date === "" || $room_id === "" || $service_id === "") {
        $error = "Enter valid details.";
    }

    if ($error === "") {
        $stmt = mysqli_prepare($conn, "INSERT INTO guests (name, birth_date, booking_date, room_id, service_id) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sddii", $name, $birth_date, $booking_date, $room_id, $service_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $name = "";
        $birth_date = "";
        $booking_date = "";
        $room_id = "";
        $service_id = "";
    }
}

$result = mysqli_query($conn, "SELECT id, name, birth_date, booking_date, room_id, service_id, created_at FROM guests ORDER BY created_at DESC");
?>