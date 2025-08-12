<?php
// Start the session
session_start();

// Database connection (adjust with your DB settings)
$conn = new mysqli("127.0.0.1", "root", "", "poso");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch tickets from the 'report' table
$sql = "SELECT ticket_number, first_name, last_name, license, created_at FROM report ORDER BY created_at DESC";
$result = $conn->query($sql);

$currentTime = time();
$cutoffTime = 24 * 60 * 60; // 24 hours in seconds
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket History</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=1.0">
    <style>
        .ticket-history-container {
            width: 95%;
            /* Adjust width as needed */
            margin: auto;
            margin-top: 30px;
        }

        table {
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .btn-container a {
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .btn-container a:hover {
            background-color: #0056b3;
        }

        .print-link {
            background-color: #007bff;
            /* Green color for print */
            color: #fff;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            /* Remove underline from link */
        }

        .print-link:hover {
            background-color: #0056b3;
        }

        .disabled-link {
            background-color: #6c757d; /* Greyed out color */
            cursor: not-allowed;
        }

        .disabled-link:hover {
            background-color: #6c757d;
        }

        .masked {
            font-size: 0.8em; /* Make the dots smaller */
            color: black;
            letter-spacing: 0.1em; /* Adjust spacing if needed */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="ticket-history-container">
            <h3 class="text-center">Ticket History</h3>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Ticket Number</th>
                        <th>Name</th>
                        <th>License</th>
                        <th>Created By</th>
                        <th>Date and Time Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Check if there are any tickets
                    if ($result->num_rows > 0) {
                        // Output data of each row
                        while ($row = $result->fetch_assoc()) {
                            $ticketNumber = $row['ticket_number'];
                            $name = $row['first_name'] . " " . $row['last_name'];
                            $license = $row['license'];
                            $createdBy = "Unknown"; // Default value
                            $createdAtTimestamp = strtotime($row['created_at']);
                            $isOlderThan24Hours = ($currentTime - $createdAtTimestamp) > $cutoffTime;

                            // Check violation tables for creator
                            $violationTables = ['violation', '2_violation', '3_violation'];
                            $officerFields = ['o_firstname', 'o_lastname', '2o_firstname', '2o_lastname', '3o_firstname', '3o_lastname'];

                            foreach ($violationTables as $index => $table) {
                                $sqlOfficer = "SELECT " . $officerFields[$index * 2] . ", " . $officerFields[$index * 2 + 1] . " FROM $table WHERE ticket_number = '$ticketNumber'";
                                $officerResult = $conn->query($sqlOfficer);

                                if ($officerResult && $officerResult->num_rows > 0) {
                                    $officerRow = $officerResult->fetch_assoc();
                                    $createdBy = $officerRow[$officerFields[$index * 2]] . " " . $officerRow[$officerFields[$index * 2 + 1]];
                                    break; // Found the creator, no need to check other tables
                                }
                            }

                            echo "<tr>
                                    <td>{$ticketNumber}</td>
                                    <td>" . ($isOlderThan24Hours ? '<span class="masked">' . str_repeat('•', strlen($name)) . '</span>' : htmlspecialchars($name)) . "</td>
                                    <td>" . ($isOlderThan24Hours ? '<span class="masked">' . str_repeat('•', strlen($license)) . '</span>' : htmlspecialchars($license)) . "</td>
                                    <td>{$createdBy}</td>
                                    <td>" . date('Y-m-d H:i:s', $createdAtTimestamp) . "</td>
                                    <td>";
                            if ($isOlderThan24Hours) {
                                echo "<button class='print-link disabled-link' disabled>Print</button>";
                            } else {
                                echo "<a href='/admin/vb.php?ticket_number={$ticketNumber}' class='print-link'>Print</a>";
                            }
                            echo "</td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No tickets found.</td></tr>";
                    }

                    // Close the database connection
                    $conn->close();
                    ?>
                </tbody>
            </table>

            <div class="btn-container">
                <a href="menu.php">Back to Main Menu</a>
            </div>
        </div>
    </div>
</body>

</html>