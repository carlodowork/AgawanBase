<?php
session_start();
include 'db.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the database
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, bio, password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user data was retrieved
if (!$user) {
    die("User  not found.");
}

// Initialize messages
$successMessage = "";
$errorMessage = "";
$passwordSuccessMessage = "";
$passwordErrorMessage = "";

// Handle form submission for user information update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];
    $currentPassword = $_POST['current_password']; // Get current password for verification

    // Verify the current password
    if (password_verify($currentPassword, $user['password'])) {
        // Update user information in the database
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $bio, $userId])) {
            $successMessage = "Information updated successfully!";
            header("Location: dashboard.php"); // Redirect to dashboard after success
            exit();
        } else {
            $errorMessage = "Failed to update information.";
        }
    } else {
        $errorMessage = "Current password is incorrect.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify the current password
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $userId])) {
                $passwordSuccessMessage = "Password changed successfully!";
                header("Location: dashboard.php"); // Redirect to dashboard after success
                exit();
            } else {
                $passwordErrorMessage = "Failed to change password.";
            }
        } else {
            $passwordErrorMessage = "New passwords do not match.";
        }
    } else {
        $passwordErrorMessage = "Current password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="set.css"> <!-- Link to your existing CSS file -->
    <title>Settings - Basebook</title>
    <style>
        body {
            position: relative; /* For absolute positioning of the back button */
        }

        .back-button {
            position: absolute; /* Position it in the top left corner */
            top: 10px; /* Adjust as needed */
            left: 10px; /* Adjust as needed */
            font-size: 24px; /* Font size for the arrow */
            color: white; /* Arrow color */
            background-color: transparent; /* No background */
            border: none; /* No border */
            cursor: pointer; /* Pointer cursor */
        }

        .back-button:hover {
            color: #0056b3; /* Darker blue on hover */
        }

        .success-message {
            color: green; /* Success message color */
        }

        .error-message {
            color: red; /* Error message color */
        }
    </style>
</head>
<body>
    <header>
        <button onclick="javascript:history.back()" class="back-button">&#8592;</button> <!-- Back arrow -->
        <h1>Settings</h1>
    </header>

    <main>
        <div class="container">
            <h2>Update Your Information</h2>

            <?php if ($successMessage): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php elseif ($errorMessage): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form action="setting.php" method="post">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <label for="bio">Bio:</label>
                <textarea name="bio" id="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>

                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>

                <button type="submit" name="update_info">Update Information</button>
            </form>

            <h2>Change Password</h2>

            <?php if ($passwordSuccessMessage): ?>
                <div class="success-message"><?php echo htmlspecialchars($passwordSuccessMessage); ?></div>
            <?php elseif ($passwordErrorMessage): ?>
                <div class="error-message"><?php echo htmlspecialchars($passwordErrorMessage); ?></div>
            <?php endif; ?>

            <form action="setting.php" method="post">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>

                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Basebook. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>