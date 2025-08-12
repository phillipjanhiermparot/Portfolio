<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_email']) || !isset($_SESSION['security_question'])) {
    header("Location: fp.php");
    exit();
}

$securityAnswer = "";
$securityAnswerErr = "";
$message = "";

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $securityAnswer = sanitize($_POST['security_answer']);

    if (empty($securityAnswer)) {
        $securityAnswerErr = "Please enter your security answer.";
    }

    if (empty($securityAnswerErr)) {
        $user_id = $_SESSION['reset_user_id'];
        $stmt = $conn->prepare("SELECT sqa FROM login WHERE ID = :user_id UNION SELECT sqa FROM login WHERE ID = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Bind as integer
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 1) {
            $row = $result[0];
            if ($securityAnswer === $row['sqa']) {
                header("Location: reset_password.php");
                exit();
            } else {
                $message = "Incorrect security answer.";
            }
        } else {
            $message = "Error retrieving security answer.";
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
    <title>Forgot Password - Security Question</title>
    <link rel="stylesheet" href="/admin/css/index.css?v=1.0">
    <style>
        /* Basic styling (adjust as needed) */
        .container { display: flex; justify-content: center; align-items: center; min-height: 35vh; }
        .form-container { background-color: #f9f9f9; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h3 { text-align: center; margin-bottom: 20px; }
        p { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"], button { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
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
            <p>Your security question:</p>
            <p><strong><?php echo htmlspecialchars($_SESSION['security_question']); ?></strong></p>
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <label for="security_answer">Your Answer:</label>
                <input type="text" name="security_answer" id="security_answer" required>
                <span class="error"><?php echo $securityAnswerErr; ?></span>
                <button type="submit">Next</button>
                <p><a href="fp.php">Back to Email Input</a></p>
            </form>
        </div>
    </div>
</body>
</html>