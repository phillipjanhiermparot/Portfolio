<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$newPassword = $confirmPassword = $captcha = "";
$newPasswordErr = $confirmPasswordErr = $captchaErr = "";
$message = "";
$showCaptcha = true;

// Function to generate a random string for captcha verification
function generateCaptchaText($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Generate captcha text if not already in session
if (!isset($_SESSION['captcha_text'])) {
    $_SESSION['captcha_text'] = generateCaptchaText();
}

function verifyCaptcha($userCaptcha) {
    return isset($_SESSION['captcha_text']) && strtolower($userCaptcha) === strtolower($_SESSION['captcha_text']);
}

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPassword = sanitize($_POST['new_password']);
    $confirmPassword = sanitize($_POST['confirm_password']);
    $captcha = sanitize($_POST['captcha']);

    if (empty($newPassword)) {
        $newPasswordErr = "Please enter a new password.";
    } elseif (strlen($newPassword) < 6) {
        $newPasswordErr = "Password must be at least 6 characters long.";
    }

    if (empty($confirmPassword)) {
        $confirmPasswordErr = "Please confirm your new password.";
    } elseif ($newPassword !== $confirmPassword) {
        $confirmPasswordErr = "Passwords do not match.";
    }

    if (empty($captcha)) {
        $captchaErr = "Please enter the text from the image.";
    } elseif (!verifyCaptcha($captcha)) {
        $captchaErr = "Incorrect text. Please try again.";
        $_SESSION['captcha_text'] = generateCaptchaText(); // Regenerate on failure
    }

    if (empty($newPasswordErr) && empty($confirmPasswordErr) && empty($captchaErr)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $user_id = $_SESSION['reset_user_id'];

        // Update password in both login and hh_login tables
        $stmt = $conn->prepare("UPDATE login SET password = :password WHERE ID = :user_id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE hh_login SET password = :password WHERE ID = :user_id");
        $stmt2->bindParam(':password', $hashedPassword);
        $stmt2->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt2->execute();

        if ($stmt->rowCount() > 0 || $stmt2->rowCount() > 0) {
            $message = "Password reset successfully. You can now <a href='index.php'>login</a> with your new password.";
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['captcha_text']);
            $showCaptcha = false;
        } else {
            $message = "Error updating password. Please try again.";
        }
        $stmt = null; // Close the statement
        $stmt2 = null; // Close the statement
    } else {
        $_SESSION['captcha_text'] = generateCaptchaText(); // Regenerate on form error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Reset</title>
    <link rel="stylesheet" href="/admin/css/index.css?v=1.0">
    <style>
        /* Basic styling (adjust as needed) */
        .container { display: flex; justify-content: center; align-items: center; min-height: 35vh; }
        .form-container { background-color: #f9f9f9; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h3 { text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="password"], input[type="text"], button, .captcha-image { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        button { background-color: #007bff; color: white; cursor: pointer; }
        .error { color: red; margin-top: 5px; }
        .message { color: green; margin-top: 5px; text-align: center; }
        .captcha-container { text-align: center; margin-bottom: 15px; }
        .captcha-image { display: inline-block; margin-right: 10px; border: 1px solid #eee; }
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
            <h3>Reset Password</h3>
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php else: ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password" required>
                    <span class="error"><?php echo $newPasswordErr; ?></span>

                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                    <span class="error"><?php echo $confirmPasswordErr; ?></span>

                    <?php if ($showCaptcha): ?>
                        <div class="captcha-container">
                            <img src="captcha.php" alt="Captcha Image" class="captcha-image">
                            <input type="text" name="captcha" id="captcha" placeholder="Enter the text" required>
                            <span class="error"><?php echo $captchaErr; ?></span>
                        </div>
                    <?php endif; ?>

                    <button type="submit">Reset Password</button>
                    <p><a href="index.php">Back to Login</a></p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>