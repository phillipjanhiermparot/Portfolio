<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: index.php");
    exit();
}

// Unset the session variable and destroy the session
unset($_SESSION['user_id']);
session_destroy();

// Redirect to the login page
header("Location: index.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO</title>
    <link rel="stylesheet" href="/admin/css/index.css?v=1.0">
</head>
<body>
<nav class="navbar">
<img src="/images/left.png" alt="Left Logo" class="logo">
        <div>
            <p class="public" >PUBLIC ORDER & SAFETY OFFICE</p>
            <p class="city">CITY OF BIÑAN, LAGUNA</p>
        </div>
        <img src="/images/arman.png" alt="POSO Logo" class="logo">
</nav>
<img class="bg" src="/images/plaza1.jpg" alt="Background Image">
<br><br><br>
    <div class="container">
        <div class="login-form">
            <h3>Logging Out...</h3>
            <p style="text-align: center; font-size: 18px;">You are being logged out.</p>
            <noscript>
                <p style="color: red; text-align: center;">Please enable JavaScript to be redirected automatically.</p>
                <p style="text-align: center;"><a href="index.php">Click here to go to the login page.</a></p>
            </noscript>
            <script>
                // Redirect after a brief delay (optional, but good for user feedback)
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 1000); // Adjust the delay in milliseconds as needed
            </script>
        </div>
    </div>
</body>
</html>