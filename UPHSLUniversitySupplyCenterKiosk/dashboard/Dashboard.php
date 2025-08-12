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

// Prepare SQL query to fetch username from the signup table
$sql = "SELECT username FROM signup "; // Assuming the ID of the user is 1
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $username = $row["username"];
    }
} else {
    $username = "Guest"; // If username is not found, fallback to a default value
}

// Prepare SQL query to fetch the sum and count of pending orders
$sql = "SELECT SUM(subtotal) AS total_pending_orders_sum, COUNT(*) AS total_pending_orders_count FROM orders WHERE orderstatus = 'Pending'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of the sum and count of pending orders
    $row = $result->fetch_assoc();
    $total_pending_orders_sum = $row["total_pending_orders_sum"];
    $total_pending_orders_count = $row["total_pending_orders_count"];
} else {
    $total_pending_orders_sum = 0; // If no pending orders found, set total pending orders sum to 0
    $total_pending_orders_count = 0; // If no pending orders found, set total pending orders count to 0
}


// Prepare SQL query to fetch the sum of subtotal for paid orders
$sql = "SELECT SUM(subtotal) AS total_sales FROM orders WHERE orderstatus = 'Paid'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of the sum of subtotal for paid orders
    $row = $result->fetch_assoc();
    $total_sales = $row["total_sales"];
    // Calculate percentage
    $percentage = ($total_sales / 100000) * 100;
    // Limit the percentage to 100 if it exceeds
    $percentage = min($percentage, 100);
} else {
    $total_sales = 0; // If no paid orders found, set total sales to 0
    $percentage = 0;
}

// Calculate Total Income
$total_income = $total_sales + $total_pending_orders_sum;


// Prepare SQL query to fetch sales data
$sql_sales = "SELECT DateTime, subtotal FROM orders WHERE orderstatus = 'Paid'";
$result_sales = $conn->query($sql_sales);

// Array to store sales data
$sales_data = array();

if ($result_sales->num_rows > 0) {
    // Output data of each row
    while($row_sales = $result_sales->fetch_assoc()) {
        // Store DateTime as X-axis labels and Subtotal as Y-axis values
        $sales_data[] = array(
            "DateTime" => $row_sales["DateTime"],
            "Subtotal" => $row_sales["subtotal"]
        );
    }
} else {
    echo "No sales data found";
}
$sql_paid_orders = "SELECT COUNT(*) AS total_paid_orders FROM orders WHERE orderstatus = 'Paid'";
$result_paid_orders = $conn->query($sql_paid_orders);

if ($result_paid_orders->num_rows > 0) {
    // Output data of the number of paid orders
    $row_paid_orders = $result_paid_orders->fetch_assoc();
    $total_paid_orders = $row_paid_orders["total_paid_orders"];
} else {
    $total_paid_orders = 0; // If no paid orders found, set total paid orders to 0
}

// Prepare SQL query to fetch the number of unique departments (counted as 1 if multiple orders are from the same department)

$sql_unique_departments = "SELECT COUNT(DISTINCT department) AS total_unique_departments FROM orders WHERE orderstatus IN ('Paid', 'Pending')";
$result_unique_departments = $conn->query($sql_unique_departments);

if ($result_unique_departments->num_rows > 0) {
    // Output data of the number of unique departments
    $row_unique_departments = $result_unique_departments->fetch_assoc();
    $total_unique_departments = $row_unique_departments["total_unique_departments"];
} else {
    $total_unique_departments = 0; // If no unique departments found, set total unique departments to 0
}
$sql_cancelled_orders = "SELECT COUNT(*) AS total_cancelled_orders FROM orders WHERE orderstatus = 'Cancelled'";
$result_cancelled_orders = $conn->query($sql_cancelled_orders);

if ($result_cancelled_orders->num_rows > 0) {
    // Output data of the number of cancelled orders
    $row_cancelled_orders = $result_cancelled_orders->fetch_assoc();
    $total_cancelled_orders = $row_cancelled_orders["total_cancelled_orders"];
} else {
    $total_cancelled_orders = 0; // If no cancelled orders found, set total cancelled orders to 0
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>

<!-- Material Icons CDN -->
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp">

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Stylesheet -->
<link rel="stylesheet" href="/dashboard/style.css">  

<style>
    .bar-graph {
        width: 80%;
        height: 400px;
        margin: 20px auto;
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
            <a href="../index.html">
                <span class="material-icons-sharp"></span>
                <h3>KIOSK</h3>
            </a>
        </div>
    </aside>
    <main>
        <h1>DASHBOARD</h1>
        
        <div class="insights">
            <div class="sales">
                <span class="material-icons-sharp">analytics</span>
                <div class="middle">
                    <div class="left">
                        <h3>Total Sales</h3>
                        <h1>P<?php echo number_format($total_sales, 2); ?></h1>
                    </div>
                    <div class="progress" style="--progress: <?php echo $percentage; ?>%;">
                        <div class="bar"></div>
                        <div class="number">
                            
                        </div>
                    </div>
                </div>
                <small class="text-muted">Last 24 Hours</small>
            </div>
            <!-- Total Pending Orders -->
            <div class="expenses">
                <span class="material-icons-sharp">bar_chart</span>
                <div class="middle">
                    <div class="left">
                        <h3>Total Pending Orders</h3>
                        <h1>P<?php echo number_format($total_pending_orders_sum, 2); ?></h1>
                        <h5><?php echo $total_pending_orders_count; ?> Orders</h5>
                    </div>
                    <!-- Progress bar for pending orders -->
                    <div class="progress">
                        <!-- Placeholder for percentage -->
                        <div class="number">
                            <!-- Placeholder for percentage -->
                        </div>
                    </div>
                </div>
                <small class="text-muted">Last 24 Hours</small>
            </div>
            <!-- End of Total Pending Orders -->
            <!----End of Sales---->
            <div class="income">
                <span class="material-icons-sharp">stacked_line_chart</span>
                <div class="middle">
                    <div class="left">
                        <h3>Total Income</h3>
                        <h1>P<?php echo number_format($total_income, 2); ?></h1>
                        <small>*Subject to change due to cancellation of pending orders within 12 hours</small>
                    </div>
                    <div class="progress">
                        <div class="number"></div>
                    </div>
                </div><br>
               
            </div>
            <!----End of Income---->
        </div>
        <!-- Bar Graph Section -->
        <h1>Sales Graph</h1>
        <canvas id="sales-chart" class="bar-graph"></canvas>
        <!-- End of Bar Graph Section -->
    </main>
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
                <a href="/dashboard/login/index.php" class="logout-btn">
                    <span class="material-icons-sharp">logout</span>
                </a>
            </div>
        </div>
        <div class="sales-analytics">
            <h2>Sales Analytics</h2>
            <div class="item offline">
                <div class="icon">
                    <span class="material-icons-sharp">local_mall</span>
                </div>
                <div class="right">
                    <div class="info">
                        <h3>Orders</h3>
                        <small class="text-muted">Last 24 Hours</small>
                    </div>
                    <!-- Display number of paid orders here -->
                    <h3><?php echo $total_paid_orders; ?></h3>
                </div>
            </div>
            <div class="item customers">
                <div class="icon">
                    <span class="material-icons-sharp">person</span>
                </div>
                <div class="right">
                    <div class="info">
                        <h3>New Customers</h3>
                        <small class="text-muted">Last 24 Hours</small>
                    </div>
                    <!-- Display number of unique departments (counted as 1 if multiple orders are from the same department) here -->
                    <h3><?php echo $total_unique_departments; ?></h3>
                    
                </div>
                
            </div>
            <div class="item offline">
                <div class="icon">
                    <span class="material-icons-sharp">local_mall</span>
                </div>
                <div class="right">
                    <div class="info">
                        <h3>Cancelled Orders</h3>
                        <small class="text-muted"></small>
                    </div>
                    <!-- Display number of paid orders here -->
                    <h3><?php echo $total_cancelled_orders; ?></h3>
                </div>
        </div>
    </div>
    </div>

<script>
// Convert PHP sales data to JavaScript format
var salesData = <?php echo json_encode($sales_data); ?>;

// Extract X-axis labels (DateTime) and Y-axis values (Subtotal)
var labels = salesData.map(function(item) {
    return item.DateTime;
});

var values = salesData.map(function(item) {
    return item.Subtotal;
});

// Create the bar graph using Chart.js
var ctx = document.getElementById('sales-chart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Sales',
            data: values,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
});
</script>

</body>
</html>
