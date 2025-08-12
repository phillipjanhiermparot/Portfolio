<?php
// Database connection
$host = '127.0.0.1';
$db = 'poso';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

// Get the ticket number and license number from the URL
$ticket_number_search = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : '';
$license_number_search = isset($_GET['license_number']) ? $_GET['license_number'] : '';

// Initialize variables
$violations_data = [];
$search_by = '';
$violation_map = [
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
    'OTHERS' => 'OTHERS',
    'OTHERS_P' => 'OTHERS'
];

// Function to fetch discount data based on search criteria
function fetchDiscountData($pdo, $ticketNumber, $license) {
    $query = "SELECT *, STATUS AS vehicle_status FROM discount WHERE 1=1";
    $params = [];
    if (!empty($ticketNumber)) {
        $query .= " AND ticket_number = ?";
        $params[] = $ticketNumber;
    }
    if (!empty($license)) {
        $query .= " AND license = ?";
        $params[] = $license;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get violations and calculate total amount from discount data
function getViolationsAndAmountFromDiscount($discount, $violationMap) {
    $violations = [];
    $total_amount = 0;
    if ($discount) {
        foreach ($violationMap as $key => $value) {
            if (isset($discount[$key]) && $discount[$key] !== null) {
                if (is_numeric($discount[$key]) && $discount[$key] > 0) {
                    $violations[] = $value;
                    $total_amount += $discount[$key];
                } elseif (!is_numeric($discount[$key]) && $discount[$key] !== '0') {
                    $violations[] = $value; // Add non-numeric values (flags)
                }
            }
        }
    }
    return ['violations' => implode(', ', array_unique($violations)), 'amount' => $total_amount];
}

// Fetch data from discount and report tables
function fetchData($pdo, $ticketNumber, $license) {
    $discount_data_list = fetchDiscountData($pdo, $ticketNumber, $license);
    $results = [];
    foreach ($discount_data_list as $discount_data) {
        $violations_amount = getViolationsAndAmountFromDiscount($discount_data, $GLOBALS['violation_map']);
        $report_query = "SELECT confiscated, violation_date, violation_time FROM report WHERE ticket_number = ? AND license = ?";
        $stmt = $pdo->prepare($report_query);
        $stmt->execute([$discount_data['ticket_number'], $discount_data['license']]);
        $report_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $results[] = [
            'ticket_number' => $discount_data['ticket_number'],
            'license' => $discount_data['license'],
            'violation' => $violations_amount['violations'],
            'amount' => $violations_amount['amount'],
            'vehicle_status' => $discount_data['vehicle_status'] ?? 'N/A',
            'confiscated' => $report_data['confiscated'] ?? 'N/A',
            'violation_date' => $report_data['violation_date'] ?? 'N/A',
            'violation_time' => $report_data['violation_time'] ?? 'N/A'
        ];
    }
    return $results;
}

// Fetch data based on search parameters
if ($ticket_number_search) {
    $violations_data = fetchData($pdo, $ticket_number_search, '');
    $search_by = 'Ticket Number';
} elseif ($license_number_search) {
    $violations_data = fetchData($pdo, '', $license_number_search);
    $search_by = 'License Number';
}

// Clear the search
if (isset($_GET['clear'])) {
    $ticket_number_search = '';
    $license_number_search = '';
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/POSO/tracker/css/tracker.css">
    <title>POSO Violation Tracker</title>
    <style>
        .no-data {
            text-align: center;
            font-size: 18px;
            margin-top: 50px;
            color: #555;
        }
        .search-info {
            text-align: center;
            margin-top: 10px;
            font-style: italic;
            color: #777;
        }
        .copyright {
            text-align: center;
            margin-top: 100px;
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body>


    <div class="main-content">
        <header class="navbar">
            <img src="/POSO/images/left.png" alt="City Logo" class="logo">
            <div>
                <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
                <p class="city">CITY OF BIÑAN, LAGUNA</p>
            </div>
            <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
        </header>
        <br><br>

        <div class="report-container">
            <h1>Please enter a valid ticket number or license number</h1>
            <form method="GET" action="" class="search-filter">
                <div class="search-bar">
                    <input type="text" id="ticket_number" name="ticket_number" placeholder="Ticket Number" value="<?php echo $ticket_number_search; ?>">
                    <input type="text" id="license_number" name="license_number" placeholder="License Number" value="<?php echo $license_number_search; ?>">
                    <?php if ($ticket_number_search || $license_number_search): ?>
                        <a href="?clear=true" class="clear-search">CLEAR</a>
                    <?php endif; ?>
                    &nbsp;&nbsp;<button type="submit">Search</button>
                </div>
                <p>*License Number can be empty if Ticket Number is present.</p>
            </form>
        </div>

        <?php if ($search_by): ?>
            <p class="search-info">Searching by: <?php echo $search_by; ?></p>
        <?php endif; ?>

    </div>

    <?php if (!empty($violations_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Ticket Number</th>
                    <?php if ($search_by !== 'Ticket Number'): ?>
                        <th>License Number</th>
                    <?php endif; ?>
                    <th>Violation(s)</th>
                    <th>Total Amount</th>
                    <th>Vehicle Status</th>
                    <th>License Confiscated</th>
                    <th>Violation Date</th>
                    <th>Violation Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations_data as $violation): ?>
                    <tr>
                        <td><?php echo $violation['ticket_number']; ?></td>
                        <?php if ($search_by !== 'Ticket Number'): ?>
                            <td><?php echo $violation['license']; ?></td>
                        <?php endif; ?>
                        <td><?php echo $violation['violation']; ?></td>
                        <td><?php echo $violation['amount']; ?></td>
                        <td><?php echo $violation['vehicle_status'] ?? 'N/A'; ?></td>
                        <td><?php echo $violation['confiscated']; ?></td>
                        <td><?php echo $violation['violation_date']; ?></td>
                        <td><?php echo $violation['violation_time']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (($ticket_number_search || $license_number_search) && empty($violations_data)): ?>
        <p class="no-data">No data found for the provided <?php echo strtolower($search_by); ?> in the discount table.</p>
    <?php endif; ?>

    <div class="copyright">
     Copyright © <?php echo date('Y'); ?> Public Order and Safety Office. All Rights Reserved. <br>
     Developed by IT11 for academic purposes.
    </div>

</body>
</html>