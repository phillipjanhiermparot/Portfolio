<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection file
include 'connection.php';

// Function to log activity
function logActivity($conn, $userId, $username, $activity) {
    $logQuery = "INSERT INTO profile_activity_log (user_id, username, activity, timestamp) VALUES (?, ?, ?, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(1, $userId, PDO::PARAM_INT);
    $logStmt->bindParam(2, $username);
    $logStmt->bindParam(3, $activity);
    $logStmt->execute();
    $logStmt->closeCursor();
}

// Get the ticket number from the query parameter
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : '';

// Fetch report details based on the ticket number
$stmt = $conn->prepare("
    SELECT
        r.ticket_number,
        r.violation_date,
        r.violation_time,
        r.first_name,
        r.middle_name,
        r.last_name,
        r.dob,
        r.address,
        r.license,
        r.registration,
        r.vehicle_owner,
        r.confiscated,
        r.street,
        r.city,
        r.vehicle_type,
        r.plate_number,
        r.signature AS violator_signature
    FROM
        report AS r
    WHERE
        r.ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();

$report = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if no report is found
if (!$report) {
    header("Location: report.php");
    exit();
}

// Initialize penalty-related variables
$status = $amount = $officer_name = $officer_signature = '';
$receipt_num = ''; // Initialize receipt_num

// Fetch violations from discount table
$stmt = $conn->prepare("
    SELECT * FROM discount WHERE ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

$selectedViolations = [];
$othersViolationText = '';
$othersViolationAmount = '';
$discountSubtotal = 0;
$previousViolations = [];

// Get previous violations for activity logging
if ($discount) {
    if ($discount['ARG'] != null) { $previousViolations['ARG'] = 'ARROGANT'; }
    if ($discount['DTO'] != null) { $previousViolations['DTO'] = 'DISREGARDING TRAFFIC OFFICER'; }
    if ($discount['DTS'] != null) { $previousViolations['DTS'] = 'DISREGARDING TRAFFIC SIGNS'; }
    if ($discount['DUL'] != null) { $previousViolations['DUL'] = 'DRIVING UNDER THE INFLUENCE OF LIQUOR'; }
    if ($discount['DUV'] != null) { $previousViolations['DUV'] = 'DRIVING UNREGISTERED VEHICLE'; }
    if ($discount['DWL'] != null) { $previousViolations['DWL'] = 'DRIVING WITHOUT LICENSE/INVALID LICENSE'; }
    if ($discount['FTWH'] != null) { $previousViolations['FTWH'] = 'FAILURE TO WEAR HELMET'; }
    if ($discount['INF'] != null) { $previousViolations['INF'] = 'INVALID OR NO FRANCHISE/COLORUM'; }
    if ($discount['ILP'] != null) { $previousViolations['ILP'] = 'ILLEGAL PARKING'; }
    if ($discount['ILV'] != null) { $previousViolations['ILV'] = 'ILLEGAL VENDING'; }
    if ($discount['IMP'] != null) { $previousViolations['IMP'] = 'IMPOUNDED'; }
    if ($discount['IVA'] != null) { $previousViolations['IVA'] = 'INVOLVE IN ACCIDENT'; }
    if ($discount['JWK'] != null) { $previousViolations['JWK'] = 'JAY WALKING'; }
    if ($discount['LUZ'] != null) { $previousViolations['LUZ'] = 'LOADING/UNLOADING IN PROHIBITED ZONE'; }
    if ($discount['NORCR'] != null) { $previousViolations['NORCR'] = 'NO OR/CR WHILE DRIVING'; }
    if ($discount['NSM'] != null) { $previousViolations['NSM'] = 'NO SIDE MIRROR'; }
    if ($discount['OBS'] != null) { $previousViolations['OBS'] = 'OBSTRUCTION'; }
    if ($discount['OMN'] != null) { $previousViolations['OMN'] = 'OPEN MUFFLER/NUISANCE'; }
    if ($discount['ONEWAY'] != null) { $previousViolations['ONEWAY'] = 'ONEWAY'; }
    if ($discount['OOL'] != null) { $previousViolations['OOL'] = 'OPERATING OUT OF LINE'; }
    if ($discount['OVL'] != null) { $previousViolations['OVL'] = 'OVERLOADING'; }
    if ($discount['RCD'] != null) { $previousViolations['RCD'] = 'RECKLESS DRIVING'; }
    if ($discount['SMB'] != null) { $previousViolations['SMB'] = 'SMOKE BELCHING'; }
    if ($discount['STV'] != null) { $previousViolations['STV'] = 'STALLED VEHICLE'; }
    if ($discount['TCT'] != null) { $previousViolations['TCT'] = 'TRIP - CUTTING'; }
    if ($discount['TRB'] != null) { $previousViolations['TRB'] = 'TRUCK BAN'; }
    if ($discount['UMV'] != null) { $previousViolations['UMV'] = 'UNREGISTERED MOTOR VEHICLE'; }
    if ($discount['WSS'] != null) { $previousViolations['WSS'] = 'WEARING SLIPPERS/SHORTS/SANDO'; }
}

//Get Status from discount table
if($discount){
    $status = $discount['STATUS'];
    $receipt_num = $discount['receipt_num']; // Get receipt number
}

if ($discount) {
    if ($discount['ARG'] != null) { $selectedViolations[] = 'ARROGANT'; $discountSubtotal += $discount['ARG']; }
    if ($discount['DTO'] != null) { $selectedViolations[] = 'DISREGARDING TRAFFIC OFFICER'; $discountSubtotal += $discount['DTO']; }
    if ($discount['DTS'] != null) { $selectedViolations[] = 'DISREGARDING TRAFFIC SIGNS'; $discountSubtotal += $discount['DTS']; }
    if ($discount['DUL'] != null) { $selectedViolations[] = 'DRIVING UNDER THE INFLUENCE OF LIQUOR'; $discountSubtotal += $discount['DUL']; }
    if ($discount['DUV'] != null) { $selectedViolations[] = 'DRIVING UNREGISTERED VEHICLE'; $discountSubtotal += $discount['DUV']; }
    if ($discount['DWL'] != null) { $selectedViolations[] = 'DRIVING WITHOUT LICENSE/INVALID LICENSE'; $discountSubtotal += $discount['DWL']; }
    if ($discount['FTWH'] != null) { $selectedViolations[] = 'FAILURE TO WEAR HELMET'; $discountSubtotal += $discount['FTWH']; }
    if ($discount['INF'] != null) { $selectedViolations[] = 'INVALID OR NO FRANCHISE/COLORUM'; $discountSubtotal += $discount['INF']; }
    if ($discount['ILP'] != null) { $selectedViolations[] = 'ILLEGAL PARKING'; $discountSubtotal += $discount['ILP']; }
    if ($discount['ILV'] != null) { $selectedViolations[] = 'ILLEGAL VENDING'; $discountSubtotal += $discount['ILV']; }
    if ($discount['IMP'] != null) { $selectedViolations[] = 'IMPOUNDED'; $discountSubtotal += $discount['IMP']; }
    if ($discount['IVA'] != null) { $selectedViolations[] = 'INVOLVE IN ACCIDENT'; $discountSubtotal += $discount['IVA']; }
    if ($discount['JWK'] != null) { $selectedViolations[] = 'JAY WALKING'; $discountSubtotal += $discount['JWK']; }
    if ($discount['LUZ'] != null) { $selectedViolations[] = 'LOADING/UNLOADING IN PROHIBITED ZONE'; $discountSubtotal += $discount['LUZ']; }
    if ($discount['NORCR'] != null) { $selectedViolations[] = 'NO OR/CR WHILE DRIVING'; $discountSubtotal += $discount['NORCR']; }
    if ($discount['NSM'] != null) { $selectedViolations[] = 'NO SIDE MIRROR'; $discountSubtotal += $discount['NSM']; }
    if ($discount['OBS'] != null) { $selectedViolations[] = 'OBSTRUCTION'; $discountSubtotal += $discount['OBS']; }
    if ($discount['OMN'] != null) { $selectedViolations[] = 'OPEN MUFFLER/NUISANCE'; $discountSubtotal += $discount['OMN']; }
    if ($discount['ONEWAY'] != null) { $selectedViolations[] = 'ONEWAY'; $discountSubtotal += $discount['ONEWAY']; }
    if ($discount['OOL'] != null) { $selectedViolations[] = 'OPERATING OUT OF LINE'; $discountSubtotal += $discount['OOL']; }
    if ($discount['OVL'] != null) { $selectedViolations[] = 'OVERLOADING'; $discountSubtotal += $discount['OVL']; }
    if ($discount['RCD'] != null) { $selectedViolations[] = 'RECKLESS DRIVING'; $discountSubtotal += $discount['RCD']; }
    if ($discount['SMB'] != null) { $selectedViolations[] = 'SMOKE BELCHING'; $discountSubtotal += $discount['SMB']; }
    if ($discount['STV'] != null) { $selectedViolations[] = 'STALLED VEHICLE'; $discountSubtotal += $discount['STV']; }
    if ($discount['TCT'] != null) { $selectedViolations[] = 'TRIP - CUTTING'; $discountSubtotal += $discount['TCT']; }
    if ($discount['TRB'] != null) { $selectedViolations[] = 'TRUCK BAN'; $discountSubtotal += $discount['TRB']; }
    if ($discount['UMV'] != null) { $selectedViolations[] = 'UNREGISTERED MOTOR VEHICLE'; $discountSubtotal += $discount['UMV']; }
    if ($discount['WSS'] != null) { $selectedViolations[] = 'WEARING SLIPPERS/SHORTS/SANDO'; $discountSubtotal += $discount['WSS']; }


    if ($discount['OTHERS'] != null) {
        $othersViolationText = $discount['OTHERS'];
    }
    if ($discount['OTHERS_P'] != null) {
        $othersViolationAmount = (float)$discount['OTHERS_P'];
    } else {
        $othersViolationAmount = 0;
    }
}

// Calculate the total amount
$amount = $discountSubtotal + $othersViolationAmount;

// Check if license and ticket_number exist in m_violation
$stmt_m_violation = $conn->prepare("
    SELECT mo_firstname, mo_lastname, mo_signature
    FROM m_violation
    WHERE license = :license AND ticket_number = :ticket_number
");
$stmt_m_violation->bindParam(':license', $report['license']);
$stmt_m_violation->bindParam(':ticket_number', $ticket_number);
$stmt_m_violation->execute();
$officer_m_violation = $stmt_m_violation->fetch(PDO::FETCH_ASSOC);

if ($officer_m_violation) {
    $officer_name = $officer_m_violation['mo_firstname'] . ' ' . $officer_m_violation['mo_lastname'];
    $officer_signature = $officer_m_violation['mo_signature'];
} else {
    // Fetch officer details from other violation tables
    $stmt_violation = $conn->prepare("
        SELECT o_firstname, o_lastname, o_signature FROM violation WHERE ticket_number = :ticket_number
        UNION
        SELECT 2o_firstname, 2o_lastname, 2o_signature FROM 2_violation WHERE ticket_number = :ticket_number
        UNION
        SELECT 3o_firstname, 3o_lastname, 3o_signature FROM 3_violation WHERE ticket_number = :ticket_number
    ");
    $stmt_violation->bindParam(':ticket_number', $ticket_number);
    $stmt_violation->execute();
    $officer = $stmt_violation->fetch(PDO::FETCH_ASSOC);

    if ($officer) {
        $officer_name = $officer['o_firstname'] . ' ' . $officer['o_lastname'];
        $officer_signature = $officer['o_signature'];
    }
}

$isPaid = ($status === 'Released');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details | POSO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"> </script>
    <link rel="stylesheet" href="/POSO/admin/css/sm.css">
</head>
<body>
<div id="overlay"></div>
    <nav class="navbar">
        <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
        <div>
            <p class="public" >PUBLIC ORDER & SAFETY OFFICE</p>
            <p class="city">CITY OF BIÑAN, LAGUNA</p>
        </div>
        <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
        <div class="hamburger" id="hamburger-icon">
            <i class="fa fa-bars"></i>
        </div>
    </nav>
    <img class="bg" src="/POSO/images/plaza1.jpg" alt="Background Image">
    <?php
        $current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
    ?>

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
    </header>
    <img class="bg" src="/POSO/images/plaza1.jpg" alt="Background Image">
    <form class="sm mb-5 pb-5" method="POST" action="update_report.php?ticket_number=<?= $_GET['ticket_number'] ?>">
        <div class="inside">
            <h2 class="gray" style="display: flex; justify-content: space-between; align-items: center;">
                ORDINANCE INFRACTION TICKET
                <span style="color: red;">NO. <?= htmlspecialchars($report['ticket_number']) ?></span>
            </h2>
            <div class="violator-info">
                <br>
                <h3 class="title">VIOLATOR INFORMATION</h3>
                <br>
                <div class="info-container">
                    <div><strong>First Name:</strong></div>
                    <div><input type="text" name="first_name" value="<?= htmlspecialchars($report['first_name']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Middle Name:</strong></div>
                    <div><input type="text" name="middle_name" value="<?= htmlspecialchars($report['middle_name']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Last Name:</strong></div>
                    <div><input type="text" name="last_name" value="<?= htmlspecialchars($report['last_name']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Birthday:</strong></div>
                    <div><input type="date" name="dob" value="<?= htmlspecialchars($report['dob']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Address:</strong></div>
                    <div><input type="text" name="address" value="<?= htmlspecialchars($report['address']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>License Number:</strong></div>
                    <div><input type="text" name="license" value="<?= htmlspecialchars($report['license']) ?>" readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Violation Date:</strong></div>
                    <div><input type="date" name="violation_date" value="<?= htmlspecialchars($report['violation_date']) ?>" readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Violation Time:</strong></div>
                    <div><input type="time" name="violation_time" value="<?= htmlspecialchars($report['violation_time']) ?>" readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>License Confiscated:</strong></div>
                    <div>
                        <select name="confiscated" <?= $isPaid ? 'disabled' : '' ?>>
                            <option value="yes" <?= ($report['confiscated'] == 'yes') ? 'selected' : '' ?>>Yes</option>
                            <option value="no" <?= ($report['confiscated'] == 'no') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="place-info">
                <br>
                <h3 class="title">PLACE OF VIOLATION</h3>
                <br>
                <div class="info-container">
                    <div><strong>Street:</strong></div>
                    <div><input type="text" name="street" value="<?= htmlspecialchars($report['street']) ?>"readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>City/Municipality:</strong></div>
                    <div><input type="text" name="city" value="<?= htmlspecialchars($report['city']) ?>"readonly></div>
                </div>
                <div class="info-container">
    <div><strong>Vehicle Type:</strong></div>
    <div>
        <input type="text" id="vehicle_type" name="vehicle_type" class="form-control" value="<?= htmlspecialchars($report['vehicle_type']) ?>" <?= $isPaid ? 'readonly' : '' ?>>
    </div>
</div>
                    </div>
                </div>
                <div class="info-container">
                    <div><strong>Plate Number:</strong></div>
                    <div><input type="text" name="plate_number" value="<?= htmlspecialchars($report['plate_number']) ?>"readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Registration Number:</strong></div>
                    <div><input type="text" name="registration" value="<?= htmlspecialchars($report['registration']) ?>"readonly></div>
                </div><div class="info-container">
                    <div><strong>Vehicle Owner:</strong></div>
                    <div><input type="text" name="vehicle_owner" value="<?= htmlspecialchars($report['vehicle_owner']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
            </div>

            <div class="penalty-info">
                <br>
                <h3 class="title">VIOLATIONS and PENALTY</h3> <br>

                <div class="section">
                    <select class="violations" name="violations[]" multiple="multiple" style="width: 100%;" <?= $isPaid ? 'disabled' : '' ?>>
                        <option value="ARROGANT" data-price="1000" <?= in_array('ARROGANT', $selectedViolations) ? 'selected' : '' ?>>ARROGANT</option>
                        <option value="DISREGARDING TRAFFIC OFFICER" data-price="200" <?= in_array('DISREGARDING TRAFFIC OFFICER', $selectedViolations) ? 'selected' : '' ?>>DISREGARDING TRAFFIC OFFICER</option>
                        <option value="DISREGARDING TRAFFIC SIGNS" data-price="200" <?= in_array('DISREGARDING TRAFFIC SIGNS', $selectedViolations) ? 'selected' : '' ?>>DISREGARDING TRAFFIC SIGNS</option>
                        <option value="DRIVING UNDER THE INFLUENCE OF LIQUOR" data-price="200" <?= in_array('DRIVING UNDER THE INFLUENCE OF LIQUOR', $selectedViolations) ? 'selected' : '' ?>>DRIVING UNDER THE INFLUENCE OF LIQUOR</option>
                        <option value="DRIVING UNREGISTERED VEHICLE" data-price="500" <?= in_array('DRIVING UNREGISTERED VEHICLE', $selectedViolations) ? 'selected' : '' ?>>DRIVING UNREGISTERED VEHICLE</option>
                        <option value="DRIVING WITHOUT LICENSE/INVALID LICENSE" data-price="1000" <?= in_array('DRIVING WITHOUT LICENSE/INVALID LICENSE', $selectedViolations) ? 'selected' : '' ?>>DRIVING WITHOUT LICENSE/INVALID LICENSE</option>
                        <option value="FAILURE TO WEAR HELMET" data-price="200" <?= in_array('FAILURE TO WEAR HELMET', $selectedViolations) ? 'selected' : '' ?>>FAILURE TO WEAR HELMET</option>
                        <option value="ILLEGAL PARKING" data-price="200" <?= in_array('ILLEGAL PARKING', $selectedViolations) ? 'selected' : '' ?>>ILLEGAL PARKING</option>
                        <option value="ILLEGAL VENDING" data-price="200" <?= in_array('ILLEGAL VENDING', $selectedViolations) ? 'selected' : '' ?>>ILLEGAL VENDING</option>
                        <option value="IMPOUNDED" data-price="800" <?= in_array('IMPOUNDED', $selectedViolations) ? 'selected' : '' ?>>IMPOUNDED</option>
                        <option value="INVOLVE IN ACCIDENT" data-price="200" <?= in_array('INVOLVE IN ACCIDENT', $selectedViolations) ? 'selected' : '' ?>>INVOLVE IN ACCIDENT</option>
                        <option value="JAY WALKING" data-price="200" <?= in_array('JAY WALKING', $selectedViolations) ? 'selected' : '' ?>>JAY WALKING</option>
                        <option value="LOADING/UNLOADING IN PROHIBITED ZONE" data-price="200" <?= in_array('LOADING/UNLOADING IN PROHIBITED ZONE', $selectedViolations) ? 'selected' : '' ?>>LOADING/UNLOADING IN PROHIBITED ZONE</option>
                        <option value="NO OR/CR WHILE DRIVING" data-price="500" <?= in_array('NO OR/CR WHILE DRIVING', $selectedViolations) ? 'selected' : '' ?>>NO OR/CR WHILE DRIVING</option>
                        <option value="NO SIDE MIRROR" data-price="200" <?= in_array('NO SIDE MIRROR', $selectedViolations) ? 'selected' : '' ?>>NO SIDE MIRROR</option>
                        <option value="OPEN MUFFLER/NUISANCE" data-price="1000" <?= in_array('OPEN MUFFLER/NUISANCE', $selectedViolations) ? 'selected' : '' ?>>OPEN MUFFLER/NUISANCE</option>
                        <option value="ONEWAY" data-price="200" <?= in_array('ONEWAY', $selectedViolations) ? 'selected' : '' ?>>ONEWAY</option>
                        <option value="OPERATING OUT OF LINE" data-price="2000" <?= in_array('OPERATING OUT OF LINE', $selectedViolations) ? 'selected' : '' ?>>OPERATING OUT OF LINE</option>
                        <option value="OVERLOADING" data-price="200" <?= in_array('OVERLOADING', $selectedViolations) ? 'selected' : '' ?>>OVERLOADING</option>
                        <option value="RECKLESS DRIVING" data-price="100" <?= in_array('RECKLESS DRIVING', $selectedViolations) ? 'selected' : '' ?>>RECKLESS DRIVING</option>
                        <option value="SMOKE BELCHING" data-price="500" <?= in_array('SMOKE BELCHING', $selectedViolations) ? 'selected' : '' ?>>SMOKE BELCHING</option>
                        <option value="STALLED VEHICLE" data-price="200" <?= in_array('STALLED VEHICLE', $selectedViolations) ? 'selected' : '' ?>>STALLED VEHICLE</option>
                        <option value="TRIP - CUTTING" data-price="200" <?= in_array('TRIP - CUTTING', $selectedViolations) ? 'selected' : '' ?>>TRIP - CUTTING</option>
                        <option value="TRUCK BAN" data-price="200" <?= in_array('TRUCK BAN', $selectedViolations) ? 'selected' : '' ?>>TRUCK BAN</option>
                        <option value="UNREGISTERED MOTOR VEHICLE" data-price="500" <?= in_array('UNREGISTERED MOTOR VEHICLE', $selectedViolations) ? 'selected' : '' ?>>UNREGISTERED MOTOR VEHICLE</option>
                        <option value="INVALID OR NO FRANCHISE/COLORUM" data-price="2000" <?= in_array('INVALID OR NO FRANCHISE/COLORUM', $selectedViolations) ? 'selected' : '' ?>>INVALID OR NO FRANCHISE/COLORUM</option>
                        <option value="WEARING SLIPPERS/SHORTS/SANDO" data-price="300" <?= in_array('WEARING SLIPPERS/SHORTS/SANDO', $selectedViolations) ? 'selected' : '' ?>>WEARING SLIPPERS/SHORTS/SANDO</option>
                        <option value="OBSTRUCTION" data-price="200" <?= in_array('OBSTRUCTION', $selectedViolations) ? 'selected' : '' ?>>OBSTRUCTION</option>
                    </select>
                    <br><br>

                    <div class="info-container ">
                        <div><strong>Subtotal:</strong></div>
                        <div><input type="number" name="subtotal" id="subtotal" value="<?= $discountSubtotal ?>" step="0.01" readonly></div>
                    </div>

                    <?php if (!empty($othersViolationText)): ?>
                        <div class="info-container"><div>
                            <strong>Others Violation:</strong>
                        </div>
                        <div>
                            <input type="text" name="others_violation_text" value="<?= htmlspecialchars($othersViolationText) ?>" <?= $isPaid ? 'readonly' : '' ?>>
                        </div>
                        </div>
                        <div class="info-container">
                            <div>
                                <strong>Others Violation Amount:</strong>
                            </div>
                            <div>
                                <input type="number" name="others_violation_amount" id="others_violation_amount" value="<?= htmlspecialchars($othersViolationAmount) ?>" step="0.01" <?= $isPaid ? 'readonly' : '' ?>>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="info-container">
                        <div><strong>Status:</strong></div>
                        <div>
                            <select name="status" id="status-select" <?= $isPaid ? 'disabled' : '' ?>>
                                <option value="Impounded" <?= $status == 'Impounded'? 'selected' : '' ?>>Impounded</option>
                                <option value="Towed" <?= $status == 'Towed' ? 'selected' : '' ?>>Towed</option>
                                <option value="Unattended" <?= $status == 'Unattended' ? 'selected' : '' ?>>Unattended</option>
                                <option value="Released" <?= $status == 'Released' ? 'selected' : '' ?>>Released</option>
                                <option value="Unreleased" <?= $status == 'Unreleased' ? 'selected' : '' ?>>Unreleased</option>
                                <option value="License Confiscated" <?= $status == 'License Confiscated' ? 'selected' : '' ?>>License Confiscated</option>
                            </select>
                        </div>
                    </div>
                    <div class="info-container" id="receipt-num-container" style="display: <?= $status == 'Released' ? 'flex' : 'none' ?>;">
                        <div><strong>Receipt Number:</strong></div>
                        <div>
                            <input type="text" name="receipt_num" value="<?= htmlspecialchars($receipt_num) ?>" <?= $isPaid ? 'readonly' : '' ?> <?= ($status == 'Released' && !$isPaid) ? 'required' : '' ?>>
                        </div>
                    </div>
                    <div class="info-container">
                        <div><strong>Total Amount:</strong></div>
                        <div><input type="number" name="amount" id="total_amount" value="<?= htmlspecialchars($amount) ?>" step="0.01" readonly></div>
                    </div>
                    <div class="info-container">
                        <div><strong>Officer In Charge:</strong></div>
                        <div><input type="text" name="officer_name" value="<?= htmlspecialchars($officer_name) ?>"readonly></div>
                    </div>

                    <h3 class="title">SIGNATURES</h3> <br>
                    <div class="info-container">
                        <div><strong>Officer Signature:</strong></div>
                        <div>
                            <?php if ($officer_signature): ?>
                                <img src="data:image/png;base64,<?= base64_encode($officer_signature) ?>" alt="Officer Signature" class="signature-img">
                            <?php else: ?>
                                <?= htmlspecialchars($officer_signature) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-container">
                        <div><strong>Violator's Signature:</strong></div><br><br><br><br><br><br>
                        <div>
                            <?php if ($report['violator_signature']): ?>
                                <img src="data:image/png;base64,<?= base64_encode($report['violator_signature']) ?>" alt="Violator Signature" class="signature-img">
                            <?php else: ?>
                                <?= htmlspecialchars($report['violator_signature']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align: center;">
                <button type="submit" class="update-btn" <?= $isPaid ? 'style="display:none;"' : '' ?>>Update</button>
                <a href="vb.php?ticket_number=<?= htmlspecialchars($report['ticket_number']) ?>" class="see" <?= $isPaid ? 'style="display:none;"' : '' ?>>See Breakdown of Violations</a>
                <a href="report.php" class="back"> Back to Reports </a>
            </div>
        </form>
    </div>

    <script>
        //hamburger and sidebar
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

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



        //DROPDOWN of violations
        $(document).ready(function() {
            // Enable Select2 on the dropdown
            $('.violations').select2({
                placeholder: "Select Violations ▼",
                allowClear: true,
                closeOnSelect: false, // Keep dropdown open to select multiple options
            });

            // Function to update total amount based on selected violations and others amount
            function updateTotalAmount() {
                let subtotal = 0;
                $('.violations :selected').each(function() {
                    subtotal += parseFloat($(this).data('price')) || 0;
                });
                let othersAmount = parseFloat($('#others_violation_amount').val()) || 0;
                $('#subtotal').val(subtotal.toFixed(2));
                $('#total_amount').val((subtotal + othersAmount).toFixed(2));
            }

            // Calculate initial total amount
            updateTotalAmount();

            // Calculate subtotal on violation selection change
            $('.violations').on('change', function() {
                updateTotalAmount();
            });

            $('#others_violation_amount').on('input', function() {
                updateTotalAmount();
            });

            // Show/hide receipt number input and make it required
            $('#status-select').on('change', function() {
                const receiptNumContainer = $('#receipt-num-container');
                const receiptNumInput = $('input[name="receipt_num"]');
                if ($(this).val() === 'Released') {
                    receiptNumContainer.show();
                    receiptNumInput.prop('required', true);
                } else {
                    receiptNumContainer.hide();
                    receiptNumInput.prop('required', false);
                }
            });
        });
    </script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$isPaid) {
    $updatedViolations = isset($_POST['violations']) ? $_POST['violations'] : [];
    $updatedOthersViolationText = isset($_POST['others_violation_text']) ? $_POST['others_violation_text'] : '';
    $updatedOthersViolationAmount = isset($_POST['others_violation_amount']) ? floatval($_POST['others_violation_amount']) : 0.00;
    $updatedStatus = $_POST['status'];
    $updatedReceiptNum = ($updatedStatus == 'Released') ? $_POST['receipt_num'] : null;

    $updateDiscountSql = "UPDATE discount SET
        ARG = NULL, DTO = NULL, DTS = NULL, DUL = NULL, DUV = NULL, DWL = NULL, FTWH = NULL, INF = NULL,
        ILP = NULL, ILV = NULL, IMP = NULL, IVA = NULL, JWK = NULL, LUZ = NULL, NORCR = NULL, NSM = NULL,
        OBS = NULL, OMN = NULL, ONEWAY = NULL, OOL = NULL, OVL = NULL, RCD = NULL, SMB = NULL, STV = NULL,
        TCT = NULL, TRB = NULL, UMV = NULL, WSS = NULL,
        OTHERS = :others_text, OTHERS_P = :others_amount, STATUS = :status, receipt_num = :receipt_num
        WHERE ticket_number = :ticket_number";

    $updateDiscountStmt = $conn->prepare($updateDiscountSql);
    $updateDiscountStmt->bindParam(':others_text', $updatedOthersViolationText);
    $updateDiscountStmt->bindParam(':others_amount', $updatedOthersViolationAmount, PDO::PARAM_FLOAT);
    $updateDiscountStmt->bindParam(':status', $updatedStatus);
    $updateDiscountStmt->bindParam(':receipt_num', $updatedReceiptNum);
    $updateDiscountStmt->bindParam(':ticket_number', $ticket_number);
    $updateDiscountStmt->execute();

    $violationUpdates = [
        'ARROGANT' => 'ARG',
        'DISREGARDING TRAFFIC OFFICER' => 'DTO',
        'DISREGARDING TRAFFIC SIGNS' => 'DTS',
        'DRIVING UNDER THE INFLUENCE OF LIQUOR' => 'DUL',
        'DRIVING UNREGISTERED VEHICLE' => 'DUV',
        'DRIVING WITHOUT LICENSE/INVALID LICENSE' => 'DWL',
        'FAILURE TO WEAR HELMET' => 'FTWH',
        'INVALID OR NO FRANCHISE/COLORUM' => 'INF',
        'ILLEGAL PARKING' => 'ILP',
        'ILLEGAL VENDING' => 'ILV',
        'IMPOUNDED' => 'IMP',
        'INVOLVE IN ACCIDENT' => 'IVA',
        'JAY WALKING' => 'JWK',
        'LOADING/UNLOADING IN PROHIBITED ZONE' => 'LUZ',
        'NO OR/CR WHILE DRIVING' => 'NORCR',
        'NO SIDE MIRROR' => 'NSM',
        'OBSTRUCTION' => 'OBS',
        'OPEN MUFFLER/NUISANCE' => 'OMN',
        'ONEWAY' => 'ONEWAY',
        'OPERATING OUT OF LINE' => 'OOL',
        'OVERLOADING' => 'OVL',
        'RECKLESS DRIVING' => 'RCD',
        'SMOKE BELCHING' => 'SMB',
        'STALLED VEHICLE' => 'STV',
        'TRIP - CUTTING' => 'TCT',
        'TRUCK BAN' => 'TRB',
        'UNREGISTERED MOTOR VEHICLE' => 'UMV',
        'WEARING SLIPPERS/SHORTS/SANDO' => 'WSS',
    ];

    foreach ($violationUpdates as $violationName => $violationCode) {
        $present = in_array($violationName, $updatedViolations);
        $wasPresent = isset($previousViolations[$violationCode]);

        if ($present && !$wasPresent) {
            // Violation added
            $updateViolationPriceSql = "SELECT price FROM ordinance WHERE violation_name = :violation_name";
            $updateViolationPriceStmt = $conn->prepare($updateViolationPriceSql);
            $updateViolationPriceStmt->bindParam(':violation_name', $violationName);
            $updateViolationPriceStmt->execute();
            $violationData = $updateViolationPriceStmt->fetch(PDO::FETCH_ASSOC);
            if ($violationData && isset($violationData['price'])) {
                $updatePriceSql = "UPDATE discount SET $violationCode = :price WHERE ticket_number = :ticket_number";
                $updatePriceStmt = $conn->prepare($updatePriceSql);
                $updatePriceStmt->bindParam(':price', $violationData['price'], PDO::PARAM_INT);
                $updatePriceStmt->bindParam(':ticket_number', $ticket_number);
                $updatePriceStmt->execute();
                logActivity($conn, $_SESSION['user_id'], $_SESSION['username'], "added '$violationName' violation in ticket number '$ticket_number' successfully.");
            }
        } elseif (!$present && $wasPresent) {
            // Violation removed
            $updatePriceNullSql = "UPDATE discount SET $violationCode = NULL WHERE ticket_number = :ticket_number";
            $updatePriceNullStmt = $conn->prepare($updatePriceNullSql);
            $updatePriceNullStmt->bindParam(':ticket_number', $ticket_number);
            $updatePriceNullStmt->execute();
            logActivity($conn, $_SESSION['user_id'], $_SESSION['username'], "removed '$violationName' violation in ticket number '$ticket_number' successfully.");
        }
    }

    // Update other report details
    $updateReportSql = "UPDATE report SET
        first_name = :first_name,
        middle_name = :middle_name,
        last_name = :last_name,
        dob = :dob,
        address = :address,
        confiscated = :confiscated,
        vehicle_type = :vehicle_type,
        vehicle_owner = :vehicle_owner
        WHERE ticket_number = :ticket_number";

    $updateReportStmt = $conn->prepare($updateReportSql);
    $updateReportStmt->bindParam(':first_name', $_POST['first_name']);
    $updateReportStmt->bindParam(':middle_name', $_POST['middle_name']);
    $updateReportStmt->bindParam(':last_name', $_POST['last_name']);
    $updateReportStmt->bindParam(':dob', $_POST['dob']);
    $updateReportStmt->bindParam(':address', $_POST['address']);
    $updateReportStmt->bindParam(':confiscated', $_POST['confiscated']);
    $updateReportStmt->bindParam(':vehicle_type', $_POST['vehicle_type']);
    $updateReportStmt->bindParam(':vehicle_owner', $_POST['vehicle_owner']);
    $updateReportStmt->bindParam(':ticket_number', $ticket_number);
    $updateReportStmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['username'], "updated details for ticket number '$ticket_number' successfully.");

    header("Location: report_details.php?ticket_number=$ticket_number");
    exit();
}
?>