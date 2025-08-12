<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('connection.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

try {
    // Fetch logged-in user data
    $query = "SELECT username FROM login WHERE ID = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $loggedInUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $loggedInUsername = $loggedInUser['username'] ?? 'Unknown User';

    // Fetch activity log for the current user, excluding entries with "in report table"
    $logQuery = "SELECT activity, timestamp FROM profile_activity_log WHERE user_id = :user_id AND activity NOT LIKE '%in report table%' ORDER BY timestamp DESC";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $logStmt->execute();
    $activityLog = $logStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

function fetchUsers($table) {
    global $conn;
    $query = "SELECT * FROM $table";
    $stmt = $conn->query($query);
    return $stmt;
}

function deleteUser($table, $id) {
    global $conn;
    $query = "DELETE FROM $table WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    if (!$stmt->execute()) {
        print_r($stmt->errorInfo());
        return false;
    }
    return true;
}

function addUser($table, $firstname, $lastname, $username, $email, $password, $signature, $security_question, $security_answer) {
    global $conn;
    $query = "INSERT INTO $table (firstname, lastname, username, email, password, signature, sq, sqa)
                    VALUES (:firstname, :lastname, :username, :email, :password, :signature, :sq, :sqa)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':firstname', $firstname);
    $stmt->bindParam(':lastname', $lastname);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':signature', $signature);
    $stmt->bindParam(':sq', $security_question);
    $stmt->bindParam(':sqa', $security_answer);
    return $stmt->execute();
}

function archiveUser($firstname, $lastname, $username, $email, $password, $signature, $security_question, $security_answer, $role) {
    global $conn;
    $query = "INSERT INTO archive (firstname, lastname, username, email, password, signature, deleted_at, sq, sqa, role)
                    VALUES (:firstname, :lastname, :username, :email, :password, :signature, NOW(), :sq, :sqa, :role)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':firstname', $firstname);
    $stmt->bindParam(':lastname', $lastname);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':signature', $signature);
    $stmt->bindParam(':sq', $security_question);
    $stmt->bindParam(':sqa', $security_answer);
    $stmt->bindParam(':role', $role);
    if (!$stmt->execute()) {
        print_r($stmt->errorInfo());
        return false;
    }
    return true;
}

function reactivateUser($id, $role) {
    global $conn;
    $archiveTable = 'archive';
    $targetTable = ($role == 'admin') ? 'login' : 'hh_login';

    $stmt = $conn->prepare("SELECT firstname, lastname, username, email, password, signature, sq, sqa FROM $archiveTable WHERE id = :id AND role = :role");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':role', $role);
    $stmt->execute();
    $userToReactivate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userToReactivate) {
        $insertQuery = "INSERT INTO $targetTable (firstname, lastname, username, email, password, signature, sq, sqa)
                                                VALUES (:firstname, :lastname, :username, :email, :password, :signature, :sq, :sqa)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindParam(':firstname', $userToReactivate['firstname']);
        $insertStmt->bindParam(':lastname', $userToReactivate['lastname']);
        $insertStmt->bindParam(':username', $userToReactivate['username']);
        $insertStmt->bindParam(':email', $userToReactivate['email']);
        $insertStmt->bindParam(':password', $userToReactivate['password']);
        $insertStmt->bindParam(':signature', $userToReactivate['signature']);
        $insertStmt->bindParam(':sq', $userToReactivate['sq']);
        $insertStmt->bindParam(':sqa', $userToReactivate['sqa']);

        if ($insertStmt->execute()) {
            $deleteArchiveQuery = "DELETE FROM $archiveTable WHERE id = :id AND role = :role";
            $deleteArchiveStmt = $conn->prepare($deleteArchiveQuery);
            $deleteArchiveStmt->bindParam(':id', $id);
            $deleteArchiveStmt->bindParam(':role', $role);
            if (!$deleteArchiveStmt->execute()) {
                print_r($deleteArchiveStmt->errorInfo());
                return false;
            }
            return true;
        } else {
            print_r($insertStmt->errorInfo());
            return false;
        }
    } else {
        return false;
    }
}

function getUserRoleByEmail($conn, $email, $checkArchive = false) {
    if ($checkArchive) {
        $stmt = $conn->prepare("SELECT role FROM archive WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $archivedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($archivedUser && !empty($archivedUser['role'])) {
            return $archivedUser['role'];
        }
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM hh_login WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        return 'Officer';
    }
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        return 'Admin';
    }
    return 'Unknown';
}

function getUsernameByIdAndRole($conn, $id, $role) {
    $table = ($role == 'admin') ? 'login' : 'hh_login';
    $stmt = $conn->prepare("SELECT username FROM $table WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user['username'] ?? null;
}

function getArchivedUsernameByIdAndRole($conn, $id, $role) {
    $stmt = $conn->prepare("SELECT username FROM archive WHERE id = :id AND role = :role");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user['username'] ?? null;
}

function logActivity($conn, $userId, $loggedInUsername, $activity) {
    $logQuery = "INSERT INTO profile_activity_log (user_id, username, activity, timestamp) VALUES (?, ?, ?, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(1, $userId, PDO::PARAM_INT);
    $logStmt->bindParam(2, $loggedInUsername);
    $logStmt->bindParam(3, $activity);
    $logStmt->execute();
    $logStmt->closeCursor();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user']) && $_POST['role'] == 'admin') {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $signature = file_get_contents($_FILES['signature']['tmp_name']);
        $security_question = $_POST['security_question'];
        $security_answer = $_POST['security_answer'];
        $table = 'login';

        if (addUser($table, $firstname, $lastname, $username, $email, $password, $signature, $security_question, $security_answer)) {
            logActivity($conn, $_SESSION['user_id'], $loggedInUsername, "$loggedInUsername added new Admin: $username successfully.");
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } elseif (isset($_POST['add_user']) && $_POST['role'] == 'officer') {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $signature = file_get_contents($_FILES['signature']['tmp_name']);
        $security_question = $_POST['security_question'];
        $security_answer = $_POST['security_answer'];
        $table = 'hh_login';

        if (addUser($table, $firstname, $lastname, $username, $email, $password, $signature, $security_question, $security_answer)) {
            logActivity($conn, $_SESSION['user_id'], $loggedInUsername, "$loggedInUsername added new Officer: $username successfully.");
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    if (isset($_POST['delete_user_confirmed'])) {
        $idToDelete = $_POST['user_id'];
        $roleToDelete = $_POST['role'];
        $tableToDelete = ($roleToDelete == 'admin') ? 'login' : 'hh_login';

        $stmt = $conn->prepare("SELECT username, email FROM $tableToDelete WHERE id = :id");
        $stmt->bindParam(':id', $idToDelete);
        $stmt->execute();
        $userToDeleteData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userToDeleteData) {
            $usernameToDelete = $userToDeleteData['username'];
            $emailToDelete = $userToDeleteData['email'];
            $actualRole = getUserRoleByEmail($conn, $emailToDelete);

            $stmtArchive = $conn->prepare("SELECT firstname, lastname, username, email, password, signature, sq, sqa FROM $tableToDelete WHERE id = :id");
            $stmtArchive->bindParam(':id', $idToDelete);
            $stmtArchive->execute();
            $userToArchive = $stmtArchive->fetch(PDO::FETCH_ASSOC);

            if ($userToArchive) {
                if (archiveUser(
                    $userToArchive['firstname'],
                    $userToArchive['lastname'],
                    $userToArchive['username'],
                    $userToArchive['email'],
                    $userToArchive['password'],
                    $userToArchive['signature'],
                    $userToArchive['sq'],
                    $userToArchive['sqa'],
                    $actualRole
                )) {
                    if (deleteUser($tableToDelete, $idToDelete)) {
                        logActivity($conn, $_SESSION['user_id'], $loggedInUsername, "$loggedInUsername deactivated $actualRole: $usernameToDelete successfully.");
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit();
                    } else {
                        echo "<script>alert('Error deleting user.');</script>";
                    }
                } else {
                    echo "<script>alert('Error archiving user.');</script>";
                }
            } else {
                echo "<script>alert('User to archive not found.');</script>";
            }
        } else {
            echo "<script>alert('User to delete not found.');</script>";
        }
    }

    if (isset($_POST['reactivate_user'])) {
        $userIdToReactivate = $_POST['reactivate_user_id'];
        $userRoleToReactivate = $_POST['reactivate_user_role'];

        $archiveStmt = $conn->prepare("SELECT username, email FROM archive WHERE id = :id AND role = :role");
        $archiveStmt->bindParam(':id', $userIdToReactivate);
        $archiveStmt->bindParam(':role', $userRoleToReactivate);
        $archiveStmt->execute();
        $userToReactivateData = $archiveStmt->fetch(PDO::FETCH_ASSOC);

        if ($userToReactivateData) {
            $usernameToReactivate = $userToReactivateData['username'];
            $emailToReactivate = $userToReactivateData['email'];
            $actualRole = getUserRoleByEmail($conn, $emailToReactivate, true); // Check archive first

            if (reactivateUser($userIdToReactivate, $userRoleToReactivate)) {
                logActivity($conn, $_SESSION['user_id'], $loggedInUsername, "$loggedInUsername reactivated $actualRole: $usernameToReactivate successfully.");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                echo "<script>alert('Error reactivating user.');</script>";
            }
        } else {
            echo "<script>alert('User to reactivate not found in archive.');</script>";
        }
    }
}

// Fetch archived officers
$archivedOfficersStmt = $conn->prepare("SELECT * FROM archive WHERE role = 'officer'");
$archivedOfficersStmt->execute();

// Fetch archived admins
$archivedAdminsStmt = $conn->prepare("SELECT * FROM archive WHERE role = 'admin'");
$archivedAdminsStmt->execute();

?>

<!DOCTYPE html>
<html>
<head>
<link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="/POSO/admin/css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
<style>
    body.modal-open,
    body.form-open {
        overflow: hidden;
    }

    .activity-log-container {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
        max-height: 300px; /* Adjust as needed */
        overflow-y: auto; /* Enable vertical scrolling */
        border: 2px solid white;
    }

    .activity-log-container h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .activity-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        font-size: 0.9em;
        color: #555;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .timestamp {
        color: #777;
        font-size: 0.8em;
        float: right;
    }

    #formOverlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height:100%;
        background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
        z-index: 1000; /* Ensure it's above other content */
    }

    #userForm {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        z-index: 1001; /* Ensure it's above the overlay */
        max-height: 80vh; /* Limit height */
        overflow-y: auto; /* Enable scrolling if content overflows */
        width: 90%;
        max-width: 650px;
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
    
    #reactivateModal .modal-content {
    background-color: #faf5df !important;
    height: 150px;
    text-align: center;
    border: 2px solid #3b5667 !important;
    margin-top: 20%;
    }
    
    #reactivateModal .delete-confirm-btn {
    background-color: #28a745;
    color: white;
    border: 1px solid white !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.623); 
    font-size: 16px;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: 0.3s ease-in-out;
    width: 80px;
    }
    
    #reactivateModal .delete-confirm-btn:hover {
        background-color: #218838;
        font-weight: bold;
    }
    
        .copyright {
            text-align: center;
            margin-top: 100px;
            color: white;
            font-size: 0.9em;
        }

</style>
</head>
<body <?php if (isset($_GET['show_modal']) || isset($_GET['show_form'])): ?>class="<?php if (isset($_GET['show_modal'])) echo 'modal-open'; if (isset($_GET['show_form'])) echo 'form-open'; ?>"<?php endif; ?>>

<img class="bg" src="/POSO/images/plaza.jpg" alt="Background Image">

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
    <i class="fa fa-bars"></i> </div>
    </header>


<div id="formOverlay" onclick="closeForm()"></div>

<div class="container d-flex justify-content-center align-items-center">
    <form class="card mt-5" id="userForm" method="POST" enctype="multipart/form-data">

        <button type="button" class="position-fixed"
            style="top: -9px; right: 10px;  width: 30px; height: 30px; font-weight: bold; font-size: 19px; padding: 0;"
            onclick="closeForm()">X</button>

        <label for="firstname">First Name:</label>
        <input type="text" id="firstname" name="firstname" required>

        <label for="lastname">Last Name:</label>
        <input type="text" id="lastname" name="lastname" required>

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="signature">Signature:</label>
        <input type="file" id="signature" name="signature" accept="image/*" required>

        <label for="security_question">Security Question:</label>
        <select id="security_question" name="security_question" required>
            <option value="">Select a question</option>
            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
            <option value="What was the name of your first pet?">What was the name of your first pet?</option>
            <option value="What is your favorite color?">What is your favorite color?</option>
            <option value="What city were you born in?">What city were you born in?</option>
            <option value="What is the name of your favorite book?">What is the name of your favorite book?</option>
            <option value="What is your favorite food?">What is your favorite food?</option>
            <option value="What was the make of your first car?">What was the make of your first car?</option>
        </select>

        <label for="security_answer">Security Answer:</label>
        <input type="text" id="security_answer" name="security_answer" required>

        <label for="role">Role:</label>
        <select id="role" name="role">
            <option value="admin">Admin</option>
            <option value="officer">Officer</option>
        </select>

<button type="submit" name="add_user" class="btn btn-success" style="margin-top: 35px; display: block; margin-left: auto; margin-right: auto;">Submit</button>

</form>
</div>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="/images/right.png" alt="POSO Logo">
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="report.php"><i class="fas fa-file-alt"></i> Reports</a></li>
        <li><a href="settings.php"  class="active"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

</header>



<div class="container mt-5 pt-5"> <br>

 <div class="activity-log-container mt-5">
    <h3>Recent Activity</h3>
    <div style="max-height: 200px; overflow-y: auto;">
    <?php if (!empty($activityLog)): ?>
        <?php foreach ($activityLog as $logEntry): ?>
            <div class="activity-item">
                <?= htmlspecialchars($logEntry['activity']) ?>
                <span class="timestamp"><?= date('Y-m-d H:i:s', strtotime($logEntry['timestamp'])) ?></span>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No recent activity to display.</p>
    <?php endif; ?>
    </div>
</div>


    <h1 class="user text-white  heading text-center mt-5 mb-5 ">- USER MANAGEMENT - </h1>

        <form action="" method="post" class="mb-4">
            <div class="row g-5">
            <button type="button" class="btn btn-success " onclick="toggleForm()">Add New User</button>
            </div>
        </form>

    <div class="table-container">
        <table class="table table-bordered mt-1 mb-3">
            <thead>
                <tr>
                    <th class="head">Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Signature</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>

        <tbody>
        <?php
            $admins = fetchUsers('login');
            while ($row = $admins->fetch(PDO::FETCH_ASSOC)) {
                $fullname = $row['firstname'] . ' ' . $row['lastname'];
                echo "<tr>
                                            <td>$fullname</td>
                                            <td>{$row['username']}</td>
                                            <td>{$row['email']}</td>
                                            <td class='password-dots'>" .str_repeat('•', strlen($row['password'])) . "</td>
                                            <td><img src='data:image/jpeg;base64," . base64_encode($row['signature']) . "' height='50'/></td>
                                            <td>Admin</td>
                                            <td>
                                                <button class='btn btn-danger' onclick='showDeleteModal(\"admin\", {$row['ID']})'>Deactivate</button>
                                            </td>
                                            </tr>";
            }

            $officers = fetchUsers('hh_login');
            while ($row = $officers->fetch(PDO::FETCH_ASSOC)) {
                $fullname = $row['firstname'] . ' ' . $row['lastname'];
                echo "<tr>
                                            <td>$fullname</td>
                                            <td>{$row['username']}</td>
                                            <td>{$row['email']}</td>
                                            <td class='password-dots'>" . str_repeat('•', strlen($row['password'])) . "</td>
                                            <td><img src='data:image/jpeg;base64," . base64_encode($row['signature']) . "' height='50'/></td>
                                            <td>Officer</td>
                                            <td>
                                                <div class='d-flex align-items-center justify-content-start gap-2'>
                                                    <button class='btn btn-danger' onclick='showDeleteModal(\"officer\", {$row['ID']})'>Deactivate</button>
                                                    <a href='update_off.php?user_id={$row['ID']}' class='btn btn-edit'>Edit</a>
                                                </div>
                                            </td>
                                            </tr>";
            }
            ?>
    </tbody>
        </table>
        </div>

    <h2 class="user text-white heading text-center mt-5 mb-5 "> -Deactivated Officers-</h2>
    <div class="table-container">
        <table class="table table-bordered mt-1 mb-5">
            <thead>
                <tr>
                    <th class="head">Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Signature</th>
                    <th>Deleted At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($archivedOfficer = $archivedOfficersStmt->fetch(PDO::FETCH_ASSOC)) {
                    $fullname = $archivedOfficer['firstname'] . ' ' . $archivedOfficer['lastname'];
                    echo "<tr>
                                                        <td>$fullname</td>
                                                        <td>{$archivedOfficer['username']}</td>
                                                        <td>{$archivedOfficer['email']}</td>
                                                        <td class='password-dots'>" . str_repeat('•', strlen($archivedOfficer['password'])) . "</td>
                                                        <td><img src='data:image/jpeg;base64," . base64_encode($archivedOfficer['signature']) . "' height='50'/></td>
                                                        <td>{$archivedOfficer['deleted_at']}</td>
                                                        <td>
                                                            
                                                            <button  class='btn btn-success' onclick='showReactivateModal(\"officer\", {$archivedOfficer['ID']})'>Reactivate</button>
                                                            
                                                        </td>
                                                        </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <h2 class="user text-white heading text-center mt-5 mb-3 "> -Deactivated Admins-</h2>
    <div class="table-container">
        <table class="table table-bordered mt-1 mb-5">
            <thead>
                <tr>
                    <th class="head">Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Signature</th>
                    <th>Deleted At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($archivedAdmin = $archivedAdminsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $fullname = $archivedAdmin['firstname'] . ' ' . $archivedAdmin['lastname'];
                    echo "<tr>
                                                        <td>$fullname</td>
                                                        <td>{$archivedAdmin['username']}</td>
                                                        <td>{$archivedAdmin['email']}</td>
                                                        <td class='password-dots'>" . str_repeat('•', strlen($archivedAdmin['password'])) . "</td>
                                                        <td><img src='data:image/jpeg;base64," . base64_encode($archivedAdmin['signature']) . "' height='50'/></td>
                                                        <td>{$archivedAdmin['deleted_at']}</td>
                                                        <td>
                                                        
                                                            <button  class='btn btn-success' onclick='showReactivateModal(\"admin\", {$archivedAdmin['ID']})'>Reactivate</button>
                                                            
                                                        </td>
                                                        </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>



    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <p>Are you sure you want to deactivate this user?</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="user_id" id="deleteUserId">
                <input type="hidden" name="role" id="deleteUserRole"> <br>
                <button type="submit" name="delete_user_confirmed" class="delete-confirm-btn">YES</button>
                <button type="button" class="btn btn-danger" onclick="closeDeleteModal()">NO</button>
            </form>
        </div>
    </div>
    
    <div class="copyright" style="line-height: 1.4; text-align: center; display: flex; align-items: center; justify-content: center; padding: 10px; color: white;">
        <img src="/POSO/images/ccs.png" alt="CCS Logo" style="height: 30px; margin-right: 15px;">
        <div style="display: flex; flex-direction: column; justify-content: center;">
            © <?php echo date('Y'); ?> POSO Biñan Ticketing System | Developed by Arielle Castillo, Brian Dimaguila, Yesha Jao, Phillip Parot. <br>
            IT11 – College of Computer Studies, UPHSL Biñan Campus
        </div>
    </div>

     <div id="inactivityDialog">
        <p>No activity detected. This user will be automatically logged out within <span id="countdown">30</span> seconds.</p>
        <button id="stayLoggedIn">Stay Logged In</button>
    </div>

<div id="reactivateModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeReactivateModal()">&times;</span>
        <p>Are you sure you want to reactivate this user?</p>
        <form id="reactivateForm" method="POST">
            <input type="hidden" name="reactivate_user_id" id="reactivateUserId">
            <input type="hidden" name="reactivate_user_role" id="reactivateUserRole"> <br>
            <button type="submit" name="reactivate_user" class="delete-confirm-btn">YES</button>
            <button type="button" class="btn btn-danger" onclick="closeReactivateModal()">NO</button>
        </form>
    </div>
</div>

    <script>
        //hamburger and sidebar
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        hamburgerIcon.addEventListener('click', function(event) {
            sidebar.classList.toggle('show'); // Toggle sidebar
            overlay.classList.toggle('show'); // Show overlay
            event.stopPropagation(); // Prevent immediate close
});

        // Close sidebar & overlay when clicking on the overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Close sidebar & overlay when clicking outside of the sidebar
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && !hamburgerIcon.contains(event.target)) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

        //add user button
        function toggleForm() {
            document.getElementById("userForm").style.display = "block";
            document.getElementById("formOverlay").style.display = "block";
            document.body.classList.add('form-open');
        }

        //form
        function closeForm() {
            document.getElementById("userForm").style.display = "none";
            document.getElementById("formOverlay").style.display = "none";
            document.body.classList.remove('form-open');
        }

        //delete modal
        function showDeleteModal(role, id) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserRole').value = role;
            document.getElementById('deleteModal').style.display = 'block';
            document.body.classList.add('modal-open');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }





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


function showReactivateModal(role, id) {
    document.getElementById('reactivateUserId').value = id;
    document.getElementById('reactivateUserRole').value = role;
    document.getElementById('reactivateModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function closeReactivateModal() {
    document.getElementById('reactivateModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1002; /* Higher than form overlay */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .delete-confirm-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
        }

        .delete-confirm-btn:hover {
            background-color: #45a049;
        }

    </style>
</body>
</html>
