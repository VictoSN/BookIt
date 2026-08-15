<?php 
require "db.php";

$edit_id = $_GET["id"] ?? "";
$id = $_POST["id"] ?? "";

$service_name = $_POST["service_name"] ?? "";
$service_number = $_POST["service_number"] ?? "";
$price = $_POST["price"] ?? "";

$error = "";
$action = $_POST["action"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($action === "add") {
        $service_name = trim($service_name);
        $service_number = trim($service_number);
        $price = trim($price);
    
        if ($service_name === "" || $service_number === "" || $price === "" || $price < 0 || !is_numeric($price)) {
            $error = "Enter valid details.";
        }

        if ($error === "") {    
            $stmt = mysqli_prepare($conn, "INSERT INTO services (service_name, service_number, price) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssi", $service_name, $service_number, $price);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
    
            header("Location: services.php");
            exit;
        }
    } elseif ($action === "delete") {
        $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM guests WHERE service_id = ?");
        mysqli_stmt_bind_param($count_stmt, "i", $id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count = mysqli_fetch_row($count_result)[0];

        if ($count > 0) {
            $error = "This service has $count booking(s), reassign or delete them first.";
        }

        if(!is_numeric($id)) {
            $error = "Invalid service id.";
        } elseif ($error === "") {
            // Ensure booked services cannot be deleted
            $stmt = mysqli_prepare($conn, "DELETE FROM services WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            if(mysqli_affected_rows($conn) === 1) {
                mysqli_stmt_close($stmt);
                header("Location: services.php");
                exit;
            }
            $error = "That service no longer exists.";
        }
    } elseif ($action === "update") {
        $service_name = trim($service_name);
        $service_number = trim($service_number);
        $price = trim($price);
    
        if (!is_numeric($id)) {
            $error = "Invalid service id.";
        }

        if ($service_name === "" || $service_number === "" || $price === "" || $price < 0 || !is_numeric($price)) {
            $error = "Enter valid details.";
        }

        if ($error === "") {    
            $stmt = mysqli_prepare($conn, "UPDATE services SET service_name=?, service_number=?, price=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssii", $service_name, $service_number, $price, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
    
            header("Location: services.php");
            exit;
        }
    }
}

if ($edit_id !== "" && is_numeric($edit_id) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = mysqli_prepare($conn, "SELECT service_name, service_number, price FROM services WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($edit_result);
    if($row !== null) {
        $service_name = $row["service_name"];
        $service_number = $row["service_number"];
        $price = $row["price"];
    }
    mysqli_stmt_close($stmt);
}

$result = mysqli_query($conn, "
    SELECT id, service_name, service_number, price
    FROM services
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
                        <label>Service Name:</label>
                        <input type="text" name="service_name" value="<?php echo htmlspecialchars($service_name); ?>">
                    </div>

                    <div>
                        <label>Service Number:</label>
                        <input type="text" name="service_number" value="<?php echo htmlspecialchars($service_number); ?>">
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
                    <a href="services.php">Cancel</a>
                <?php endif; ?>
            </form>
    
            <table>
                <thead>
                    <tr><th>Service Name</th><th>Service Number</th><th>Price</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["service_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["service_number"]); ?></td>
                            <td><?php echo htmlspecialchars($row["price"]); ?></td>
                            <td>
                                <a href="services.php?id=<?php echo htmlspecialchars($row["id"]); ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["id"]); ?>">
                                    <button type="submit" onclick="return confirm('Delete this service?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </body>    
</html>