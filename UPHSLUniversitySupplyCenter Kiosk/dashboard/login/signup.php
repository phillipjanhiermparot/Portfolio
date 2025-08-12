<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
            background-color: #2196f3;
            color: #ffffff;
            cursor: pointer;
            border-radius: 3px;
            outline: none;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0d47a1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>
        <form action="signup_process.php" method="POST">
            <input type="text" name="username" class="form-input" placeholder="Username" required>
            <input type="text" name="firstname" class="form-input" placeholder="First Name" required>
            <input type="text" name="lastname" class="form-input" placeholder="Last Name" required>
            <input type="email" name="email" class="form-input" placeholder="Email" required>
            <input type="password" name="password" class="form-input" placeholder="Password" required>
            <input type="password" name="confirmpassword" class="form-input" placeholder="Confirm Password" required>
            <button type="submit" class="btn">Sign Up</button>
        </form>
        <p>Already have an account? <a href="index.php">Login</a></p>
    </div>
</body>
</html>