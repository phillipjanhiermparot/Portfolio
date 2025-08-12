<?php
// Start the session
session_start();

// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Collect form data
$ticket_number = $_POST['ticket_number']; // Get the ticket number from the form
$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$dob = $_POST['dob'];
$address = $_POST['address'];
$license = $_POST['license'];
$confiscated = isset($_POST['confiscated']) ? $_POST['confiscated'] : 'no';
$violation_date = $_POST['date'];
$violation_time = $_POST['time'];
$street = $_POST['street'];
$plate_number = $_POST['plate_number'];
$city = $_POST['city'];
$registration = $_POST['registration'];
$vehicle_type = $_POST['vehicle_type'];
$vehicle_owner = $_POST['vehicle_owner'];
$v_status = $_POST['v_status'];

// Check if a record with the given ticket_number already exists
$check_sql = "SELECT ticket_number FROM report WHERE ticket_number = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $ticket_number);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // If a record exists, prepare and execute the UPDATE statement
    $sql = "UPDATE report SET
            first_name = ?,
            middle_name = ?,
            last_name = ?,
            dob = ?,
            address = ?,
            license = ?,
            confiscated = ?,
            violation_date = ?,
            violation_time = ?,
            street = ?,
            plate_number = ?,
            city = ?,
            registration = ?,
            vehicle_type = ?,
            vehicle_owner = ?,
            v_status = ?
            WHERE ticket_number = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssssssssss",
        $first_name,
        $middle_name,
        $last_name,
        $dob,
        $address,
        $license,
        $confiscated,
        $violation_date,
        $violation_time,
        $street,
        $plate_number,
        $city,
        $registration,
        $vehicle_type,
        $vehicle_owner,
        $v_status,
        $ticket_number
    );

    if ($stmt->execute()) {
        // Redirect to violation.php with the ticket number, first name, and last name
        header("Location: violation.php?ticket_number=" . urlencode($ticket_number) . "&first_name=" . urlencode($first_name) . "&last_name=" . urlencode($last_name));
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }
} else {
    // If no record exists, prepare and execute the INSERT statement
    $sql = "INSERT INTO report (ticket_number, first_name, middle_name, last_name, dob, address, license, confiscated, violation_date, violation_time, street, plate_number, city, registration, vehicle_type, vehicle_owner, v_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssssssssss",
        $ticket_number,
        $first_name,
        $middle_name,
        $last_name,
        $dob,
        $address,
        $license,
        $confiscated,
        $violation_date,
        $violation_time,
        $street,
        $plate_number,
        $city,
        $registration,
        $vehicle_type,
        $vehicle_owner,
        $v_status
    );

    if ($stmt->execute()) {
        // Redirect to violation.php with the ticket number, first name, and last name
        header("Location: violation.php?ticket_number=" . urlencode($ticket_number) . "&first_name=" . urlencode($first_name) . "&last_name=" . urlencode($last_name));
        exit();
    } else {
        echo "Error inserting record: " . $stmt->error;
    }
}

// Close the statements and connection
$check_stmt->close();
$stmt->close();
$conn->close();
?>