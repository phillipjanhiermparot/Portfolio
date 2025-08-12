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

// Get user information from the session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Assuming username is stored in the session

// Get the ticket number from the URL or POST data
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : (isset($_POST['ticket_number']) ? $_POST['ticket_number'] : '');

// Check if the ticket_number exists in the report table
if (empty($ticket_number)) {
    echo "Ticket number is missing!";
    exit();
}

$stmt_check = $conn->prepare("SELECT COUNT(*) FROM report WHERE ticket_number = :ticket_number");
$stmt_check->bindParam(':ticket_number', $ticket_number);
$stmt_check->execute();
$result = $stmt_check->fetchColumn();

if ($result == 0) {
    echo "Ticket number does not exist in the report table!";
    exit();
}

// Fetch the original report data before update
$stmt_original = $conn->prepare("SELECT first_name, middle_name, last_name, dob, address, license, violation_date, violation_time, confiscated, vehicle_owner, street, city, vehicle_type, plate_number, registration, v_status FROM report WHERE ticket_number = :ticket_number");
$stmt_original->bindParam(':ticket_number', $ticket_number);
$stmt_original->execute();
$original_report = $stmt_original->fetch(PDO::FETCH_ASSOC);

// Collect form data
$first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
$middle_name = isset($_POST['middle_name']) ? $_POST['middle_name'] : '';
$last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
$dob = isset($_POST['dob']) ? $_POST['dob'] : '';
$address = isset($_POST['address']) ? $_POST['address'] : '';
$license = isset($_POST['license']) ? $_POST['license'] : '';
$violation_date = isset($_POST['violation_date']) ? $_POST['violation_date'] : '';
$violation_time = isset($_POST['violation_time']) ? $_POST['violation_time'] : '';
$confiscated = isset($_POST['confiscated']) ? $_POST['confiscated'] : $original_report['confiscated']; // Get directly from POST
$vehicle_owner = isset($_POST['vehicle_owner']) ? $_POST['vehicle_owner'] : '';
$street = isset($_POST['street']) ? $_POST['street'] : '';
$city = isset($_POST['city']) ? $_POST['city'] : '';
$vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : '';
$plate_number = isset($_POST['plate_number']) ? $_POST['plate_number'] : '';
$registration = isset($_POST['registration']) ? $_POST['registration'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$amount = isset($_POST['amount']) ? $_POST['amount'] : '';
$officer_name = isset($_POST['officer_name']) ? $_POST['officer_name'] : '';
$receipt_num = isset($_POST['receipt_num']) ? $_POST['receipt_num'] : '';

// Collect other violation data.
$others_violation_text = isset($_POST['others_violation_text']) ? $_POST['others_violation_text'] : null;
$others_violation_amount = isset($_POST['others_violation_amount']) ? $_POST['others_violation_amount'] : null;

// Prepare the update query for the report table
$stmt_report = $conn->prepare("UPDATE report SET
    first_name = :first_name,
    middle_name = :middle_name,
    last_name = :last_name,
    dob = :dob,
    address = :address,
    license = :license,
    violation_date = :violation_date,
    violation_time = :violation_time,
    confiscated = :confiscated,
    vehicle_owner = :vehicle_owner,
    street = :street,
    city = :city,
    vehicle_type = :vehicle_type,
    plate_number = :plate_number,
    registration = :registration,
    v_status = :status
WHERE ticket_number = :ticket_number");

$stmt_report->bindParam(':first_name', $first_name);
$stmt_report->bindParam(':middle_name', $middle_name);
$stmt_report->bindParam(':last_name', $last_name);
$stmt_report->bindParam(':dob', $dob);
$stmt_report->bindParam(':address', $address);
$stmt_report->bindParam(':license', $license);
$stmt_report->bindParam(':violation_date', $violation_date);
$stmt_report->bindParam(':violation_time', $violation_time);
$stmt_report->bindParam(':confiscated', $confiscated);
$stmt_report->bindParam(':vehicle_owner', $vehicle_owner);
$stmt_report->bindParam(':street', $street);
$stmt_report->bindParam(':city', $city);
$stmt_report->bindParam(':vehicle_type', $vehicle_type);
$stmt_report->bindParam(':plate_number', $plate_number);
$stmt_report->bindParam(':registration', $registration);
$stmt_report->bindParam(':status', $status); // Update v_status in report table
$stmt_report->bindParam(':ticket_number', $ticket_number);

try {
    $stmt_report->execute();

    // Log individual field updates in the report table
    if ($original_report['first_name'] !== $first_name) {
        $activity = "$username updated ticket number $ticket_number First Name from '" . $original_report['first_name'] . "' to '" . $first_name . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['middle_name'] !== $middle_name) {
        $activity = "$username updated ticket number $ticket_number Middle Name from '" . $original_report['middle_name'] . "' to '" . $middle_name . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['last_name'] !== $last_name) {
        $activity = "$username updated ticket number $ticket_number Last Name from '" . $original_report['last_name'] . "' to '" . $last_name . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['dob'] !== $dob) {
        $activity = "$username updated ticket number $ticket_number Date of Birth from '" . $original_report['dob'] . "' to '" . $dob . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['address'] !== $address) {
        $activity = "$username updated ticket number $ticket_number Address from '" . $original_report['address'] . "' to '" . $address . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['license'] !== $license) {
        $activity = "$username updated ticket number $ticket_number License Number from '" . $original_report['license'] . "' to '" . $license . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['violation_date'] !== $violation_date) {
        $activity = "$username updated ticket number $ticket_number Violation Date from '" . $original_report['violation_date'] . "' to '" . $violation_date . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['violation_time'] !== $violation_time) {
        $activity = "$username updated ticket number $ticket_number Violation Time from '" . $original_report['violation_time'] . "' to '" . $violation_time . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['confiscated'] !== $confiscated) {
        $activity = "$username updated ticket number $ticket_number License Confiscated from '" . $original_report['confiscated'] . "' to '" . $confiscated . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['vehicle_owner'] !== $vehicle_owner) {
        $activity = "$username updated ticket number $ticket_number Vehicle Owner from '" . $original_report['vehicle_owner'] . "' to '" . $vehicle_owner . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['street'] !== $street) {
        $activity = "$username updated ticket number $ticket_number Street from '" . $original_report['street'] . "' to '" . $street . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['city'] !== $city) {
        $activity = "$username updated ticket number $ticket_number City from '" . $original_report['city'] . "' to '" . $city . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    // Modified activity logging for vehicle_type
    if ($original_report['vehicle_type'] !== $vehicle_type) {
        $activity = "$username updated ticket number $ticket_number Vehicle Type from '" . $original_report['vehicle_type'] . "' to '" . $vehicle_type . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['plate_number'] !== $plate_number) {
        $activity = "$username updated ticket number $ticket_number Plate Number from '" . $original_report['plate_number'] . "' to '" . $plate_number . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['registration'] !== $registration) {
        $activity = "$username updated ticket number $ticket_number Registration from '" . $original_report['registration'] . "' to '" . $registration . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }
    if ($original_report['v_status'] !== $status) {
        $activity = "$username updated ticket number $ticket_number Status in report table from '" . $original_report['v_status'] . "' to '" . $status . "'.";
        logActivity($conn, $user_id, $username, $activity);
    }

} catch (PDOException $e) {
    echo "Error updating report table: " . $e->getMessage();
    exit();
}

// Fetch existing violations
$stmt_violation = $conn->prepare("SELECT * FROM discount WHERE ticket_number = :ticket_number");
$stmt_violation->bindParam(':ticket_number', $ticket_number);
$stmt_violation->execute();
$existing_violations = $stmt_violation->fetch(PDO::FETCH_ASSOC);

// Get violations from POST request
$new_violations = isset($_POST['violations']) ? $_POST['violations'] : [];

// Define available violations with respective column names and penalties
$violation_map =  [
    'FAILURE TO WEAR HELMET' => ['column' => 'FTWH', 'penalty' => 200],
    'OPEN MUFFLER/NUISANCE' => ['column' => 'OMN', 'penalty' => 1000],
    'ARROGANT' => ['column' => 'ARG', 'penalty' => 1000],
    'ONEWAY' => ['column' => 'ONEWAY', 'penalty' => 200],
    'ILLEGAL PARKING' => ['column' => 'ILP', 'penalty' => 200],
    'DRIVING WITHOUT LICENSE/INVALID LICENSE' => ['column' => 'DWL', 'penalty' => 1000],
    'NO OR/CR WHILE DRIVING' => ['column' => 'NORCR', 'penalty' => 500],
    'DRIVING UNREGISTERED VEHICLE' => ['column' => 'DUV', 'penalty' => 500],
    'UNREGISTERED MOTOR VEHICLE' => ['column' => 'UMV', 'penalty' => 500],
    'OBSTRUCTION' => ['column' => 'OBS', 'penalty' => 200],
    'DISREGARDING TRAFFIC SIGNS' => ['column' => 'DTS', 'penalty' => 200],
    'DISREGARDING TRAFFIC OFFICER' => ['column' => 'DTO', 'penalty' => 200],
    'TRUCK BAN' => ['column' => 'TRB', 'penalty' => 200],
    'STALLED VEHICLE' => ['column' => 'STV', 'penalty' => 200],
    'RECKLESS DRIVING' => ['column' => 'RCD', 'penalty' => 100],
    'DRIVING UNDER THE INFLUENCE OF LIQUOR' => ['column' => 'DUL', 'penalty' => 200],
    'INVALID OR NO FRANCHISE/COLORUM' => ['column' => 'INF', 'penalty' => 2000],
    'OPERATING OUT OF LINE' => ['column' => 'OOL', 'penalty' => 2000],
    'TRIP - CUTTING' => ['column' => 'TCT', 'penalty' => 200],
    'OVERLOADING' => ['column' => 'OVL', 'penalty' => 200],
    'LOADING/UNLOADING IN PROHIBITED ZONE' => ['column' => 'LUZ', 'penalty' => 200],
    'INVOLVE IN ACCIDENT' => ['column' => 'IVA', 'penalty' => 200],
    'SMOKE BELCHING' => ['column' => 'SMB', 'penalty' => 500],
    'NO SIDE MIRROR' => ['column' => 'NSM', 'penalty' => 200],
    'JAY WALKING' => ['column' => 'JWK', 'penalty' => 200],
    'WEARING SLIPPERS/SHORTS/SANDO' => ['column' => 'WSS', 'penalty' => 300],
    'ILLEGAL VENDING' => ['column' => 'ILV', 'penalty' => 200],
    'IMPOUNDED' => ['column' => 'IMP', 'penalty' => 800]
];

function logActivity($conn, $userId, $username, $activity) {
    $logQuery = "INSERT INTO profile_activity_log (user_id, username, activity, timestamp) VALUES (?, ?, ?, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(1, $userId, PDO::PARAM_INT);
    $logStmt->bindParam(2, $username);
    $logStmt->bindParam(3, $activity);
    $logStmt->execute();
    $logStmt->closeCursor();
}

?>

<script>
    if (confirm("Are you sure you want to update the details for Ticket Number <?php echo $ticket_number; ?>? Any incorrect or unjustified changes—especially those made without proper documentation or proof—may result in serious consequences.")) {
        var formData = new FormData();
        formData.append('ticket_number', '<?php echo $ticket_number; ?>');
        formData.append('status', '<?php echo $status; ?>');
        formData.append('receipt_num', '<?php echo $receipt_num; ?>');
        formData.append('others_violation_text', '<?php echo $others_violation_text; ?>');
        formData.append('others_violation_amount', '<?php echo $others_violation_amount; ?>');
        formData.append('violations', JSON.stringify(<?php echo json_encode($new_violations); ?>));

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_discount_ajax.php', true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                // Log the status update after successful AJAX call for discount table
                var status = '<?php echo $status; ?>';
                var logActivityDiscount = "<?php echo $username; ?> updated ticket number <?php echo $ticket_number; ?> STATUS in discount table to " + status + ".";
                var logXhrDiscount = new XMLHttpRequest();
                logXhrDiscount.open('POST', 'log_activity.php', true);
                logXhrDiscount.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                logXhrDiscount.send('user_id=<?php echo $user_id; ?>&username=<?php echo $username; ?>&activity=' + encodeURIComponent(logActivityDiscount));

                window.location.href = "report.php";
            } else {
                alert('An error occurred during the update.');
                window.location.href = "sm.php?ticket_number=<?php echo $ticket_number; ?>";
            }
        };
        xhr.onerror = function() {
            alert('An error occurred during the update.');
            window.location.href = "sm.php?ticket_number=<?php echo $ticket_number; ?>";
        };
        xhr.send(formData);
    } else {
        window.location.href = "sm.php?ticket_number=<?php echo $ticket_number; ?>";
    }
</script>
