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

// Function to update order status after 12 hours
function updateOrderStatus() {
    global $conn;
    $sql = "UPDATE orders SET orderstatus = 'Cancelled' WHERE TIMESTAMPDIFF(HOUR, DateTime, NOW()) >= 12 AND orderstatus = 'Pending'";
    $conn->query($sql);
}

// Call the function to update order status
updateOrderStatus();

// Define the limit for the number of orders to fetch initially


// Prepare SQL query to fetch limited number of orders from the orders table
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);

// Prepare SQL query to fetch username from the signup table
$sql_username = "SELECT username FROM signup"; // Assuming the ID of the user is 1
$result_username = $conn->query($sql_username);

if ($result_username->num_rows > 0) {
    // Output data of each row
    while($row_username = $result_username->fetch_assoc()) {
        $username = $row_username["username"];
    }
} else {
    $username = "Guest"; // If username is not found, fallback to a default value
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Material cdn -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp">
    <!-- Stylesheet -->
    <link rel="stylesheet" href="/dashboard/style.css">  
    <style>
    /* CSS to center the table and style it with borders */
    table {
        border-collapse: collapse;
        width: 135%; /* Adjust the width as per your requirement */
        margin: 0 auto; /* Center the table horizontally */
    }
    th, td {
        border: 1px solid #ddd; /* Border style */
        padding: 8px; /* Padding inside each cell */
        text-align: center; /* Center align the content */
    }
    th {
        background-color: #f2f2f2; /* Background color for table header */
    }
    .update-btn {
        background-color: #7380ec; /* Button color */
        color: white; /* Text color */
        padding: 8px 16px; /* Padding for the button */
        border: none; /* Remove button border */
        border-radius: 4px; /* Rounded corners */
        text-decoration: none; /* Remove default link underline */
        display: inline-block; /* Make it inline */
        cursor: pointer; /* Add pointer cursor on hover */
    }
    
</style>
</head>
<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="logo.png">
                </div>
                <div class="close" id="close-btn">
                    <span class="material-icons-sharp">close</span>
                </div>
            </div>
            <div class="sidebar">
                <a href="Dashboard.php">
                    <span class="material-icons-sharp"></span>
                    <h3>HOME</h3>
                </a>
                <a href="orders.php">
                    <span class="material-icons-sharp"></span>
                    <h3>ORDERS</h3>
                </a>
            </div>
        </aside>
        <!-----End of Sidebar----->
      
        <div class="right">
            <div class="top">
                <div class="profile">
                    <div class="info">
                        <p>Hey, <b><?php echo $username; ?></b></p>
                        <small class="text-muted">Admin</small>
                    </div>
                    <div class="profile-photo">
                        <span class="material-icons-sharp">account_circle</span>
                    </div>
                    <!-- Logout button -->
                    <a href="/dashboard/login/index.php" class="logout-btn">
                        <span class="material-icons-sharp">logout</span>
                    </a>
                </div>
            </div>

            <h1>Orders</h1>
            <table>
                <tr>
                    <th>ID</th>
                    <th>DateTime</th>
                    <th>Department</th>
                    <th>Product</th>
                    <th>Size</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
                <?php
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["ID"] . "</td>";
                        echo "<td>" . $row["DateTime"] . "</td>";
                        echo "<td>" . $row["department"] . "</td>";
                        echo "<td>" . $row["product"] . "</td>";
                        echo "<td>" . $row["size"] . "</td>";
                        echo "<td>" . $row["quantity"] . "</td>";
                        echo "<td>" . $row["price"] . "</td>";
                        echo "<td>" . $row["subtotal"] . "</td>";
                        echo "<td>" . $row["orderstatus"] . "</td>";
                        echo "<td><a class='update-btn' href='update_status.php?ID=" . $row["ID"] . "'>Update Order Status</a></td>";
                        echo "</tr>";
                    }
                   
                } else {
                    echo "<tr><td colspan='10'>No orders found.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</body>
</html>
