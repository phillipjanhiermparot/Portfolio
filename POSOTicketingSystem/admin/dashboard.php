<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'connection.php';

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, image FROM login WHERE ID = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $row['username'] ?? "ADMIN 123";
$imageData = $row['image'] ?? null;

try {
    // Fetch activity log for the current user
    $logQuery = "SELECT activity, timestamp FROM profile_activity_log WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT 5"; // Fetch last 5 activities for dashboard
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $logStmt->execute();
    $activityLog = $logStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log the error or handle it as needed
    error_log("Error fetching activity log for dashboard: " . $e->getMessage());
    $activityLog = []; // Initialize as empty array to avoid errors in display
}

// Function to fetch ticket counts per month
function getMonthlyTicketCounts($conn) {
    $monthlyCounts = array_fill(1, 12, 0); // Initialize counts for all months to 0
    $currentYear = date('Y');

    $stmt = $conn->prepare("
        SELECT
            MONTH(created_at) AS month,
            COUNT(*) AS ticket_count
        FROM report
        WHERE YEAR(created_at) = :year
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at)
    ");
    $stmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $monthlyCounts[(int)$result['month']] = (int)$result['ticket_count'];
    }

    return $monthlyCounts;
}

// Get the monthly ticket data
$monthlyTicketData = getMonthlyTicketCounts($conn);
$months = json_encode(array_keys($monthlyTicketData));
$ticketCounts = json_encode(array_values($monthlyTicketData));

// Month names for chart labels
$monthNames = json_encode([
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]);

// Query other dashboard statistics (as in your original code)
$stmt1 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM violation GROUP BY ticket_number");
$stmt1->execute();
$stmt2 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM 2_violation GROUP BY ticket_number");
$stmt2->execute();
$stmt3 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM 3_violation GROUP BY ticket_number");
$stmt3->execute();
$stmt4 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM report GROUP BY ticket_number");
$stmt4->execute();
$stmt5 = $conn->prepare("SELECT COUNT(*) as total_report_violations FROM report");
$stmt5->execute();
$row5 = $stmt5->fetch(PDO::FETCH_ASSOC);
$totalReportViolations = $row5['total_report_violations'] ?? 0;
$firstViolation = 0;
$secondViolation = 0;
$thirdViolation = 0;
$totalViolations = 0;
while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
    $firstViolation++;
}
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $secondViolation++;
}
while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    $thirdViolation++;
}
while ($row = $stmt4->fetch(PDO::FETCH_ASSOC)) {
    $totalViolations++;
}
$stmtNewTickets = $conn->prepare("SELECT COUNT(*) as new_ticket_count FROM report WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$stmtNewTickets->execute();
$rowNewTickets = $stmtNewTickets->fetch(PDO::FETCH_ASSOC);
$newTicketsCount = $rowNewTickets['new_ticket_count'] ?? 0;
$stmtOverdueTickets = $conn->prepare("SELECT COUNT(*) as overdue_ticket_count FROM discount WHERE STATUS = 'Unreleased'");
$stmtOverdueTickets->execute();
$rowOverdueTickets = $stmtOverdueTickets->fetch(PDO::FETCH_ASSOC);
$overdueTicketsCount = $rowOverdueTickets['overdue_ticket_count'] ?? 0;
$stmtTicketCount = $conn->prepare("SELECT COUNT(DISTINCT ticket_number) as ticket_count FROM discount");
$stmtTicketCount->execute();
$rowTicketCount = $stmtTicketCount->fetch(PDO::FETCH_ASSOC);
$ticketCount = $rowTicketCount['ticket_count'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="/POSO/admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <style>
body, html {
    overflow-x: hidden; /* Prevent horizontal scroll */
}

.data1{
    color: #023469 !important;
    text-align: center;
    font-size: 30px;
    letter-spacing: 7px;
    margin-top: 5%;
    position: relative;
    z-index: 11;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgb(0, 0, 0);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px; /* Space between text and lines */
    font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
}

.data1::before,
.data1::after {
    content: "";
    display: block;
    width: 100px;
    height: 3px;
    background-color: #023469;
}

.chart-container {
    margin: 100px 0 !important;
    width: 80%;
    max-width: 600px;
    height: 300px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
}
canvas#monthlyTicketBarChart {
    width: 900px !important;
    height: 400px !important;
}


        .timestamp {
            color: #777;
            font-size: 0.8em;
            float: right;
        }

        .copyright {
            text-align: center;
            margin-top: 100px;
            color: #777;
            font-size: 0.9em;
        }

       #inactivityDialog {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        z-index: 1003;
        text-align: center;
    }

    #inactivityDialog button {
        margin-top: 10px;
        padding: 8px 16px;
        cursor: pointer;
    }


    </style>

</head>

<body>
    <div id="overlay"></div>

    <div class="main-content">
        <header class="navbar">
            <img src="/POSO/images/left.png" alt="City Logo" class="logo">
            <div>
                <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
                <p class="city">CITY OF BIÑAN, LAGUNA</p>
            </div>
            <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">

            <div class="hamburger" id="hamburger-icon">
                <i class="fa fa-bars"></i>
            </div>
        </header>

        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="/POSO/images/right.png" alt="POSO Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"> <i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="report.php"> <i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

<div class="slider">
    <div class="slide-track">
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg1.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg8.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg3.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg4.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg5.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg6.jpg">
        </div>

        <div class="slide">
            <img class="carousel" src="/POSO/images/bg7.webp">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg2.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg9.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg10.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg11.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg12.jpg">
        </div>
    </div>
</div>
<br><Br>
<div class="data-analytics-container">
    <h1 class="data" style="text-align: center; color:white;">DATA ANALYTICS</h1> <br><br>
    <div class="analytics-container">
        <div class="container">
            <div class="c1">
                <a href="report.php?filter_level=New" class="DA">
                    <h2>New Tickets</h2> <br><br>
                    <div class="number-display">
                        <?php echo $newTicketsCount; ?>
                    </div>
                </a>
            </div>
        </div>
        <div class="container">
            <div class="c2">
                <a href="report.php?filter_level=Unreleased">
                    <h2>Unreleased Tickets</h2>  <br><br>
                    <div class="number-display">
                        <?php echo $overdueTicketsCount; ?>
                    </div>
                </a>
            </div>
        </div>
        <div class="container container-with-c3">
            <div class="c3">
                <h2>Ticket Count</h2>  <br><br>
                <div class="number-display">
                    <?php echo $ticketCount; ?>
                </div>
            </div>
        </div>
    </div>
<br><br>
    <h1 class="data1" style="text-align: center; color:white; white-space: nowrap;">MONTHLY TICKET STATISTICS</h1> <br><br>

    <div class="chart-container">
    <canvas id="monthlyTicketBarChart" ></canvas>
    </div>



    <script>
        //hamburger and sidebar
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        hamburgerIcon.addEventListener('click', function(event) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            event.stopPropagation();
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && !hamburgerIcon.contains(event.target)) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

// Monthly Ticket Bar Chart
const monthNames = <?php echo $monthNames; ?>;
const ticketCounts = <?php echo $ticketCounts; ?>;
const barChartCtx = document.getElementById('monthlyTicketBarChart').getContext('2d');
const monthlyTicketBarChart = new Chart(barChartCtx, {
    type: 'bar',
    data: {
        labels: monthNames,
        datasets: [{
            label: 'Number of Tickets',
            data: ticketCounts,
            backgroundColor: 'rgb(57, 155, 191)',
            borderColor: 'rgb(31, 98, 142)',
            borderWidth: 2,
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Tickets',
                    font: {
                        size: 18,
                        weight: 'bold',
                    }
                },
                grid: {
                    borderColor: 'rgb(0, 0, 0)',
                    borderWidth: 2,
                    lineWidth: 2,
                }
            },
            x: {
                title: {
                    display: true,
                    text: ' (<?php echo date('Y'); ?>)',
                    font: {
                        size: 18,
                        weight: 'bold',
                    }
                },
                grid: {
                    borderColor: 'rgb(0, 0, 0)',
                    borderWidth: 2,
                    lineWidth: 2,
                }
            }
        },
        plugins: {
            legend: {
                display: false // Hide the legend
            }
        }
    }
});

    </script>
    
        <div class="copyright" style="line-height: 1.4; text-align: center; display: flex; align-items: center; justify-content: center; padding: 10px; color: #777;">
        <img src="/POSO/images/ccs.png" alt="CCS Logo" style="height: 30px; margin-right: 15px;">
        <div style="display: flex; flex-direction: column; justify-content: center;">
            © <?php echo date('Y'); ?> POSO Biñan Ticketing System | Developed by Arielle Castillo, Brian Dimaguila, Yesha Jao, Phillip Parot. <br>
            IT11 – College of Computer Studies, UPHSL Biñan Campus
        </div>
    </div>


   <div id="inactivityDialog">
        <p>No activity detected. This user will be automatically logged out within <span id="countdown">30</span> seconds.</p>
        <button id="stayLoggedIn">Stay Logged In</button>
    </div>


    <script>
        // Inactivity timer
        let inactivityTimeout;
        let countdownInterval;
        let timeRemaining = 30;

        function setInactivityTimer() {
            inactivityTimeout = setTimeout(showInactivityDialog, 120000); // 2 minutes (120000 ms)
            resetCountdown(); // Initialize countdown
        }

        function resetInactivityTimer() {
            clearTimeout(inactivityTimeout);
            clearInterval(countdownInterval);
            timeRemaining = 30;
            setInactivityTimer();
        }

        function showInactivityDialog() {
            document.getElementById('inactivityDialog').style.display = 'block';
            startCountdown();
        }

        function startCountdown() {
            countdownInterval = setInterval(function() {
                timeRemaining--;
                document.getElementById('countdown').textContent = timeRemaining;
                if (timeRemaining <= 0) {
                    clearInterval(countdownInterval);
                    // Perform logout (redirect to logout page)
                    window.location.href = 'logout.php';
                }
            }, 1000);
        }

        function resetCountdown() {
             timeRemaining = 30;
             if (countdownInterval) {
                clearInterval(countdownInterval);
             }
             document.getElementById('countdown').textContent = timeRemaining;
        }

        // Reset timer on any user activity
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keydown', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('scroll', resetInactivityTimer);
        document.addEventListener('wheel', resetInactivityTimer);


        // Event listener for "Stay Logged In" button
        document.getElementById('stayLoggedIn').addEventListener('click', function() {
            document.getElementById('inactivityDialog').style.display = 'none';
            resetInactivityTimer();
        });

        // Start the timer when the page loads
        setInactivityTimer(); 

    </script>
</body>
</html>
