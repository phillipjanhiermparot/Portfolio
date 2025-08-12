<?php
// Include the database connection file
include 'connection.php';

// Check if selected_tickets is set
if (isset($_POST['selected_tickets'])) {
    $selectedTickets = $_POST['selected_tickets'];
    $ticketArray = explode('|', $selectedTickets); // Convert comma-separated string to array

    if (count($ticketArray) > 0) {
        // Prepare the SQL query to fetch data for the selected tickets
        $placeholders = implode(',', array_fill(0, count($ticketArray), '?')); // Create placeholders
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
                r.created_at
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
            WHERE r.ticket_number IN ($placeholders)
            ORDER BY r.ticket_number ASC
        ";


        $stmt = $conn->prepare($sql);

        // Bind each ticket number to the prepared statement
        for ($i = 1; $i <= count($ticketArray); $i++) {
            $stmt->bindValue($i, $ticketArray[$i - 1]);
        }
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($reports) > 0) {
            // Create the Excel file (using CSV for simplicity - you can use a library for more complex formatting)
            $filename = "Violation_Reports_" . date('Y-m-d') . ".csv";
            header("Content-Type: application/csv");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            $output = fopen("php://output", "w");

            // Write the header row
            fputcsv($output, array('Ticket Number', 'Violation Date', 'First Name', 'Last Name', 'Status', 'Violation Level', 'Violations', 'Created At'));

            // Write the data rows
            foreach ($reports as $report) {
                fputcsv($output, array(
                    $report['ticket_number'],
                    $report['violation_date'],
                    $report['first_name'],
                    $report['last_name'],
                    $report['status'],
                    $report['violation_level'],
                    $report['violations'],
                    $report['created_at']
                ));
            }

            fclose($output);
            exit; // Important:  Stop further execution to prevent HTML from being appended
        } else {
            echo "No records found for the selected tickets.";
        }
    } else {
        echo "No tickets selected for export.";
    }
} else {
    echo "No data received for export.";
}
?>
