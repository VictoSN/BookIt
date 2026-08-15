<?php 
require "db.php";

$edit_id = $_GET["id"] ?? "";
$id = $_POST["id"] ?? "";

$room_number = $_POST["room_number"] ?? "";
$class = $_POST["class"] ?? "";
$price = $_POST["price"] ?? "";

$error = "";
$action = $_POST["action"] ?? "";
$search = trim($_GET["search"] ?? "");

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

$sort_options = [
    "newest"    => "id DESC",
    "oldest"    => "id ASC",
    "room_az"   => "room_number ASC",
    "room_za"   => "room_number DESC",
    "class_az"  => "class ASC",
    "price_low" => "price ASC",
    "price_high" => "price DESC",
];

$sort = $_GET["sort"] ?? "";
$sort_sql = $sort_options[$sort] ?? $sort_options["newest"];

$like = str_replace(["%", "_"], ["\\%", "\\_"], $search);

$sql = "
    SELECT id, room_number, class, price
    FROM rooms
    WHERE 1=1
";

$types = "";
$params = [];

if ($like !== "") {
    $sql .= " AND (room_number LIKE ? OR class LIKE ? OR CAST(price AS CHAR) LIKE ?)";
    $types .= "sss";
    for ($i = 0; $i < 3; $i++) {
        $params[] = "%$like%";
    }
}

$sql .= " ORDER BY $sort_sql";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
    <head>
        <?php $page_title = "Rooms"; ?>
        <?php include "head.php"; ?>
    </head>
    <body>
        <main>
            <?php if($error !== ""): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
    
            <div>
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
        
                <div class="flex flex-row justify-between items-center">
                <p>Room</p>
                <div>
                    <form method="get">
                        <input placeholder="Search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="border-b border-black">
                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === "newest" ? "selected" : ""; ?>>Newest first</option>
                            <option value="oldest" <?php echo $sort === "oldest" ? "selected" : ""; ?>>Oldest first</option>
                            <option value="room_az" <?php echo $sort === "room_az" ? "selected" : ""; ?>>Room number A-Z</option>
                            <option value="room_za" <?php echo $sort === "room_za" ? "selected" : ""; ?>>Room number Z-A</option>
                            <option value="class_az" <?php echo $sort === "class_az" ? "selected" : ""; ?>>Class A-Z</option>
                            <option value="price_low" <?php echo $sort === "price_low" ? "selected" : ""; ?>>Price low to high</option>
                            <option value="price_high" <?php echo $sort === "price_high" ? "selected" : ""; ?>>Price high to low</option>
                        </select>
                    </form>
                </div>
            </div>

            <table>
                    <thead>
                        <tr><th>No</th><th>Room Number</th><th>Class</th><th>Price</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php $row_number = 1; ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row_number; ?></td>
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
                                <?php $row_number++; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </body>    
</html>