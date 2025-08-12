<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPHSL University Supply Center Kiosk - Orders</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            margin: 20px;
        }

        .content-container {
            border: 2px solid #7380ec;
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Button style */
        button {
            padding: 15px 30px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color: #7380ec;
            color: #fff;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background-color: #5c67c4;
        }

        /* Total price style */
        .total-price {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        /* Checkout button style */
        .checkout-button {
            margin-top: 20px;
        }

        /* Back button style */
        .back-button {
            padding: 15px 30px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color: #7380ec;
            color: #fff;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .back-button:hover {
            background-color: #5c67c4;
        }
    </style>
</head>
<body>

<div class="content-container">
    <?php
    // Assuming you have a connection to your database
    $servername = "localhost";
    $username = "id22081870_usc";
    $password = "#Uphsl123";
    $dbname = "id22081870_usc";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the department number from the URL parameter
    $department = isset($_GET['department']) ? $_GET['department'] : '';

    // Display the department number
    echo "<h2>Department Number: $department</h2>";

    // Get the current date and time
    $currentDateTime = date('Y-m-d H:i:s');

    // Display the date and time
    echo "<p>Date and Time: $currentDateTime</p>";

    // Function to delete a product from the database
    function deleteProduct($conn, $productId) {
        $sql = "DELETE FROM orders WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->close();
    }

    // Check if a product delete request is made
    if (isset($_POST['delete_product'])) {
        $productId = $_POST['delete_product'];
        deleteProduct($conn, $productId);
    }

    // Query to fetch orders for the given department
    $sql = "SELECT * FROM orders WHERE department = '$department'";
    $result = $conn->query($sql);

    $totalPrice = 0; // Initialize total price variable

    // Check if there are any orders
    if ($result->num_rows > 0) {
        // Display the orders in a table
        echo "<table>";
        echo "<tr><th>Product</th><th>Size</th><th>Quantity</th><th>Price</th><th>Subtotal</th><th></th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['product']}</td>";
            echo "<td>{$row['size']}</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "<td>{$row['price']}</td>"; // Display the price
            echo "<td>{$row['subtotal']}</td>"; // Display the subtotal
            echo "<td><form method='post'><button type='submit' name='delete_product' value='{$row['ID']}'>Delete</button></form></td>";
            echo "</tr>";

            // Add the subtotal of each product to the total price
            $totalPrice += $row['subtotal'];
        }
        echo "</table>";

        // Display the total price below the table
        echo "<p class='total-price'>Total Price: $totalPrice</p>";

        // Button to alert that the order is "printed" and redirect
        echo "<button class='checkout-button' onclick=\"alert('Order printed. Click OK to return.'); window.location.href = 'index.html';\">Checkout</button>";
    } else {
        // No orders found, redirect to index.php
       echo "No orders found! Press back button to add items in cart. <br>"; 
    }

    $conn->close();
    ?>

    <!-- Back button -->
    <button class="back-button" onclick="goBack()">Back</button>
</div>

<script>
    function goBack() {
        window.history.back();
    }
</script>

</body>
</html>
