<?php
// Start the session
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Main Menu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/POSO/poso.tix/css/menu.css">
    <style>
        /* Custom styles for the modal */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            display: flex; /* Enable flexbox for centering */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Adjust as needed */
            max-width: 500px; /* Optional: set a maximum width */
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .modal-buttons button {
            margin-left: 10px;
        }

        .btn-grey {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .btn-grey:hover {
            background-color: #5a6268;
        }

        .btn-red {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-red:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center">
        <div class="ticket-container d-flex flex-column justify-content-center align-items-center">
            <div class="header-container d-flex justify-content-between align-items-center">
                <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
                <div class="col text-center">
                    <p class="title">POSO Traffic Violations</p>
                    <p class="city">-City of Binan, Laguna-</p>
                </div>
                <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
            </div>

            <div class="btn-container">
                <button class="btn btn-primary square-button" onclick="location.href='ticket.php'">CREATE INFRACTION TICKET</button>
                <button class="btn btn-primary square-button" onclick="location.href='history.php'">TICKET HISTORY</button>
                <button class="btn btn-secondary square-button" id="logoutBtn">LOGOUT</button>
            </div>
        </div>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-grey" onclick="closeLogoutModal()">Cancel</button>
                <button type="button" class="btn btn-red" onclick="location.href='logout.php'">Logout</button>
            </div>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("logoutModal");

        // Get the button that opens the modal
        var btn = document.getElementById("logoutBtn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close-button")[0];

        // Initially, ensure the modal is hidden
        modal.style.display = "none";

        // Open the modal when the button is clicked
        btn.onclick = function() {
            modal.style.display = "flex"; // Changed to flex for centering
        }

        // Close the modal when the user clicks on <span> (x)
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Close the modal when the user clicks anywhere outside of the modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Function to close the modal
        function closeLogoutModal() {
            modal.style.display = "none";
        }
    </script>
</body>
</html>