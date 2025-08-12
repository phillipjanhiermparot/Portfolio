<?php
// Assuming you have a MySQL database connection established
$host = "localhost";
$username = "id22081870_usc";
$password = "#Uphsl123";
$database = "id22081870_usc";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if ID parameter is set
if(isset($_GET['ID'])) {
    // Escape user inputs for security
    $id = $conn->real_escape_string($_GET['ID']);
    
    // Fetch order details based on ID
    $sql = "SELECT * FROM orders WHERE ID='$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch order details
        $row = $result->fetch_assoc();
        $order_id = $row["ID"];
        $datetime = $row["DateTime"];
        $department = $row["department"];
        $product = $row["product"];
        $size = $row["size"];
        $quantity = $row["quantity"];
        $price = $row["price"];
        $subtotal = $row["subtotal"];
        $orderstatus = $row["orderstatus"];
    } else {
        echo "No order found with ID: $id";
        exit();
    }
} else {
    echo "ID parameter is missing.";
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escape user inputs for security
    $status = $conn->real_escape_string($_POST['status']);
    
    // Update order status in the database
    $update_sql = "UPDATE orders SET orderstatus='$status' WHERE ID='$id'";
    if ($conn->query($update_sql) === TRUE) {
        // Redirect to updated orders page
        header("Location: orders.php");
        exit();
    } else {
        echo "Error updating order status: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        form {
            text-align: center;
        }
        label {
            font-weight: bold;
            margin-right: 10px;
        }
        select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        input[type="submit"] {
            background-color: #7380ec;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #5f6cd7;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Order Status</h1>
        <form method="post">
            <label for="status">Order Status:</label>
            <select name="status" id="status">
                <option value="Pending" <?php if($orderstatus == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Cancelled" <?php if($orderstatus == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                <option value="Paid" <?php if($orderstatus == 'Paid') echo 'selected'; ?>>Paid</option>
            </select><br><br>
            <input type="submit" value="Save">
        </form>
    </div>
</body>
</html>

