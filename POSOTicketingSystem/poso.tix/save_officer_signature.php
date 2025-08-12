<?php
// Start the session
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_number = $_POST['ticket_number'] ?? '';
    $officer_id = $_POST['officer_id'] ?? '';

    if (empty($ticket_number) || empty($officer_id)) {
        echo "Invalid data.";
        exit;
    }

    $conn = new mysqli("127.0.0.1", "root", "", "poso");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch officer details
    $stmt = $conn->prepare("SELECT firstname, lastname, signature FROM hh_login WHERE id = ?");
    $stmt->bind_param("i", $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $officerDetails = $result->fetch_assoc();
    $stmt->close();

    if (!$officerDetails) {
        echo "Officer not found.";
        $conn->close();
        exit;
    }

    // Determine which violation table contains the ticket_number
    $tables = ['violation', '2_violation', '3_violation', 'm_violation'];
    foreach ($tables as $index => $table) {
        $stmt = $conn->prepare("SELECT ticket_number FROM $table WHERE ticket_number = ?");
        $stmt->bind_param("s", $ticket_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // Determine the correct columns to update
            if ($table === 'm_violation') {
                $updateSql = "UPDATE $table SET mo_firstname = ?, mo_lastname = ?, mo_signature = ? WHERE ticket_number = ?";
            } else {
                $columnPrefix = $index === 0 ? '' : ($index === 1 ? '2' : '3');
                $updateSql = "UPDATE $table SET {$columnPrefix}o_firstname = ?, {$columnPrefix}o_lastname = ?, {$columnPrefix}o_signature = ? WHERE ticket_number = ?";
            }

            $stmtUpdate = $conn->prepare($updateSql);
            $stmtUpdate->bind_param("ssss", $officerDetails['firstname'], $officerDetails['lastname'], $officerDetails['signature'], $ticket_number);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            break;
        }
        $stmt->close();
    }

    $conn->close();
    echo "Officer signature saved successfully.";
}
?>
