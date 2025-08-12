<?php
// Start session
session_start();

// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Get the ticket number from the URL
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : '';

// Fetch the violation record for the given ticket number
$sql = "SELECT 'First Offense' AS violation_type, first_violation, first_total, notes FROM violation WHERE ticket_number = ?
         UNION ALL
         SELECT 'Second Offense' AS violation_type, second_violation, second_total, notes FROM 2_violation WHERE ticket_number = ?
         UNION ALL
         SELECT 'Third Offense' AS violation_type, third_violation, third_total, notes FROM 3_violation WHERE ticket_number = ?
         UNION ALL
         SELECT 'Multiple Offense' AS violation_type, mv AS first_violation, mt AS first_total, notes FROM m_violation WHERE ticket_number = ?
         ORDER BY FIELD(violation_type, 'First Offense', 'Second Offense', 'Third Offense', 'Multiple Offense')"; // Order the results by violation type

$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $ticket_number, PDO::PARAM_INT);
$stmt->bindParam(2, $ticket_number, PDO::PARAM_INT);
$stmt->bindParam(3, $ticket_number, PDO::PARAM_INT);
$stmt->bindParam(4, $ticket_number, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all rows as an associative array

// Fetch officer information (combine lastname and firstname from hh_login table)
$sql_officer = "SELECT CONCAT(lastname, ', ', firstname) AS officer_name FROM hh_login LIMIT 1";
$stmt_officer = $conn->prepare($sql_officer);
$stmt_officer->execute();
$officer_result = $stmt_officer->fetchAll(PDO::FETCH_ASSOC);
$officer = $officer_result[0] ?? null;
$officer_name = $officer['officer_name'] ?? '';

// Fetch violator's information (name, license number, plate number, street, city/municipality, confiscated, violation_date, violation_time, vehicle_type, signature from report table)
$sql_violator = "SELECT CONCAT(last_name, ', ', first_name) AS violator_name, license, plate_number, street, city, confiscated, violation_date, violation_time, vehicle_type, signature FROM report WHERE ticket_number = ?";
$stmt_violator = $conn->prepare($sql_violator);
$stmt_violator->bindParam(1, $ticket_number, PDO::PARAM_INT);
$stmt_violator->execute();
$violator_result = $stmt_violator->fetchAll(PDO::FETCH_ASSOC);
$violator = $violator_result[0] ?? null;
$violator_name = $violator['violator_name'] ?? '';
$license_number = $violator['license'] ?? '';
$plate_number = $violator['plate_number'] ?? '';
$street = $violator['street'] ?? '';
$city = $violator['city'] ?? '';
$confiscated = strtolower($violator['confiscated'] ?? 'no');
$violation_date = $violator['violation_date'] ?? '';
$violation_time = $violator['violation_time'] ?? '';
$vehicle_type = $violator['vehicle_type'] ?? '';
$violator_signature_data = $violator['signature'] ?? null;

$license_status = ($confiscated == 'yes') ? 'Confiscated' : 'Not Confiscated';

// Fetch officer signature based on where the ticket number is found
$officer_signature_data = null;
$sql_officer_signature = "SELECT o_signature FROM violation WHERE ticket_number = ?";
$stmt_officer_signature = $conn->prepare($sql_officer_signature);
$stmt_officer_signature->bindParam(1, $ticket_number, PDO::PARAM_INT);
$stmt_officer_signature->execute();
$officer_signature_result = $stmt_officer_signature->fetch(PDO::FETCH_ASSOC);
if ($officer_signature_result && $officer_signature_result['o_signature']) {
    $officer_signature_data = $officer_signature_result['o_signature'];
} else {
    $sql_officer_signature = "SELECT 2o_signature FROM 2_violation WHERE ticket_number = ?";
    $stmt_officer_signature = $conn->prepare($sql_officer_signature);
    $stmt_officer_signature->bindParam(1, $ticket_number, PDO::PARAM_INT);
    $stmt_officer_signature->execute();
    $officer_signature_result = $stmt_officer_signature->fetch(PDO::FETCH_ASSOC);
    if ($officer_signature_result && $officer_signature_result['2o_signature']) {
        $officer_signature_data = $officer_signature_result['2o_signature'];
    } else {
        $sql_officer_signature = "SELECT 3o_signature FROM 3_violation WHERE ticket_number = ?";
        $stmt_officer_signature = $conn->prepare($sql_officer_signature);
        $stmt_officer_signature->bindParam(1, $ticket_number, PDO::PARAM_INT);
        $stmt_officer_signature->execute();
        $officer_signature_result = $stmt_officer_signature->fetch(PDO::FETCH_ASSOC);
        if ($officer_signature_result && $officer_signature_result['3o_signature']) {
            $officer_signature_data = $officer_signature_result['3o_signature'];
        } else {
            $sql_officer_signature = "SELECT mo_signature FROM m_violation WHERE ticket_number = ?";
            $stmt_officer_signature = $conn->prepare($sql_officer_signature);
            $stmt_officer_signature->bindParam(1, $ticket_number, PDO::PARAM_INT);
            $stmt_officer_signature->execute();
            $officer_signature_result = $stmt_officer_signature->fetch(PDO::FETCH_ASSOC);
            if ($officer_signature_result && $officer_signature_result['mo_signature']) {
                $officer_signature_data = $officer_signature_result['mo_signature'];
            }
        }
    }
}

// Check if ticket_number and license exist in m_violation
$sql_check_m_violation = "SELECT COUNT(*) FROM m_violation WHERE ticket_number = ? AND mv IS NOT NULL AND mt IS NOT NULL";
$stmt_check_m_violation = $conn->prepare($sql_check_m_violation);
$stmt_check_m_violation->bindParam(1, $ticket_number, PDO::PARAM_INT);
$stmt_check_m_violation->execute();
$m_violation_exists = $stmt_check_m_violation->fetchColumn() > 0;

// Function to convert BLOB to base64
function blobToBase64($blob) {
    if ($blob === null || empty($blob)) {
        return null;
    }
    return base64_encode($blob);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Violation Receipt</title>
    <link rel="stylesheet" href="./css/style1.css?v=3.2">
    <link rel="icon" href="/images/poso.png" type="image/png">
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
            width: 120px;    /* Set a specific width */
            height: 35px;    /* Set a specific height */
            padding: 0;      /* Remove padding */
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
         .signature-container {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            align-items: flex-end; /* Align signatures to the bottom */
        }
        .signature {
            text-align: center;
        }
        .signature img {
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

@media print {
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

     .signature-container {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            align-items: flex-end; /* Align signatures to the bottom */
        }
        .signature {
            text-align: center;
        }
        .signature img {
            max-width: 130px;
            height: auto;
            margin-bottom: 5px;
        }
        .signature-label {
            font-size: 0.8em;
            font-style: italic;
        }
}
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (count($result) > 0) : ?>
            <div class="ticket-container">
                <div class="header-container d-flex justify-content-between align-items-center">
                    <img src="/images/left.png" alt="Left Logo" class="logo">
                    <div class="col text-center">
                        <p class="title">Traffic Violations</p>
                        <p class="city">-City of Binan, Laguna-</p>
                    </div>
                    <img src="/images/arman.png" alt="Right Logo" class="logo">
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
        $has_others = false; // Flag to check if 'OTHERS' has a value

        foreach ($result as $violation) {
            $violation_details = htmlspecialchars($violation['first_violation'] ?? $violation['second_violation'] ?? $violation['third_violation']);
            $violation_amount = htmlspecialchars($violation['first_total'] ?? $violation['second_total'] ?? $violation['third_total']);
            $formatted_violation_details = str_replace(", ", "<br>", $violation_details);

            if (stripos($violation_details, 'IMPOUNDED') !== false) {
                $impoundFound = true;
            }

            if ($violation_amount >= 1) {
                $sql_discount = "SELECT * FROM discount WHERE ticket_number = ? AND license = ?";
                $stmt_discount = $conn->prepare($sql_discount);
                $stmt_discount->bindParam(1, $ticket_number, PDO::PARAM_INT);
                $stmt_discount->bindParam(2, $license_number, PDO::PARAM_STR);
                $stmt_discount->execute();
                $discount_result = $stmt_discount->fetchAll(PDO::FETCH_ASSOC);
                $discount = $discount_result[0] ?? null;

                if ($discount) {
                    $discount_applied = false;
                    foreach ($violation_columns as $col => $violation_name) {
                        if (isset($discount[$col]) && $discount[$col] !== null) {
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
                    // Check if OTHERS and OTHERS_P has value.
                    if (isset($discount['OTHERS']) && $discount['OTHERS'] !== null && isset($discount['OTHERS_P']) && $discount['OTHERS_P'] > 0) {
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

        if (count($result) == 0) {
            echo "<tr><td colspan='3'>No violations found.</td></tr>";
        }

        // Check for Multiple/Third Violation
        foreach ($result as $violation) {
            if ($violation['violation_type'] == 'Third Offense' || $violation['violation_type'] == 'Multiple Offense') {
                echo "<tr><td colspan='3' class='license-confiscated-warning'>THIS VIOLATOR IS NOW SUBJECT TO VEHICLE IMPOUND AND LICENSE CONFISCATION FOR HAVING MULTIPLE VIOLATION RECORD. PLEASE COORDINATE WITH POSO BIÑAN FOR FURTHER DETAILS.</td></tr>";
                break; // No need to check other rows
            }
        }

        // Check if License is Confiscated and if not due to multiple violation in m_violation
        if ($confiscated == 'yes' && !$m_violation_exists) {
            echo "<tr><td colspan='3' class='license-confiscated-warning'>THIS VIOLATOR'S LICENSE HAS BEEN CONFISCATED.</td></tr>";
        }
        ?>
    </tbody>
                </table>
<br>
<div class="gray">
<h3>SIGNATORY</h3>
</div>
<div class="signature-container">

    <?php
    // Function to generate the HTML for a signature box
    function generateSignatureBox($signature_data_uri, $name, $label) {
        $html = '<div class="signature">';
        if (!empty($signature_data_uri)) {
            $html .= '<img src="data:image/png;base64,' . htmlspecialchars(blobToBase64($signature_data_uri)) . '" alt="' . htmlspecialchars($label) . '\'s Signature" class="signature-image">';
        } else {
            $html .= '<div style="height: auto; width: 130px; border-bottom: 1px dashed #ccc; margin-bottom: 5px;"></div>';
        }
        $html .= '<p>' . htmlspecialchars($name) . '</p>';
        $html .= '<p class="signature-label"><i>' . htmlspecialchars($label) . '</i></p>'; // Added italic style
        $html .= '</div>';
        return $html;
    }
    // Generate Officer Signature Box
    echo generateSignatureBox($officer_signature_data, $officer_name, "Officer");

    // Generate Violator Signature Box
    echo generateSignatureBox($violator_signature_data, $violator_name, "Violator");
    ?>
</div>
<br>
<h4>NOTES:</h4>
<ul>
<?php
// Reset the result pointer to fetch notes
foreach ($result as $violation) {
echo "<li>" . htmlspecialchars($violation['notes']) . "</li>";
}
?>
</ul>
<p class="compliance-message">PLEASE PROCEED TO OFFICE OF THE CITY TREASURER. THANK YOU FOR YOUR COMPLIANCE.</p>

                <div class="button-container">
                    <button id="printButton" onclick="printReceipt()">Print</button>
                    <button id="previousButton" class="btn btn-secondary" onclick="goToPreviousPage()">Back</button>
                </div>
            </div>
        <?php else : ?>
            <p>No violation records found for this individual.</p>
        <?php endif; ?>
</div>

<script>
function printReceipt() {
try {
document.querySelector('.button-container').style.display = 'none';

                    if (typeof InnerPrinter !== "undefined" && InnerPrinter.print) {
                        const receiptContent = document.querySelector('.container').innerHTML;

                        const formattedContent = `
                            <html>
                                <head>
                                    <title>Receipt</title>
                                </head>
                                <body>
                                    ${receiptContent}
                                </body>
                            </html>
                        `;

                        InnerPrinter.print(formattedContent, function (success) {
                            if (success) {
                                alert("Printed successfully!");
                            } else {
                                alert("Failed to print. Please try again.");
                            }
                        });
                    } else {
                        window.print();
                    }
} catch (error) {
                    console.error("Printing error: ", error);
                    alert("Printing failed. Check your printer connection.");
} finally {
                    document.querySelector('.button-container').style.display = 'block';
}
}
function goToPreviousPage() {
    window.history.back();
}
</script>
</body>
</html>
