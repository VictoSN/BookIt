<?php 
require "db.php";

$edit_id = $_GET["id"] ?? "";
$id = $_POST["id"] ?? "";

$room_number = $_POST["room_number"] ?? "";
$class = $_POST["class"] ?? "";
$price = $_POST["price"] ?? "";

$error = "";
$action = $_POST["action"] ?? "";

$valid_classes = ["Single", "Twin", "En-Suite", "Premium"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($action === "add") {
        $room_number = trim($room_number);
        $class = trim($class);
        $price = trim($price);
    
        if ($room_number === "" || $class === "" || $price === "" || $price < 0 || !is_numeric($price)) {
            $error = "Enter valid details.";
        } elseif (!in_array($class, $valid_classes)) {
            $error = "Pick a valid class.";
        }

        if ($error === "") {    
            $stmt = mysqli_prepare($conn, "INSERT INTO rooms (room_number, class, price) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssi", $room_number, $class, $price);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
    
            header("Location: rooms.php");
            exit;
        }
    } elseif ($action === "delete") {
        $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM guests WHERE room_id = ?");
        mysqli_stmt_bind_param($count_stmt, "i", $id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count = mysqli_fetch_row($count_result)[0];

        if(!is_numeric($id)) {
            $error = "Invalid room id.";
        }

        if ($count > 0) {
            $error = "This room has $count booking(s), reassign or delete them first.";
        }

        if ($error === "") {
            // Ensure booked rooms cannot be deleted
            $stmt = mysqli_prepare($conn, "DELETE FROM rooms WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            if(mysqli_affected_rows($conn) === 1) {
                mysqli_stmt_close($stmt);
                header("Location: rooms.php");
                exit;
            }
            $error = "That room no longer exists.";
        }
    } elseif ($action === "update") {
        $room_number = trim($room_number);
        $class = trim($class);
        $price = trim($price);
    
        if (!is_numeric($id)) {
            $error = "Invalid room id.";
        }

        if ($room_number === "" || $class === "" || $price === "" || $price < 0 || !is_numeric($price)) {
            $error = "Enter valid details.";
        } elseif (!in_array($class, $valid_classes)) {
            $error = "Pick a valid class.";
        }

        if ($error === "") {    
            $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_number=?, class=?, price=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssii", $room_number, $class, $price, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
    
            header("Location: rooms.php");
            exit;
        }
    }
}

if ($edit_id !== "" && is_numeric($edit_id) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = mysqli_prepare($conn, "SELECT room_number, class, price FROM rooms WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($edit_result);
    if($row !== null) {
        $room_number = $row["room_number"];
        $class = $row["class"];
        $price = $row["price"];
    }
    mysqli_stmt_close($stmt);
}

$result = mysqli_query($conn, "
    SELECT id, room_number, class, price
    FROM rooms
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
    <head>
        <title>bookIt</title>
    </head>
    <body>
        <?php include "nav.php"; ?>

        <main>
            <?php if($error !== ""): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
    
            <form method="post">
                <div>
                    <div>
                        <label>Room Number:</label>
                        <input type="text" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                    </div>

                    <div>
                        <label>Class:</label>
                        <select name="class">
                            <!-- Use foreach for arrays, whlie is for cursors that advance -->
                            <?php foreach ($valid_classes as $class_option): ?>
                                <option value="<?php echo $class_option; ?>" <?php echo $class === $class_option ? "selected" : ""; ?>><?php echo $class_option; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Price:</label>
                        <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?>">
                    </div>
                </div>
    
                <input type="hidden" name="action" value="<?php echo $edit_id !== "" ? "update" : "add"; ?>">
                <?php if($edit_id !== ""): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_id); ?>">
                <?php endif; ?>
                <button type="submit"><?php echo $edit_id !== "" ? "Update" : "Add"; ?></button>
                
                <?php if($edit_id !== ""): ?>
                    <a href="rooms.php">Cancel</a>
                <?php endif; ?>
            </form>
    
            <table>
                <thead>
                    <tr><th>Room Number</th><th>Class</th><th>Price</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                            <td><?php echo htmlspecialchars($row["class"]); ?></td>
                            <td><?php echo htmlspecialchars($row["price"]); ?></td>
                            <td>
                                <a href="rooms.php?id=<?php echo htmlspecialchars($row["id"]); ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["id"]); ?>">
                                    <button type="submit" onclick="return confirm('Delete this room?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </body>    
</html>