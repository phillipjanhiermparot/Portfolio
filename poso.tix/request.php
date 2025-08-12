<?php
// Start the session
session_start();

// Include PHPMailer Autoloader
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database connection (replace with your actual credentials)
include 'connection.php';

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $officer_email = filter_var($_POST["officer_email"], FILTER_SANITIZE_EMAIL);

    if (filter_var($officer_email, FILTER_VALIDATE_EMAIL)) {
        // Check if the email exists in the officers table
        $sql = "SELECT ID, username FROM hh_login WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $officer_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $officer_id = $row['ID'];
            $officer_username = $row['username'];
            $officer_email_requested = $_POST['officer_email']; // Get the submitted email

            // --- Email Notification to Administrator using PHPMailer ---
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host       = 'smtp.gmail.com';             //Set the SMTP server to send through (e.g., Gmail)
                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                $mail->Username   = 'posobinan25@gmail.com';       //SMTP username (your Gmail address)
                $mail->Password   = 'zsof xjyd sxiu rfup';                   //SMTP password (your Gmail password or App Password)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also possible
                $mail->Port       = 587;                                    //TCP port to connect to; use 465 if using `PHPMailer::ENCRYPTION_SMTPS`

                //Recipients
                $mail->setFrom('admin@posobinan.online', 'POSO Ticketing System');
                $mail->addAddress('posobinan25@gmail.com', 'Administrator');     //Add a recipient

                //Content
                $mail->isHTML(false);                                  //Set email format to plain text
                $mail->Subject = 'POSO Ticketing System - Officer Password Reset Request';
                $mail->Body    = "An officer has requested a password reset.\n\n" .
                                   "Officer Email: " . $officer_email_requested . "\n" .
                                   "Officer Username: " . $officer_username . "\n\n" .
                                   "Please follow your internal procedures to assist this officer with resetting their password.";

                $mail->send();
                $_SESSION['info'] = "A password reset request has been sent to the administrator.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to send the password reset request notification. Mailer Error: {$mail->ErrorInfo}";
            }
            // --- End Email Notification ---

            header("Location: request.php");
            exit();

        } else {
            $_SESSION['error'] = "No officer account found with the email address: $officer_email";
            header("Location: request.php");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: request.php");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - POSO Ticketing System</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/poso.tix/css/index.css">
    <style>
        .forgot-password-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .forgot-password-form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        .forgot-password-form h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }

        .forgot-password-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .forgot-password-form input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .forgot-password-form button[type="submit"] {
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .forgot-password-form button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .forgot-password-form .back-to-login {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-password-form .back-to-login a {
            color: #007bff;
            text-decoration: none;
        }

        .forgot-password-form .back-to-login a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>

<div class="forgot-password-container">
    <div class="forgot-password-form">
        <h3>Forgot Officer Password</h3>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info" role="alert">
                <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
            </div>
        <?php endif; ?>
        <p>Enter your registered email address to request a password reset.</p>
        <form action="request.php" method="POST">
            <label for="officer_email">Officer Email</label>
            <input type="email" name="officer_email" id="officer_email" required>
            <button type="submit">Request Password Reset</button>
        </form>
        <div class="back-to-login">
            <a href="index.php">Back to Login</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>

</body>
</html>