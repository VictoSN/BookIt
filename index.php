<?php 
require "db.php";

$name = $_POST["name"] ?? "";
$birth_date = $_POST["birth_date"] ?? "";
$booking_date = $_POST["booking_date"] ?? "";
$room_id = $_POST["room_id"] ?? "";
$service_id = $_POST["service_id"] ?? "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($name);
    $birth_date = trim($birth_date);
    $booking_date = trim($booking_date);
    $room_id = trim($room_id);
    $service_id = trim($service_id);

    
    if ($name === "" || $birth_date === "" || $booking_date === "" || $room_id === "") {
        $error = "Enter valid details.";
    }

    $today = date("Y-m-d");
    if ($booking_date < $today) { $error = "Booking date must be today or later."; }
    if ($birth_date > $today) { $error = "Birth date can't be in the future."; }
        
    if ($error === "") {
        if ($service_id === "") {
            $service_id = null;
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO guests (name, birth_date, booking_date, room_id, service_id) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssii", $name, $birth_date, $booking_date, $room_id, $service_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: index.php");
        exit;
    }
}

$result = mysqli_query($conn, "
    SELECT g.id as guest_id, g.name, g.birth_date, g.booking_date, r.room_number, r.class, s.service_number, s.service_name, (r.price + COALESCE(s.price, 0)) AS total_price, g.created_at 
    FROM guests g
    LEFT JOIN rooms r ON g.room_id = r.id
    LEFT JOIN services s ON g.service_id = s.id
    ORDER BY created_at DESC
");

$rooms = mysqli_query($conn, "SELECT id, room_number, class, price FROM rooms ORDER BY id DESC");
$services = mysqli_query($conn, "SELECT id, service_name, service_number, price FROM services ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
    <head>
        <title>bookIt</title>
    </head>
    <body>
        <?php if($error !== ""): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <div>
                <div>
                    <label>Name:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                </div>

                <div>
                    <label>Date of Birth:</label>
                    <input type="date" name="birth_date" max="<?php echo date("Y-m-d"); ?>" value="<?php echo htmlspecialchars($birth_date); ?>">
                </div>

                <div>
                    <label>Booking Date:</label>
                    <input type="date" name="booking_date" min="<?php echo date("Y-m-d"); ?>" value="<?php echo htmlspecialchars($booking_date); ?>">
                </div>

                <div>
                    <label>Room Option:</label>
                    <select name="room_id">
                        <?php while ($room = mysqli_fetch_assoc($rooms)): ?>
                            <option value="<?php echo $room["id"]; ?>">
                                Room <?php echo htmlspecialchars($room["room_number"]); ?> - $<?php echo htmlspecialchars($room["price"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label>Service Option:</label>
                    <select name="service_id">
                        <option value="">None</option>
                        <?php while ($service = mysqli_fetch_assoc($services)): ?>
                            <option value="<?php echo $service["id"]; ?>">
                                <?php echo htmlspecialchars($service["service_name"]); ?> <?php echo htmlspecialchars($service["service_number"]); ?> - $<?php echo htmlspecialchars($service["price"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <button type="submit">Add</button>
        </form>

        <table>
            <thead>
                <tr><th>Name</th><th>Date of Birth</th><th>Booking Date</th><th>Room</th><th>Service</th><th>Total Price</th><th>Created At</th></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["name"]); ?></td>
                        <td><?php echo $row["birth_date"]; ?></td>
                        <td><?php echo $row["booking_date"]; ?></td>
                        <td><?php echo htmlspecialchars($row["room_number"]); ?> - <?php echo htmlspecialchars($row["class"]); ?></td>
                        <td><?php echo $row["service_name"] !== null ? htmlspecialchars($row["service_number"]) . " - " . htmlspecialchars($row["service_name"]) : "-"; ?></td>
                        <td><?php echo $row["total_price"]; ?></td>
                        <td><?php echo $row["created_at"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </body>    
</html>