<?php
// Start the session
session_start();

// Get the ticket number from the URL (if it's available)
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : 'N/A'; // Default to 'N/A' if not set

// If ticket number is provided, proceed with the database update
if ($ticket_number !== 'N/A') {
    $conn = new mysqli("127.0.0.1", "root", "", "poso");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch officer details from the hh_login table
    $sql = "SELECT firstname, lastname, signature FROM hh_login WHERE signature IS NOT NULL LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $officerDetails = $result->fetch_assoc();

        // Check and update officer's details in the corresponding violation table
        $tables = ['violation', '2_violation', '3_violation', 'm_violation'];

       foreach ($tables as $index => $table) {
        $sql = "SELECT * FROM $table WHERE ticket_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ticket_number); // Changed to "s" for string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Determine the correct column to update based on the table
            if ($table === 'm_violation') {
                // Correct column names for m_violation table
                $updateSql = "UPDATE $table SET mo_firstname = ?, mo_lastname = ?, mo_signature = ? WHERE ticket_number = ?"; // Corrected column name here
            } else {
                $columnPrefix = $index === 0 ? '' : ($index === 1 ? '2' : '3');
                $updateSql = "UPDATE $table SET {$columnPrefix}o_firstname = ?, {$columnPrefix}o_lastname = ?, {$columnPrefix}o_signature = ? WHERE ticket_number = ?";
            }
            $stmtUpdate = $conn->prepare($updateSql);

            if($table === 'm_violation'){
                $stmtUpdate->bind_param("ssss", $officerDetails['firstname'], $officerDetails['lastname'], $officerDetails['signature'], $ticket_number);
            } else {
                $stmtUpdate->bind_param("ssss", $officerDetails['firstname'], $officerDetails['lastname'], $officerDetails['signature'], $ticket_number);
            }
            $stmtUpdate->execute();
        }
    }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Signature</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <style>
        .logo {
            width: 50px;
            height: auto;
        }

        .ticket-info {
            display: flex;
            justify-content: space-between;
        }

        .ticket-label {
            font-weight: bold;
            color: #333;
        }

        .ticket-number {
            font-weight: bold;
            color: red;
        }

        .signature-section {
            margin-top: 30px;
            text-align: center;
        }

        .canvas-container {
            margin-top: 10px;
            text-align: center;
            border: 2px solid #ccc;
            padding: 20px;
            width: 300px;
            height: 150px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 200px;
        }

        button {
            margin: 10px;
            padding: 8px 15px;
            font-size: 14px;
        }

        /* Penalty Message Style */
        .penalty-message {
            font-size: 11px;
            font-weight: bold;
            color: red;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ticket-container">
            <div class="header-container">
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

            <div class="signature-section">
                <div class="gray">
                    <h3>OFFICER'S SIGNATURE</h3>
                </div>
                <br>
     <select id="officerSignature" onchange="updateSignature('officerSignatureCanvas', 'officerSignature')">
    <option value="">Select Officer</option>
    <?php
    $conn = new mysqli("127.0.0.1", "root", "", "poso");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, firstname, lastname, signature FROM hh_login WHERE signature IS NOT NULL";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $officer_id = $row['id'];
            $officer_name = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
            $signature_data = base64_encode($row['signature']);
            echo "<option value='{$officer_id}' data-signature='data:image/jpeg;base64,{$signature_data}'>{$officer_name}</option>";
        }
    } else {
        echo '<option value="">No officers found</option>';
    }

    $conn->close();
    ?>
</select>

                <div class="canvas-container" id="officerSignatureCanvas"></div>
            </div>

            <div class="signature-section">
                <div class="gray">
                    <h3>DRIVER'S SIGNATURE</h3>
                </div>
                <div class="canvas-container">
                    <canvas id="driverSignatureCanvas" width="300" height="150"></canvas>
                </div>

                <p class="penalty-message">
                    PAY THE PENALTY TO CITY/ MUNICIPAL TREASURER’S OFFICE WITHIN (3) DAYS. FAILURE TO DO SO WILL FORCE THE REFERRAL TO MUNICIPAL TRIAL COURT OF BINAN, LAGUNA FOR LEGAL ACTION.
                </p>

                <button onclick="clearSignature()">Clear</button>
                <button onclick="saveSignature()">Save</button>
                <button onclick="submitTicket()">Next</button>
            </div>
        </div>
    </div>

    <script>
        // Update Officer's Signature
        function updateSignature(canvasId, selectId) {
    const canvasContainer = document.getElementById(canvasId);
    const selectElement = document.getElementById(selectId);
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const signatureData = selectedOption.getAttribute('data-signature');

    canvasContainer.innerHTML = "";

    if (signatureData) {
        const img = document.createElement("img");
        img.src = signatureData;
        canvasContainer.appendChild(img);
    }
}


        // Canvas for Driver's Signature
        const canvas = document.getElementById('driverSignatureCanvas');
        const ctx = canvas.getContext('2d');
        let drawing = false;

        // Function to get the correct position for mouse/touch
        function getPosition(event) {
            const rect = canvas.getBoundingClientRect();
            if (event.touches) { // For touch events
                return {
                    x: event.touches[0].clientX - rect.left,
                    y: event.touches[0].clientY - rect.top
                };
            } else { // For mouse events
                return {
                    x: event.offsetX,
                    y: event.offsetY
                };
            }
        }

        // Mouse Events
        canvas.addEventListener('mousedown', (e) => {
            drawing = true;
            const pos = getPosition(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        });

        canvas.addEventListener('mousemove', (e) => {
            if (drawing) {
                const pos = getPosition(e);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            }
        });

        canvas.addEventListener('mouseup', () => (drawing = false));
        canvas.addEventListener('mouseout', () => (drawing = false));

        // Touch Events
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault(); // Prevent scrolling
            drawing = true;
            const pos = getPosition(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault(); // Prevent scrolling
            if (drawing) {
                const pos = getPosition(e);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            }
        });

        canvas.addEventListener('touchend', () => (drawing = false));
        canvas.addEventListener('touchcancel', () => (drawing = false));

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function saveSignature() {
            const signatureData = canvas.toDataURL('image/png'); // Get the current signature as a data URL
            const ticketNumber = "<?php echo htmlspecialchars($ticket_number); ?>";

            if (ticketNumber === 'N/A') {
                alert("Invalid ticket number!");
                return;
            }

            const formData = new FormData();
            formData.append('signature', signatureData);
            formData.append('ticket_number', ticketNumber);

            fetch('save_driver_signature.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data);

                // Disable the canvas to make it uneditable
                canvas.style.pointerEvents = 'none'; // Prevent interactions with the canvas
                document.querySelector('button[onclick="clearSignature()"]').disabled = true; // Disable Clear button
                document.querySelector('button[onclick="saveSignature()"]').disabled = true; // Disable Save button
            })
            .catch(error => console.error('Error:', error));
        }

        function submitTicket() {
    const ticketNumber = "<?php echo htmlspecialchars($ticket_number); ?>";
    const selectElement = document.getElementById('officerSignature');
    const officerId = selectElement.value;

    if (ticketNumber === 'N/A') {
        alert("Invalid ticket number!");
        return;
    }

    if (!officerId) {
        alert("Please select an officer.");
        return;
    }

    // Send the selected officer ID and ticket number to the server
    const formData = new FormData();
    formData.append('ticket_number', ticketNumber);
    formData.append('officer_id', officerId);

    fetch('save_officer_signature.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // After saving, redirect to receipt page
        window.location.href = 'receipt.php?ticket_number=' + encodeURIComponent(ticketNumber);
    })
    .catch(error => console.error('Error:', error));
}

    </script>
</body>
</html>