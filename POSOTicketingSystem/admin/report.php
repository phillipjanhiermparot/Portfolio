<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection file
include 'connection.php';

// Fetch user data from login table
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, image FROM login WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$username = "ADMIN 123";
$imageData = null;

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $row['username'];
    $imageData = $row['image'];
}

// Get filter values (default to all if not set)
$filterLevel = isset($_GET['filter_level']) ? $_GET['filter_level'] : '';
$filterViolation = isset($_GET['filter_violation']) ? $_GET['filter_violation'] : '';

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination logic
$limit = 8; // Number of records per page
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page
$start = ($page - 1) * $limit; // Calculate the starting row

// Prepare the base query with a WHERE clause for search term
$sql = "
    SELECT
        r.ticket_number,
        r.violation_date,
        r.first_name,
        r.last_name,
        d.STATUS as status,
        CASE
            WHEN mv.ticket_number IS NOT NULL THEN 'Multiple Offense'
            WHEN v.ticket_number IS NOT NULL THEN 'First Offense'
            WHEN v2.ticket_number IS NOT NULL THEN 'Second Offense'
            WHEN v3.ticket_number IS NOT NULL THEN 'Third Offense'
            ELSE 'Unknown'
        END AS violation_level,
        CONCAT(
            IFNULL(v.first_violation, ''),
            IFNULL(v.others_violation, ''),
            IFNULL(v2.second_violation, ''),
            IFNULL(v2.others_violation, ''),
            IFNULL(v3.third_violation, ''),
            IFNULL(v3.others_violation, '')
        ) AS violations,
        r.created_at,
        d.* -- Include all columns from the discount table for violation filtering
    FROM
        report AS r
    LEFT JOIN
        violation AS v ON r.ticket_number = v.ticket_number
    LEFT JOIN
        2_violation AS v2 ON r.ticket_number = v2.ticket_number
    LEFT JOIN
        3_violation AS v3 ON r.ticket_number = v3.ticket_number
    LEFT JOIN discount as d ON r.ticket_number = d.ticket_number
    LEFT JOIN m_violation as mv ON r.ticket_number = mv.ticket_number
    WHERE
        (r.ticket_number LIKE :searchTerm
        OR r.first_name LIKE :searchTerm
        OR r.last_name LIKE :searchTerm)
";

// Add filter conditions
if ($filterLevel) {
    if (in_array($filterLevel, ['First Offense', 'Second Offense', 'Third Offense', 'Multiple Offense'])) {
        $sql .= " AND CASE
                        WHEN mv.ticket_number IS NOT NULL THEN 'Multiple Offense'
                        WHEN v.ticket_number IS NOT NULL THEN 'First Offense'
                        WHEN v2.ticket_number IS NOT NULL THEN 'Second Offense'
                        WHEN v3.ticket_number IS NOT NULL THEN 'Third Offense'
                       END = :filter_level";
    } elseif ($filterLevel === 'New') {
        $sql .= " AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
    } else {
        // Filter by status
        $sql .= " AND d.STATUS = :filter_level";
    }
}

if ($filterViolation) {
    $sql .= " AND d." . $filterViolation . " IS NOT NULL";
}

// Add order by clause for ticket number
$sql .= " ORDER BY r.ticket_number ASC";

// Add pagination limit
$sql .= " LIMIT :start, :limit";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':searchTerm', '%' . $searchTerm . '%'); // Wildcards for partial match
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

// Bind filter parameters if filters are applied
if ($filterLevel && $filterLevel !== 'New') {
    $stmt->bindValue(':filter_level', $filterLevel);
}
// No need to bind $filterViolation here, it's part of the SQL string directly

$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of records to calculate total pages
$totalStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM report AS r
    LEFT JOIN
        violation AS v ON r.ticket_number = v.ticket_number
    LEFT JOIN
        2_violation AS v2 ON r.ticket_number = v2.ticket_number
    LEFT JOIN
        3_violation AS v3 ON r.ticket_number = v3.ticket_number
    LEFT JOIN discount as d ON r.ticket_number = d.ticket_number
    LEFT JOIN m_violation as mv ON r.ticket_number = mv.ticket_number
    WHERE
        (r.ticket_number LIKE :searchTerm
        OR r.first_name LIKE :searchTerm
        OR r.last_name LIKE :searchTerm)
");

// Add the filter to the total count query
if ($filterLevel && $filterLevel !== 'New') {
    $totalStmt->bindValue(':filter_level', $filterLevel);
}
if ($filterViolation) {
    $totalStmt->queryString .= " AND d." . $filterViolation . " IS NOT NULL";
}
$totalStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
$totalStmt->execute();
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Function to sort reports
function sortReports($reports, $sortBy, $sortOrder) {
    usort($reports, function ($a, $b) use ($sortBy, $sortOrder) {
        $comparison = strcmp($a[$sortBy], $b[$sortBy]);
        return ($sortOrder == 'asc') ? $comparison : -$comparison;
    });
    return $reports;
}

// Handle sorting
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'ticket_number';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'asc';

$reports = sortReports($reports, $sortBy, $sortOrder);

// Violation list for the new filter dropdown
$violationsList = [
    '' => 'All Violations',
    'FTWH' => 'FAILURE TO WEAR HELMET',
    'OMN' => 'OPEN MUFFLER/NUISANCE',
    'ARG' => 'ARROGANT',
    'ONEWAY' => 'ONEWAY',
    'ILP' => 'ILLEGAL PARKING',
    'DWL' => 'DRIVING WITHOUT LICENSE/INVALID LICENSE',
    'NORCR' => 'NO OR/CR WHILE DRIVING',
    'DUV' => 'DRIVING UNREGISTERED VEHICLE',
    'UMV' => 'UNREGISTERED MOTOR VEHICLE',
    'OBS' => 'OBSTRUCTION',
    'DTS' => 'DISREGARDING TRAFFIC SIGNS',
    'DTO' => 'DISREGARDING TRAFFIC OFFICER',
    'TRB' => 'TRUCK BAN',
    'STV' => 'STALLED VEHICLE',
    'RCD' => 'RECKLESS DRIVING',
    'DUL' => 'DRIVING UNDER THE INFLUENCE OF LIQUOR',
    'INF' => 'INVALID OR NO FRANCHISE/COLORUM',
    'OOL' => 'OPERATING OUT OF LINE',
    'TCT' => 'TRIP - CUTTING',
    'OVL' => 'OVERLOADING',
    'LUZ' => 'LOADING/UNLOADING IN PROHIBITED ZONE',
    'IVA' => 'INVOLVE IN ACCIDENT',
    'SMB' => 'SMOKE BELCHING',
    'NSM' => 'NO SIDE MIRROR',
    'JWK' => 'JAY WALKING',
    'WSS' => 'WEARING SLIPPERS/SHORTS/SANDO',
    'ILV' => 'ILLEGAL VENDING',
    'IMP' => 'IMPOUNDED',
    'OTHERS' => 'OTHERS'
];

// Function to get violations from discount table and filter if needed
function getDiscountViolations($conn, $ticketNumber, $filterViolation = '', $violationsList = []) {
    $stmt = $conn->prepare("SELECT * FROM discount WHERE ticket_number = :ticket_number");
    $stmt->bindParam(':ticket_number', $ticketNumber);
    $stmt->execute();
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);

    $violations = [];
    if ($discount) {
        if ($discount['FTWH'] != null) $violations[] = 'FAILURE TO WEAR HELMET';
        if ($discount['OMN'] != null) $violations[] = 'OPEN MUFFLER/NUISANCE';
        if ($discount['ARG'] != null) $violations[] = 'ARROGANT';
        if ($discount['ONEWAY'] != null) $violations[] = 'ONEWAY';
        if ($discount['ILP'] != null) $violations[] = 'ILLEGAL PARKING';
        if ($discount['DWL'] != null) $violations[] = 'DRIVING WITHOUT LICENSE/INVALID LICENSE';
        if ($discount['NORCR'] != null) $violations[] = 'NO OR/CR WHILE DRIVING';
        if ($discount['DUV'] != null) $violations[] = 'DRIVING UNREGISTERED VEHICLE';
        if ($discount['UMV'] != null) $violations[] = 'UNREGISTERED MOTOR VEHICLE';
        if ($discount['OBS'] != null) $violations[] = 'OBSTRUCTION';
        if ($discount['DTS'] != null) $violations[] = 'DISREGARDING TRAFFIC SIGNS';
        if ($discount['DTO'] != null) $violations[] = 'DISREGARDING TRAFFIC OFFICER';
        if ($discount['TRB'] != null) $violations[] = 'TRUCK BAN';
        if ($discount['STV'] != null) $violations[] = 'STALLED VEHICLE';
        if ($discount['RCD'] != null) $violations[] = 'RECKLESS DRIVING';
        if ($discount['DUL'] != null) $violations[] = 'DRIVING UNDER THE INFLUENCE OF LIQUOR';
        if ($discount['INF'] != null) $violations[] = 'INVALID OR NO FRANCHISE/COLORUM';
        if ($discount['OOL'] != null) $violations[] = 'OPERATING OUT OF LINE';
        if ($discount['TCT'] != null) $violations[] = 'TRIP - CUTTING';
        if ($discount['OVL'] != null) $violations[] = 'OVERLOADING';
        if ($discount['LUZ'] != null) $violations[] = 'LOADING/UNLOADING IN PROHIBITED ZONE';
        if ($discount['IVA'] != null) $violations[] = 'INVOLVE IN ACCIDENT';
        if ($discount['SMB'] != null) $violations[] = 'SMOKE BELCHING';
        if ($discount['NSM'] != null) $violations[] = 'NO SIDE MIRROR';
        if ($discount['JWK'] != null) $violations[] = 'JAY WALKING';
        if ($discount['WSS'] != null) $violations[] = 'WEARING SLIPPERS/SHORTS/SANDO';
        if ($discount['ILV'] != null) $violations[] = 'ILLEGAL VENDING';
        if ($discount['IMP'] != null) $violations[] = 'IMPOUNDED';
        if ($discount['OTHERS'] != null) $violations[] = $discount['OTHERS']; // Include OTHERS violation
    }

    if ($filterViolation && isset($violationsList[$filterViolation])) {
        if ($discount[$filterViolation] != null) {
            return htmlspecialchars($violationsList[$filterViolation]);
        } else {
            return ''; // Should not happen due to the SQL filter, but for safety
        }
    }

    return htmlspecialchars(implode(', ', $violations));
}

// Function to check if a ticket is new (created within the last 24 hours)
function isNewTicket($createdAt) {
    $createdAtTimestamp = strtotime($createdAt);
    $twentyFourHoursAgo = strtotime('-24 hours');
    return $createdAtTimestamp > $twentyFourHoursAgo;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <title>Reports</title>
    <link rel="stylesheet" href="/POSO/admin/css/report.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .new-ticket {
            color: green;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .status-released {
            color: green;
        }
        .status-unreleased {
            color: red !important;
        }
        .status-red {
            color: red;
        }
        .status-unattended {
            color: darkorange !important;
        }
        .violation-multiple{
            color: red;
        }
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
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
        
        .copyright {
            text-align: center;
            margin-top: 100px;
            color: white;
            font-size: 0.9em;
        }

    </style>
</head>

<body>
    <img class="bg" src="/POSO/images/reports1.jpg" alt="Background Image">

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
                <li><a href="dashboard.php" > <i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="report.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="search-filter">
            <form action="report.php" method="get" class="filter-container">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <select name="filter_level">
                    <option value="">All Status</option>
                    <option value="First Offense" <?php echo ($filterLevel == 'First Offense') ? 'selected' : ''; ?>>First Offense</option>
                    <option value="Second Offense" <?php echo ($filterLevel == 'Second Offense') ? 'selected' : ''; ?>>Second Offense</option>
                    <option value="Third Offense" <?php echo ($filterLevel == 'Third Offense') ? 'selected' : ''; ?>>Third Offense</option>
                    <option value="Multiple Offense" <?php echo ($filterLevel == 'Multiple Offense') ? 'selected' : ''; ?>>Multiple Offense</option>
                    <option value="Overdue" <?php echo ($filterLevel == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                    <option value="New" style="display:none;">New</option>
                    <option value="Impounded" <?php echo ($filterLevel == 'Impounded') ? 'selected' : ''; ?>>Impounded</option>
                    <option value="Towed" <?php echo ($filterLevel == 'Towed') ? 'selected' : ''; ?>>Towed</option>
                    <option value="Unattended" <?php echo ($filterLevel == 'Unattended') ? 'selected' : ''; ?>>Unattended</option>
                    <option value="Released" <?php echo ($filterLevel == 'Released') ? 'selected' : ''; ?>>Released</option>
                    <option value="Unreleased" <?php echo ($filterLevel == 'Unreleased') ? 'selected' : ''; ?>>Unreleased</option>
                    <option value="License Confiscated" <?php echo ($filterLevel == 'License Confiscated') ? 'selected' : ''; ?>>License Confiscated</option>
                </select>
                <select name="filter_violation">
                    <?php foreach ($violationsList as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($filterViolation == $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
            <form id="exportForm" action="export_excel.php" method="post" style="margin-left: 10px;">
                <input type="hidden" name="export_search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <input type="hidden" name="export_filter_level" value="<?php echo htmlspecialchars($filterLevel); ?>">
                <input type="hidden" name="export_filter_violation" value="<?php echo htmlspecialchars($filterViolation); ?>">
                <button type="submit" id="exportSelected" disabled>Export selected to CSV</button>
                <input type="hidden" id="selected_tickets" name="selected_tickets" value="">
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="checkAll">
                    </th>
                    <th>
                        <a class="link" href="?sort=ticket_number&order=<?php echo ($sortBy == 'ticket_number' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($searchTerm); ?>&filter_level=<?php echo urlencode($filterLevel); ?>&filter_violation=<?php echo urlencode($filterViolation); ?>">
                            Ticket No. <i class="fa <?php echo ($sortBy == 'ticket_number' ? ($sortOrder == 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-arrows-up-down'); ?>"></i>
                        </a>
                    </th>
                    <th>Name</th>
                    <th>
                        <a class="link" href="?sort=violation_level&order=<?php echo ($sortBy == 'violation_level' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($searchTerm); ?>&filter_level=<?php echo urlencode($filterLevel); ?>&filter_violation=<?php echo urlencode($filterViolation); ?>">
                            Violation Level <i class="fa <?php echo ($sortBy == 'violation_level' ? ($sortOrder == 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-arrows-up-down'); ?>"></i>
                        </a>
                    </th>
                    <th>Violation/s</th>
                    <th>
                        <a class="link" href="?sort=violation_date&order=<?php echo ($sortBy == 'violation_date' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($searchTerm); ?>&filter_level=<?php echo urlencode($filterLevel); ?>&filter_violation=<?php echo urlencode($filterViolation); ?>">
                            Violation Date <i class="fa <?php echo ($sortBy == 'violation_date' ? ($sortOrder == 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-arrows-up-down'); ?>"></i>
                        </a>
                    </th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report) : ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_tickets[]" value="<?php echo htmlspecialchars($report['ticket_number']); ?>">
                        </td>
                        <td>
                            <?php echo htmlspecialchars($report['ticket_number']); ?>
                            <?php if (isNewTicket($report['created_at'])): ?>
                                <span class="new-ticket">NEW</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($report['first_name']) . ' ' . htmlspecialchars($report['last_name']); ?></td>
                        <td class="<?php if($report['violation_level'] == 'Multiple Offense') { echo "violation-multiple";}?>"><?php echo htmlspecialchars($report['violation_level']); ?></td>
                        <td><?php echo getDiscountViolations($conn, $report['ticket_number'], $filterViolation, $violationsList); ?></td>
                        <td><?php echo htmlspecialchars($report['violation_date']); ?></td>
                        <td class="<?php
                            switch (htmlspecialchars($report['status'])) {
                                case 'Released':
                                    echo 'status-released';
                                    break;
                                case 'Unreleased':
                                    echo 'status-unreleased';
                                    break;
                                case 'Impounded':
                                case 'Towed':
                                case 'License Confiscated':
                                    echo 'status-red';
                                    break;
                                case 'Unattended':
                                    echo 'status-unattended';
                                    break;
                                default:
                                    break;
                            }
                            ?>">
                            <?php echo htmlspecialchars($report['status']); ?>
                        </td>
                        <td>
                            <a href="sm.php?ticket_number=<?php echo htmlspecialchars($report['ticket_number']); ?>" class="pagination-btn">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
                // Show previous button only if not on the first page
                if ($page > 1):
            ?>
                <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($searchTerm); ?>&filter_level=<?php echo urlencode($filterLevel); ?>&filter_violation=<?php echo urlencode($filterViolation); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" class="pagination-btn previous">Prev</a>
            <?php endif; ?>

            <?php
                // Display numbered pagination links
                for ($i = 1; $i <= $totalPages; $i++):
                    $activeClass = ($i == $page) ? 'active' : ''; // Highlight the current page
            ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&filter_level=<?php echo urlencode($filterLevel); ?>&filter_violation=<?php echo urlencode($filterViolation); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" class="pagination-btn <?php echo $activeClass; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php
                // Show next button only if not on the last page
                if ($page < $totalPages):
            ?>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($searchTerm); ?>&filter_level=<?php echo urlencode($filterLevel); ?>&filter_violation=<?php echo urlencode($filterViolation); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" class="pagination-btn next">Next</a>
            <?php endif; ?>
        </div>
        
        <div class="copyright" style="line-height: 1.4; text-align: center; display: flex; align-items: center; justify-content: center; padding: 10px; color: white;">
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
        //hamburger and sidebar
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const sidebar = document.getElementById('sidebar');const overlay = document.getElementById('overlay');

        hamburgerIcon.addEventListener('click', function(event) {
            sidebar.classList.toggle('show'); // Toggle sidebar
            overlay.classList.toggle('show'); // Show overlay
            event.stopPropagation(); // Prevent immediate close
        });

        // Close sidebar & overlay when clicking on the overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Close sidebar & overlay when clicking outside of the sidebar
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && !hamburgerIcon.contains(event.target)) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

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

        // Get the "Check All" checkbox
        const checkAll = document.getElementById('checkAll');
        // Get all the ticket checkboxes
        const ticketCheckboxes = document.querySelectorAll('input[name="selected_tickets[]"]');
        const exportSelectedButton = document.getElementById('exportSelected');
        const selectedTicketsInput = document.getElementById('selected_tickets');


        let selectedTickets = new Set(); // Use a Set to store unique ticket numbers

        // Function to update export button state and selected tickets input
        function updateExportButtonState() {
            if (selectedTickets.size > 0) {
                exportSelectedButton.disabled = false;
            } else {
                exportSelectedButton.disabled = true;
            }
             // Update the hidden input field with comma-separated ticket numbers
            selectedTicketsInput.value = Array.from(selectedTickets).join(',');
        }

        // Event listener for the "Check All" checkbox
        checkAll.addEventListener('change', function() {
            ticketCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                 if (this.checked) {
                    selectedTickets.add(checkbox.value);
                } else {
                    selectedTickets.delete(checkbox.value);
                }
            });
            updateExportButtonState();
        });

        // Event listener for individual ticket checkboxes
        ticketCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    selectedTickets.add(this.value);
                } else {
                    selectedTickets.delete(this.value);
                }

                // If an individual checkbox is unchecked, uncheck the "Check All" checkbox
                if (!this.checked) {
                    checkAll.checked = false;
                } else {
                    // If all individual checkboxes are checked, check the "Check All" checkbox
                    let allChecked = true;
                    ticketCheckboxes.forEach(cb => {
                        if (!cb.checked) {
                            allChecked = false;
                        }
                    });
                    checkAll.checked = allChecked;
                }
                updateExportButtonState();
            });
        });

        // Event listener for the export form submission
        document.getElementById('exportForm').addEventListener('submit', function(event) {
             if (selectedTickets.size === 0) {
                event.preventDefault(); // Prevent form submission if no tickets are selected
                alert("Please select tickets to export."); // Show an error message
            }
        });

        updateExportButtonState(); // Initial call to set the button state

    </script>
</body>
</html>