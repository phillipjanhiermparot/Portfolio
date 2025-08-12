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

// Get the department number from the POST data
$department = isset($_POST['department']) ? $_POST['department'] : '';

// Get the product, size, quantity, and price from the POST data
$product = isset($_POST['product']) ? $_POST['product'] : '';
$size = isset($_POST['size']) ? $_POST['size'] : '';
$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : '';
$price = isset($_POST['price']) ? $_POST['price'] : '';

// Calculate the subtotal
$subTotal = $quantity * $price;

// Insert the order data into the database
$sql = "INSERT INTO orders (department, product, size, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssiid", $department, $product, $size, $quantity, $price, $subTotal);

if ($stmt->execute()) {
    echo "Item added to cart successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
