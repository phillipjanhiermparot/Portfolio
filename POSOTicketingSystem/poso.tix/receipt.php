<?php
// Start session
session_start();

// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Get the ticket number from the session
$ticket_number = $_GET['ticket_number']; // Get from URL

// Initialize variables
$officer_name = "";
$officer_signature_blob = null;
$violation_data = null;

// Fetch violation records and officer data with a single query
$sql = "SELECT
            'First Offense' AS violation_type,
            first_violation,
            first_total,
            notes,
            o_signature,
            o_firstname,
            o_lastname,
            NULL AS second_officer_name,
            NULL AS third_officer_name,
            NULL AS multiple_officer_name
        FROM violation
        WHERE ticket_number = ?
        UNION ALL
        SELECT
            'Second Offense' AS violation_type,
            second_violation,
            second_total,
            notes,
            2o_signature,
            NULL,
            NULL,
            CONCAT(2o_firstname, ' ', 2o_lastname),
            NULL,
            NULL
        FROM 2_violation
        WHERE ticket_number = ?
        UNION ALL
        SELECT
            'Third Offense' AS violation_type,
            third_violation,
            third_total,
            notes,
            3o_signature,
            NULL,
            NULL,
            NULL,
            CONCAT(3o_firstname, ' ', 3o_lastname),
            NULL
        FROM 3_violation
        WHERE ticket_number = ?
        UNION ALL
        SELECT
            'Multiple Offense' AS violation_type,
            mv AS first_violation,
            mt AS first_total,
            notes,
            mo_signature,
            NULL,
            NULL,
            NULL,
            NULL,
            CONCAT(mo_firstname, ' ', mo_lastname)
        FROM m_violation
        WHERE ticket_number = ?
        ORDER BY violation_type ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $ticket_number, $ticket_number, $ticket_number, $ticket_number);
$stmt->execute();
$result = $stmt->get_result();

// Fetch violator's information
$sql_violator = "SELECT CONCAT(last_name, ', ', first_name) AS violator_name, license, plate_number, vehicle_type, street, city, confiscated, violation_date, violation_time, signature FROM report WHERE ticket_number = ?";
$stmt_violator = $conn->prepare($sql_violator);
$stmt_violator->bind_param("i", $ticket_number);
$stmt_violator->execute();
$violator_result = $stmt_violator->get_result();
$violator = $violator_result->fetch_assoc();
$violator_name = $violator['violator_name'];
$license_number = $violator['license'];
$plate_number = $violator['plate_number'];
$vehicle_type = $violator['vehicle_type'];
$street = $violator['street'];
$city = $violator['city'];
$confiscated = $violator['confiscated'];
$violation_date = $violator['violation_date'];
$violation_time = $violator['violation_time'];
$signature_blob = $violator['signature'];
$signature_data_uri = null;

if ($signature_blob) {
    $signature_data_uri = 'data:image/png;base64,' . base64_encode($signature_blob);
}

// Determine officer name and signature
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['o_firstname'] != null && $row['o_lastname'] != null)
        {
            $officer_name = $row['o_firstname'] . " " . $row['o_lastname'];
            $officer_signature_blob = $row['o_signature'];
            $violation_data = $row;
            break;
        }
        else if ($row['second_officer_name'] != null)
        {
            $officer_name = $row['second_officer_name'];
            $officer_signature_blob = $row['o_signature'];
            $violation_data = $row;
            break;
        }
        else if ($row['third_officer_name'] != null)
        {
            $officer_name = $row['third_officer_name'];
            $officer_signature_blob = $row['o_signature'];
            $violation_data = $row;
            break;
        }
        else if ($row['multiple_officer_name'] != null)
        {
            $officer_name = $row['multiple_officer_name'];
            $officer_signature_blob = $row['o_signature'];
            $violation_data = $row;
            break;
        }
    }
}

$officer_signature_data_uri = null;
if ($officer_signature_blob) {
    $officer_signature_data_uri = 'data:image/png;base64,' . base64_encode($officer_signature_blob);
}

$license_status = ($confiscated == 'yes') ? 'Confiscated' : 'Not Confiscated';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Violation Receipt</title>
    <link rel="stylesheet" href="/POSO/admin/css/style1.css">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <style>
        .impound-warning, .license-confiscated-warning {
            color: red;
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 10px;
        }
        .compliance-message {
            text-align: center;
            font-weight: regular;
            margin-top: 4px;
            font-size: 1.2em;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        button {
            width: 120px;       /* Set a specific width */
            height: 35px;       /* Set a specific height */
            padding: 0;         /* Remove padding */
            font-size: 14px;      /* Smaller font size */
            margin: 5px;        /* Reduced margin */
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .signatory-container {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            align-items: flex-end; /* Align signatures to the bottom */
        }
        .signature-box {
            text-align: center;
        }
        .signature-image {
            max-width: 130px;
            height: auto;
            margin-bottom: 5px;
        }
        .signature-label {
            font-size: 0.8em;
            font-style: italic;
        }
@media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .button-container {
                display: none; /* Hide buttons during printing */
            }

            .container {
                width: 100%; /* Expand to full width for print */
            }

            .ticket-container {
                margin: 0;
                border: none; /* Adjust for clean edges in print */
            }

    body {
        font-size: 10pt;
        font-family: sans-serif;
        margin: auto; /* Reset body margins */
        padding: auto;
    }

    .container {
        width: auto ; /* Or 21cm for A4 */
        margin: auto; /* Center on the page */
    }

    .button-container {
        display: none;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 5px;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        height: 50px; /* Adjust as needed */
    }

    .ticket-number {
        font-size: 1.2em;
        font-weight: bold;
    }

    /* Prevent page breaks within table rows */
    tr {
        page-break-inside: avoid;
    }

    /* Force page break before the notes section */
    h4 {
        page-break-before: auto; /* Or 'always' if needed */
    }
    .signatory-container {
        display: flex;
        justify-content: space-around;
        margin-top: 20px;
        align-items: flex-end;
    }
    .signature-box {
        text-align: center;
    }
    .signature-image {
        max-width: 100px;
        height: auto;
        margin-bottom: 3px;
    }
    .signature-label {
        font-size: 0.7em;
        font-style: italic;
    }
}

        /* Hide print button on mobile during printing */
        @media print and (max-width: 768px) {
            .button-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($result->num_rows > 0) : ?>
            <div class="ticket-container">
                <div class="header-container d-flex justify-content-between align-items-center">
                    <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
                    <div class="col text-center">
                        <p class="title">Traffic Violations</p>
                        <p class="city">-City of Binan, Laguna-</p>
                    </div>
                    <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
                </div>

                <div class="ticket-info">
                    <p class="ticket-label">Ordinance Infraction Ticket</p>
                    <p class="ticket-number">No. <?php echo htmlspecialchars($ticket_number); ?></p>
                </div>
<div class="gray">
    <h3>Officer Information</h3>
</div>
<p>Name: <?php echo htmlspecialchars($officer_name); ?></p>
<p>Street: <?php echo htmlspecialchars($street); ?></p>
<p>City/Municipality: <?php echo htmlspecialchars($city); ?></p>

                <div class="gray">
                    <h3>Violator Information</h3>
                </div>

                <p>Name: <?php echo htmlspecialchars($violator_name); ?></p>
                <p>License Number: <?php echo htmlspecialchars($license_number); ?></p>
                <p>Plate Number: <?php echo htmlspecialchars($plate_number); ?></p>
                <p>Vehicle Type: <?php echo htmlspecialchars($vehicle_type); ?></p>
                <p>License Status: <?php echo htmlspecialchars($license_status); ?></p>
                <p>Date of Violation: <?php echo htmlspecialchars($violation_date); ?></p>
                <p>Time of Violation: <?php echo htmlspecialchars($violation_time); ?></p>

                <div class="gray">
                    <h3>BREAKDOWN OF VIOLATION CHARGES</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>OFFENSE LEVEL</th>
                            <th>VIOLATION</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
        <?php
        $impoundFound = false;
        $violation_columns = [
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
            'IMP' => 'IMPOUNDED'
        ];
        $totalAmount = 0;
        $has_others = false; // Flag to check if OTHERS column should be displayed
        $result->data_seek(0);
        while ($violation = $result->fetch_assoc()) {
            $violation_details = htmlspecialchars($violation['first_violation'] ?? $violation['second_violation'] ?? $violation['third_violation']);
            $violation_amount = htmlspecialchars($violation['first_total'] ?? $violation['second_total'] ?? $violation['third_total']);
            $formatted_violation_details = str_replace(", ", "<br>", $violation_details);

            if (stripos($violation_details, 'IMPOUNDED') !== false) {
                $impoundFound = true;
            }

            if ($violation_amount >= 1) {
                $sql_discount = "SELECT * FROM discount WHERE ticket_number = ? AND license = ?";
                $stmt_discount = $conn->prepare($sql_discount);
                $stmt_discount->bind_param("is", $ticket_number, $license_number);
                $stmt_discount->execute();
                $discount_result = $stmt_discount->get_result();
                $discount = $discount_result->fetch_assoc();
                $stmt_discount->close();

                if ($discount) {
                    $discount_applied = false;
                    foreach ($violation_columns as $col => $violation_name) {
                        if ($discount[$col] !== null) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($violation['violation_type']) . "</td>
                                    <td>" . htmlspecialchars($violation_name) . "</td>
                                    <td>" . htmlspecialchars($discount[$col]) . "</td>
                                </tr>";
                            $totalAmount += $discount[$col];
                            $discount_applied = true;
                        }
                    }
                    if (!$discount_applied) {
                        echo "<tr>
                                    <td>" . htmlspecialchars($violation['violation_type']) . "</td>
                                    <td>" . $formatted_violation_details . "</td>
                                    <td>" . $violation_amount . "</td>
                                </tr>";
                        $totalAmount += $violation_amount;
                    }
                    // Check if OTHERS and OTHERS_P have values
                    if ($discount['OTHERS'] !== null && !empty(trim($discount['OTHERS'])) && $discount['OTHERS_P'] > 0) {
                        echo "<tr>
                                    <td>OTHERS</td>
                                    <td>" . htmlspecialchars($discount['OTHERS']) . "</td>
                                    <td>" . htmlspecialchars($discount['OTHERS_P']) . "</td>
                                </tr>";
                        $totalAmount += $discount['OTHERS_P'];
                        $has_others = true;
                    }
                } else {
                    echo "<tr>
                                <td>" . htmlspecialchars($violation['violation_type']) . "</td>
                                <td>" . $formatted_violation_details . "</td>
                                <td>" . $violation_amount . "</td>
                            </tr>";
                    $totalAmount += $violation_amount;
                }
            }
        }

        echo "<tr><td colspan='2'>Total</td><td>" . $totalAmount . "</td></tr>";

        if ($impoundFound) {
            echo "<tr><td colspan='3' class='impound-warning'>THIS VIOLATOR IS SUBJECT TO VEHICLE IMPOUND.</td></tr>";
        }

        if ($result->num_rows == 0) {
            echo "<tr><td colspan='3'>No violations found.</td></tr>";
        }

        // Check for Multiple/Third Violation
        $result->data_seek(0); // Reset the result pointer
        while ($violation = $result->fetch_assoc()) {
            if ($violation['violation_type'] == 'Third Offense' || $violation['violation_type'] == 'Multiple Offense') {
                echo "<tr><td colspan='3' class='license-confiscated-warning'>THIS VIOLATOR IS NOW SUBJECT TO VEHICLE IMPOUND AND LICENSE CONFISCATION FOR HAVING MULTIPLE VIOLATION RECORD. PLEASE COORDINATE WITH POSO BIÑAN FOR FURTHER DETAILS.</td></tr>";
                break; // No need to check other rows
            }
        }

        // Check if License is Confiscated
        if ($confiscated == 'yes') {
            echo "<tr><td colspan='3' class='license-confiscated-warning'>THIS VIOLATOR'S LICENSE HAS BEEN CONFISCATED.</td></tr>";
        }
        ?>
                    </tbody>
                </table>
<br>
<div class="gray">
                    <h3>SIGNATORY</h3>
                </div>
                <div class="signatory-container">
                    <div class="signature-box">
                        <?php if (!empty($officer_signature_blob)): ?>
                            <img src="<?php echo htmlspecialchars($officer_signature_data_uri); ?>" alt="Officer's Signature" class="signature-image">
                        <?php else: ?>
                            <div style="height: auto; width: 130px; border-bottom: 1px dashed #ccc; margin-bottom: 5px;"></div>
                        <?php endif; ?>

                        <p><?php echo htmlspecialchars($officer_name); ?></p>
<p class="signature-label">Officer</p>
                    </div>
                    <div class="signature-box">
                        <?php if (!empty($signature_blob)): ?>
                            <img src="<?php echo htmlspecialchars($signature_data_uri); ?>" alt="Violator's Signature" class="signature-image">
                        <?php else: ?>
                            <div style="height: auto; width: 130px; border-bottom: 1px dashed #ccc; margin-bottom: 5px;"></div>
                        <?php endif; ?>

                        <p><?php echo htmlspecialchars($violator_name); ?></p>
                        <p class="signature-label">Violator</p>
                    </div>
                </div>
<br>
<h4>NOTES:</h4>
<ul>
<?php
// Reset the result pointer to fetch notes
$result->data_seek(0); // Move to the first record
while ($violation = $result->fetch_assoc()) {
echo "<li>" . htmlspecialchars($violation['notes']) . "</li>";
}
?>
</ul>
<p class="compliance-message">PLEASE PROCEED TO OFFICE OF THE CITY TREASURER. THANK YOU FOR YOUR COMPLIANCE.</p>


                <div class="button-container">
                    <button id="printButton" onclick="printReceipt()">Print</button>
                </div>
            </div>
        <?php else : ?>
            <p>No violation records found for this individual.</p>
        <?php endif; ?>
</div>

<script>
let printButtonVisible = true;

function printReceipt() {
try {
    if (printButtonVisible) {
        document.querySelector('.button-container').style.display = 'none';
        printButtonVisible = false; // Hide the button

        if (typeof InnerPrinter !== "undefined" && InnerPrinter.print) {
            const receiptContent = document.querySelector('.container').innerHTML;

            const formattedContent = `
                    <html>
                        <head>
                            <title>Receipt</title>
                            <style>
                                @media print {
                                    @page {
                                        size: auto;    /* auto is the initial value */
                                        margin: 5mm;  /* Adjust margins as needed */
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            ${receiptContent}
                            <script>
                                if (typeof InnerPrinter !== 'undefined' && InnerPrinter.setDPI) {
                                    InnerPrinter.setDPI(600);
                                }
                            
                `;

            InnerPrinter.print(formattedContent, function (success) {
                if (success) {
                    // Redirect after 5 seconds if printing is successful
                    setTimeout(function() {
                        window.location.href = "BLK.php?ticket_number=<?php echo $ticket_number; ?>";
                    }, 5000);
                } else {
                    alert("Failed to print. Please try again.");
                    document.querySelector('.button-container').style.display = 'block';
                    printButtonVisible = true; // Show the button again on failure
                }
            });
        } else {
            window.print();
            // Redirect after 5 seconds if printing is successful
            setTimeout(function() {
                window.location.href = "ticket.php";
            }, 5000);
        }
    }
} catch (error) {
    console.error("Printing error: ", error);
    alert("Printing failed. Check your printer connection.");
} finally {
    // The button will remain hidden after a successful print due to the printButtonVisible flag.
    // If printing failed, the button is shown again.
}
}

</script>
</body>
</html>
<?php
// Close connection
$stmt->close();
$stmt_violator->close();
$conn->close();
?>
