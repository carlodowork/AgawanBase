<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="for.css">
    <title>Forgot Password</title>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <form action="forgot_password.php" method="post">
            <label for="email">Enter your email:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Send Reset Link</button>
        </form>
    </div>

    <?php
    include 'db.php'; // Include your database connection

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = $_POST['email'];
        $token = bin2hex(random_bytes(50)); // Generate a random token
        $expDate = date("Y-m-d H:i:s", strtotime('+1 hour')); // Set expiration time

        // Insert token into the database
        $stmt = $conn->prepare("INSERT INTO password_reset_temp (email, token, expDate) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expDate]);

        // Send email
        $to = $email;
        $subject = "Password Reset Request";
        $message = "Click the link to reset your password: ";
        $message .= "http://yourwebsite.com/reset.php?token=" . $token; // Change to your domain
        mail($to, $subject, $message);

        echo "<div class='success-message'>Reset link has been sent to your email.</div>";
    }
    ?>
</body>
</html>