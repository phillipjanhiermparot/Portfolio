<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Your database connection parameters
    $host = "localhost";
    $username = "id22081870_usc";
    $password = "#Uphsl123";
    $database = "id22081870_usc";

    // Create a new database connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the username and password are set in the POST data
    if(isset($_POST["username"]) && isset($_POST["password"])){
        // Get user input
        $username = $_POST["username"];
        $password = $_POST["password"];

        // Query the database for the user with the given nickname
        $sql = "SELECT * FROM signup WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            // Verify the password
            if ($password == $row["password"]) {
                // Password is correct, start a session
                $_SESSION["username"] = $username;
                header("Location: \dashboard\Dashboard.php"); // Redirect to the dashboard or another authenticated page
                exit;
            } else {
                // Password is incorrect
                $error_message = "Invalid password";
            }
        } else {
            // User not found
            $error_message = "User not found";
        }
    }
    
    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USC Login</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 350px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-input {
            width: 100%;
            margin-bottom: 20px;
            padding: 10px;
            border: none;
            border-bottom: 1px solid #9e9e9e;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-bottom-color: #2196f3;
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            background-color:#7380ec;
            color: #ffffff;
            cursor: pointer;
            border-radius: 3px;
            outline: none;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0d47a1;
        }

        .error-message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Login</h2>
        <form action="index.php" method="post">
            <input type="username" class="form-input" placeholder="Username" name="username" required>
            <input type="password" class="form-input" placeholder="Password" name="password" required>
            <button type="submit" class="btn">Login</button>
        </form>
        <?php if(isset($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
    </div>
</body>
</html>
