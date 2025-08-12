<?php
session_start();
include 'connection.php';

$email = "";
$emailErr = "";
$message = "";

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Please enter a valid email address.";
    }

    if (empty($emailErr)) {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT ID, sq FROM login WHERE email = :email UNION SELECT ID, sq FROM login WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Use fetchAll for UNION results

        if (count($result) == 1) {
            $row = $result[0]; // Access the first (and only) row
            $_SESSION['reset_user_id'] = $row['ID'];
            $_SESSION['reset_email'] = $email;
            $_SESSION['security_question'] = $row['sq'];
            header("Location: security_question.php");
            exit();
        } else {
            $message = "Email address not found.";
        }
        $stmt->closeCursor(); // Use closeCursor() with PDO
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Email</title>
    <link rel="stylesheet" href="/admin/css/index.css?v=1.0">
    <style>
        /* Basic styling (adjust as needed) */
        .container { display: flex; justify-content: center; align-items: center; min-height: 35vh; }
        .form-container { background-color: #f9f9f9; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h3 { text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="email"], button { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        button { background-color: #007bff; color: white; cursor: pointer; }
        .error { color: red; margin-top: 5px; }
        .message { color: green; margin-top: 5px; }
    </style>
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

    <div class="container">
        <div class="form-container">
            <h3>Forgot Password</h3>
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <label for="email">Enter your email address:</label>
                <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($email); ?>">
                <span class="error"><?php echo $emailErr; ?></span>
                <button type="submit">Next</button>
                <p><a href="index.php">Back to Login</a></p>
            </form>
        </div>
    </div>
</body>
</html>