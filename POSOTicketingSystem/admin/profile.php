<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    // Fetch logged-in user data using PDO
    $query = "SELECT * FROM login WHERE ID = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user data was found
    if (!$user) {
        throw new Exception("User data not found.");
    }

    // Function to log activity
    function logActivity($conn, $user_id, $activity) {
        $logQuery = "INSERT INTO profile_activity_log (user_id, activity, timestamp) VALUES (:user_id, :activity, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $logStmt->bindParam(':activity', $activity);
        $logStmt->execute();
    }

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $image = null; // Initialize image to null

        // Fetch the current user's username before the update
        $currentUsername = $user['username'];

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['tmp_name']) {
            $image = file_get_contents($_FILES['profile_image']['tmp_name']);
            logActivity($conn, $_SESSION['user_id'], "$currentUsername updated their profile picture.");
        } else {
            // If no new image is provided, keep the old image.
            $query_old_image = "SELECT image FROM login WHERE ID = :user_id";
            $stmt_old_image = $conn->prepare($query_old_image);
            $stmt_old_image->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt_old_image->execute();
            $old_image = $stmt_old_image->fetch(PDO::FETCH_ASSOC);
            if ($old_image && $old_image['image']) {
                $image = $old_image['image'];
            }
        }

        $updateQuery = "UPDATE login SET firstname = :firstname, lastname = :lastname, username = :username, email = :email, password = :password, image = :image WHERE ID = :user_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':firstname', $firstname);
        $updateStmt->bindParam(':lastname', $lastname);
        $updateStmt->bindParam(':username', $username);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':password', $password);
        $updateStmt->bindParam(':user_id', $_SESSION['user_id']);
        $updateStmt->bindParam(':image', $image, PDO::PARAM_LOB); // Bind image as LOB
        $updateStmt->execute();

        // Log other profile updates
        if ($firstname !== $user['firstname']) {
            logActivity($conn, $_SESSION['user_id'], "$currentUsername updated their first name to $firstname.");
        }
        if ($lastname !== $user['lastname']) {
            logActivity($conn, $_SESSION['user_id'], "$currentUsername updated their last name to $lastname.");
        }
        if ($username !== $user['username']) {
            logActivity($conn, $_SESSION['user_id'], "$currentUsername updated their username to $username.");
        }
        if ($email !== $user['email']) {
            logActivity($conn, $_SESSION['user_id'], "$currentUsername updated their email to $email.");
        }
        if ($password !== $user['password']) {
            logActivity($conn, $_SESSION['user_id'], "$currentUsername updated their password.");
        }

        // Refresh user data after update
        header("Location: profile.php");
        exit();
    }

    // Fetch activity log for the current user
    $logQuery = "SELECT activity, timestamp FROM profile_activity_log WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT 10"; // Fetch last 10 activities
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $logStmt->execute();
    $activityLog = $logStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="/POSO/admin/css/profile2.css?v=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
    
        .copyright {
            text-align: center;
            margin-top: 100px;
            color: white;
            font-size: 0.9em;
        }
        
        .activity-log-container {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            border: 1px solid white;
            background-color: #0000004c;
            
            overflow-x: auto;
            height: 50vh;

        }

        .activity-log-container h3 {
            margin-bottom: 15px;
            color: white;
            text-align: center;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9em;
            color: white;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .timestamp {
            color: white;
            font-size: 0.8em;
            float: right;
        }

        #inactivityDialog {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        z-index: 1003;
        text-align: center;
    }

    #inactivityDialog button {
        margin-top: 10px;
        padding: 8px 16px;
        cursor: pointer;
    }


    </style>
</head>
<body>
<img class="bg" src="/POSO/images/paz.jpg" alt="Background Image">
<div id="overlay"></div>
<div class="main-content">
    <header class="navbar">
        <img src="/POSO/images/left.png" alt="City Logo" class="logo">
        <div>
            <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
            <p class="city">CITY OF BIÑAN, LAGUNA</p>
        </div>
        <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
        <div class="hamburger" id="hamburger-icon">
            <i class="fa fa-bars"></i>
        </div>
    </header>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/POSO/images/right.png" alt="POSO Logo">
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="profile.php" class="active"> <i class="fas fa-user"></i> Profile</a></li>
            <li><a href="report.php"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php"> <i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/images/right.png" alt="POSO Logo">
        </div>
        <ul>
            <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="report.php" class="<?= $current_page == 'report.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div><br><br>
    <div class="card">
        <div class="profile-container text-center mt-5">
            <label for="profileImage" class="profile-image-label position-relative">
                <?php if ($user['image']): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($user['image']) ?>" alt="Profile Picture" class="profile-image">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="profile-image">
                <?php endif; ?>
                <input type="file" name="profile_image" id="profileImage" class="d-none" accept="image/*">
                <div class="profile-overlay d-none" id="profileOverlay">
                    <i class="fas fa-camera"></i>
                </div>
            </label>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-5 shadow rounded bg-white" id="profileForm">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="d-flex flex-column align-items-center mb-3 mt-3">
                        <button type="button" id="toggleUpdate" class="edit btn btn-success">Edit Profile</button>
                        <div id="updateNotification" class="update-notification text-white mt-2" style="display: none;">
                            You're updating your profile
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <input type="file" name="profile_image" class="form-control-file" id="profileImageInput" style="display: none;" disabled>
                    </div>
                    <div class="form-group mt-5">
                        <label>First Name:</label>
                        <input type="text" name="firstname" class="form-control mb-2" value="<?= htmlspecialchars($user['firstname']) ?>" required id="firstname" disabled>
                        <label>Last Name:</label>
                        <input type="text" name="lastname" class="form-control mb-2" value="<?= htmlspecialchars($user['lastname']) ?>" required id="lastname" disabled>
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="username" class="form-control mb-2" value="<?= htmlspecialchars($user['username']) ?>" required id="username" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-control mb-2" value="<?= htmlspecialchars($user['email']) ?>" required id="email" disabled>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" class="form-control mb-2" value="<?= htmlspecialchars($user['password']) ?>" required id="password" disabled>
                    </div>
                    <div class="d-flex flex-column align-items-center mb-5 mt-5">
                        <button type="submit" name="update_profile" class="update btn btn-success" id="updateButton" style="display: none;">Update Profile</button>
                    </div>
                </div>
            </div>
        </form>


        
        <div class="copyright" style="line-height: 1.4; text-align: center; display: flex; align-items: center; justify-content: center; padding: 10px; color: white;">
        <img src="/POSO/images/ccs.png" alt="CCS Logo" style="height: 30px; margin-right: 15px;">
        <div style="display: flex; flex-direction: column; justify-content: center;">
            © <?php echo date('Y'); ?> POSO Biñan Ticketing System | Developed by Arielle Castillo, Brian Dimaguila, Yesha Jao, Phillip Parot. <br>
            IT11 – College of Computer Studies, UPHSL Biñan Campus
        </div>
    </div>
    
    </div>
</div>

<div id="inactivityDialog">
        <p>No activity detected. This user will be automatically logged out within <span id="countdown">30</span> seconds.</p>
        <button id="stayLoggedIn">Stay Logged In</button>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let isUpdating = false;
    let originalValues = {};
    const profileImageInput = document.getElementById("profileImageInput");
    const profileImagePreview = document.querySelector(".profile-image");

    document.getElementById("toggleUpdate").addEventListener("click", function () {
        const toggleButton = document.getElementById("toggleUpdate");
        const profileOverlay = document.getElementById("profileOverlay");

        if (!isUpdating) {
            originalValues = {
                firstname: document.getElementById("firstname").value,
                lastname: document.getElementById("lastname").value,
                username: document.getElementById("username").value,
                email: document.getElementById("email").value,
                password: document.getElementById("password").value
            };

            toggleButton.innerText = "Cancel Edit";
            isUpdating = true;

            profileOverlay.classList.remove("d-none");
            profileImageInput.style.display = "block";
            profileImageInput.disabled = false;
        } else {
            document.getElementById("firstname").value = originalValues.firstname;
            document.getElementById("lastname").value = originalValues.lastname;
            document.getElementById("username").value = originalValues.username;
            document.getElementById("email").value = originalValues.email;
            document.getElementById("password").value = originalValues.password;

            toggleButton.innerText = "Edit Profile";
            isUpdating = false;

            profileOverlay.classList.add("d-none");
            profileImageInput.style.display = "none";
            profileImageInput.disabled = true;
        }

        const elements = ["firstname", "lastname", "username", "email", "password"];
        elements.forEach(id => document.getElementById(id).disabled = !isUpdating);

        document.getElementById("updateButton").style.display = isUpdating ? "block" : "none";
        document.getElementById("updateNotification").style.display = isUpdating ? "block" : "none";
    });

    profileImageInput.addEventListener("change", function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                profileImagePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    //hamburger and sidebar
    const hamburgerIcon = document.getElementById('hamburger-icon');
    const sidebar = document.getElementById('sidebar');
    const overlayElement = document.getElementById('overlay');

    hamburgerIcon.addEventListener('click', function(event) {
        sidebar.classList.toggle('show');
        overlayElement.classList.toggle('show');
        event.stopPropagation();
    });

    overlayElement.addEventListener('click', function() {
        sidebar.classList.remove('show');
        overlayElement.classList.remove('show');
    });

    document.addEventListener('click', function(event) {
        if (!sidebar.contains(event.target) && !hamburgerIcon.contains(event.target)) {
            sidebar.classList.remove('show');
            overlayElement.classList.remove('show');
        }
    });



   // Inactivity timer
        let inactivityTimeout;
        let countdownInterval;
        let timeRemaining = 30;

        function setInactivityTimer() {
            inactivityTimeout = setTimeout(showInactivityDialog, 120000); // 2 minutes (120000 ms)
            resetCountdown(); // Initialize countdown
        }

        function resetInactivityTimer() {
            clearTimeout(inactivityTimeout);
            clearInterval(countdownInterval);
            timeRemaining = 30;
            setInactivityTimer();
        }

        function showInactivityDialog() {
            document.getElementById('inactivityDialog').style.display = 'block';
            startCountdown();
        }

        function startCountdown() {
            countdownInterval = setInterval(function() {
                timeRemaining--;
                document.getElementById('countdown').textContent = timeRemaining;
                if (timeRemaining <= 0) {
                    clearInterval(countdownInterval);
                    // Perform logout (redirect to logout page)
                    window.location.href = 'logout.php';
                }
            }, 1000);
        }

        function resetCountdown() {
             timeRemaining = 30;
             if (countdownInterval) {
                clearInterval(countdownInterval);
             }
             document.getElementById('countdown').textContent = timeRemaining;
        }

        // Reset timer on any user activity
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keydown', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('scroll', resetInactivityTimer);
        document.addEventListener('wheel', resetInactivityTimer);


        // Event listener for "Stay Logged In" button
        document.getElementById('stayLoggedIn').addEventListener('click', function() {
            document.getElementById('inactivityDialog').style.display = 'none';
            resetInactivityTimer();
        });

        // Start the timer when the page loads
        setInactivityTimer();
});
</script>

</body>
</html>
