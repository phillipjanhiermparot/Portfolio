<?php

// Database connection
    $servername = "localhost";
    $username = "id22081870_usc";
    $password = "#Uphsl123";
    $database = "id22081870_usc";


// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST["username"];
    $firstName = $_POST["firstname"];
    $lastName = $_POST["lastname"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmpassword"];

    // Perform validation
    $errors = array();

    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($firstName)) {
        $errors[] = "First Name is required";
    }

    if (empty($lastName)) {
        $errors[] = "Last Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }

    // If there are no errors, insert data into database
    if (empty($errors)) {
        // Prepare SQL statement
        $sql = "INSERT INTO signup (username, firstname, lastname, email, password) VALUES (?, ?, ?, ?, ?)";

        // Prepare and bind parameters
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $firstName, $lastName, $email, $password);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect to index.php
            header("Location: index.php");
            exit;
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        // Close statement and connection
        $stmt->close();
        $conn->close();
    } else {
        // If there are errors, display them to the user
        foreach ($errors as $error) {
            echo $error . "<br>";
        }
    }
} else {
    // If the form is not submitted, redirect the user to the signup page
    header("Location: signup.php");
    exit;
}
?>
