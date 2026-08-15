<?php 
require "db.php";

// Default blocks
$edit_id = $_GET["id"] ?? "";
$id = $_POST["id"] ?? "";

$name = $_POST["name"] ?? "";
$birth_date = $_POST["birth_date"] ?? "";
$booking_date = $_POST["booking_date"] ?? "";
$room_id = $_POST["room_id"] ?? "";
$service_id = $_POST["service_id"] ?? "";

$error = "";
$action = $_POST["action"] ?? "";

$search = trim($_GET["search"] ?? "");

// The router, every form have a hidden 'action' field and this decides the action
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($action === "add") {
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
            
        // if not service is choosen, ensure its made into null
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
    } elseif ($action === "delete") {
        if(!is_numeric($id)) {
            $error = "Invalid booking id.";
        } elseif ($error === "") {
            $stmt = mysqli_prepare($conn, "DELETE FROM guests WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            if(mysqli_affected_rows($conn) === 1) {
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit;
            }
            $error = "That booking no longer exists.";
        }
    } elseif ($action === "update") {
        $name = trim($name);
        $birth_date = trim($birth_date);
        $booking_date = trim($booking_date);
        $room_id = trim($room_id);
        $service_id = trim($service_id);
    
        if (!is_numeric($id)) {
            $error = "Invalid booking id.";
        }

        if ($name === "" || $birth_date === "" || $booking_date === "" || $room_id === "") {
            $error = "Enter valid details.";
        }

        $today = date("Y-m-d");
        if ($birth_date > $today) { $error = "Birth date can't be in the future."; }
            
        if ($error === "") {
            if ($service_id === "") {
                $service_id = null;
            }
    
            $stmt = mysqli_prepare($conn, "UPDATE guests SET name=?, birth_date=?, booking_date=?, room_id=?, service_id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssiii", $name, $birth_date, $booking_date, $room_id, $service_id, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
    
            header("Location: index.php");
            exit;
        }
    }
}

// if its in edit mode, prefill the variables
if ($edit_id !== "" && is_numeric($edit_id) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = mysqli_prepare($conn, "SELECT name, birth_date, booking_date, room_id, service_id FROM guests WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($edit_result);
    if($row !== null) {
        $name = $row["name"];
        $birth_date = $row["birth_date"];
        $booking_date = $row["booking_date"];
        $room_id = $row["room_id"];
        $service_id = $row["service_id"] ?? "";
    }
    mysqli_stmt_close($stmt);
}

$sort_options = [
    "newest" => "g.created_at DESC",
    "oldest" => "g.created_at ASC",
    "name_az" => "g.name ASC",
    "name_za" => "g.name DESC",
    "price_low" => "total_price ASC",
    "price_high" => "total_price DESC",
];

$sort = $_GET["sort"] ?? "";
$sort_sql = $sort_options[$sort] ?? $sort_options["newest"];

$like = str_replace(["%", "_"], ["\\%", "\\_"], $search);

$sql = "
    SELECT g.id as guest_id, g.name, g.birth_date, g.booking_date, r.room_number, r.class, s.service_number, s.service_name, (r.price + COALESCE(s.price, 0)) AS total_price, g.created_at 
    FROM guests g
    LEFT JOIN rooms r ON g.room_id = r.id
    LEFT JOIN services s ON g.service_id = s.id
    WHERE 1=1
";

$types = "";
$params = [];

if ($like !== "") {
    $sql .= " AND (
        g.name LIKE ? OR
        g.birth_date LIKE ? OR
        g.booking_date LIKE ? OR
        r.room_number LIKE ? OR
        r.class LIKE ? OR
        s.service_number LIKE ? OR
        s.service_name LIKE ? OR
        CAST((r.price + COALESCE(s.price, 0)) AS CHAR) LIKE ?
    )";
    $types .= "ssssssss";
    for ($i = 0; $i < 7; $i++) {
        $params[] = "%$like%";
    }
}

$sql .= " ORDER BY $sort_sql";

$stmt = mysqli_prepare($conn, $sql);
if($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params); // ...params will spread the array into arguments
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rooms = mysqli_query($conn, "SELECT id, room_number, class, price FROM rooms ORDER BY id DESC");
$services = mysqli_query($conn, "SELECT id, service_name, service_number, price FROM services ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
    <head>
        <?php $page_title = "Bookings"; ?>
        <?php include "head.php"; ?>
    </head>
    <body>
        <main class="flex flex-col w-full px-8">
            <?php if($error !== ""): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        
            <div class="flex flex-row justify-between items-center">
                <p class="text-xl font-bold">Booking</p>
                <div>
                    <form method="get">
                        <input placeholder="Search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="border-b border-black">
                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === "newest" ? "selected" : ""; ?>>Newest first</option>
                            <option value="oldest" <?php echo $sort === "oldest" ? "selected" : ""; ?>>Oldest first</option>
                            <option value="name_az" <?php echo $sort === "name_az" ? "selected" : ""; ?>>Name A-Z</option>
                            <option value="name_za" <?php echo $sort === "name_za" ? "selected" : ""; ?>>Name Z-A</option>
                            <option value="price_low" <?php echo $sort === "price_low" ? "selected" : ""; ?>>Price low to high</option>
                            <option value="price_high" <?php echo $sort === "price_high" ? "selected" : ""; ?>>Price high to low</option>
                        </select>
                    </form>
                </div>
            </div>
            <table>
                <thead>
                    <tr><th>No</th><th>Name</th><th>Birth Date</th><th>Booking Date</th><th>Room</th><th>Service</th><th>Total Price</th><th>Created At</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php $row_number = 1; ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row_number; ?></td>
                            <td><?php echo htmlspecialchars($row["name"]); ?></td>
                            <td><?php echo $row["birth_date"]; ?></td>
                            <td><?php echo $row["booking_date"]; ?></td>
                            <td><?php echo htmlspecialchars($row["room_number"]); ?> - <?php echo htmlspecialchars($row["class"]); ?></td>
                            <td><?php echo $row["service_name"] !== null ? htmlspecialchars($row["service_number"]) . " - " . htmlspecialchars($row["service_name"]) : "-"; ?></td>
                            <td><?php echo $row["total_price"]; ?></td>
                            <td><?php echo $row["created_at"]; ?></td>
                            <td>
                                <a href="index.php?id=<?php echo htmlspecialchars($row["guest_id"]); ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["guest_id"]); ?>">
                                    <button type="submit" onclick="return confirm('Delete this booking?')">Delete</button>
                                </form>
                            </td>
                            <?php $row_number++; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <form method="post" class="fixed bottom-10">
                <div class="flex flex-row gap-2">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                    </div>

                    <div>
                        <label>Birth Date:</label>
                        <input type="date" name="birth_date" max="<?php echo date("Y-m-d"); ?>" value="<?php echo htmlspecialchars($birth_date); ?>">
                    </div>
    
                    <div>
                        <label>Booking Date:</label>
                        <input type="date" name="booking_date" <?php echo $edit_id === "" ? 'min="' . date("Y-m-d") . '"' : "" ?> value="<?php echo htmlspecialchars($booking_date); ?>">
                    </div>
    
                    <div>
                        <label>Room:</label>
                        <select name="room_id">
                            <?php while ($room = mysqli_fetch_assoc($rooms)): ?>
                                <!-- Convert the row id and current room_id into string to compare them -->
                                <option value="<?php echo $room["id"]; ?>" <?php echo (string)$room["id"] === (string)$room_id ? "selected" : ""; ?>>
                                    Room <?php echo htmlspecialchars($room["room_number"]); ?> - $<?php echo htmlspecialchars($room["price"]); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Service:</label>
                        <select name="service_id">
                            <option value="" <?php echo $service_id === "" ? "selected" : ""; ?>>None</option>
                            <?php while ($service = mysqli_fetch_assoc($services)): ?>
                                <!-- Convert the service id and current service_id into string to compare them -->
                                <option value="<?php echo $service["id"]; ?>" <?php echo (string)$service["id"] === (string)$service_id ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($service["service_name"]); ?> <?php echo htmlspecialchars($service["service_number"]); ?> - $<?php echo htmlspecialchars($service["price"]); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <input type="hidden" name="action" value="<?php echo $edit_id !== "" ? "update" : "add"; ?>">
                    <?php if($edit_id !== ""): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_id); ?>">
                    <?php endif; ?>
                    <button type="submit"><?php echo $edit_id !== "" ? "Update" : "Add"; ?></button>
                    
                    <?php if($edit_id !== ""): ?>
                        <a href="index.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </main>
    </body>    
</html>