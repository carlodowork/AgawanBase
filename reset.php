<?php
include 'db.php'; // Database connection

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid
    $stmt = $conn->prepare("SELECT * FROM password_reset_temp WHERE token = ? AND expDate > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Show reset password form
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $email = $row['email'];

            // Update the user's password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$newPassword, $email]);

            // Delete the token
            $stmt = $conn->prepare("DELETE FROM password_reset_temp WHERE token = ?");
            $stmt->execute([$token]);

            echo "<div class='success-message'>Password has been reset successfully.</div>";
        }
    } else {
        echo "<div class='error-message'>Invalid or expired token.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="for.css">
    <title>Reset Password</title>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form action="" method="post">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>