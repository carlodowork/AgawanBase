<?php
session_start();
include 'db.php';

$error = ""; 
$username = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; 
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
        exit(); 
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basebook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #666362;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative;
        }

        .container {
            background-color: #D3D3D3;
            padding: 25px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 280px;
            text-align: center;
        }

        .logo {
            width: 100px; /* Adjust the width according to your logo size */
            height: auto; /* Maintain aspect ratio */
            margin-bottom: 20px; /* Space between logo and welcome text */
        }

        h2 {
            margin-bottom: 20px;
            color: #1877f2;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1 ```css
            border: 1px solid #ccc;
            border-radius: 4px;
            align-items: center;
        }

        button {
            background-color: #1877f2;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #165eab;
        }

        .footer {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: white;
            text-align: center;
        }

        .footer a {
            color: #1877f2;
            text-decoration: none;
            margin: 0 5px;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .forgot-password {
            margin-top: 10px;
            font-size: 14px;
        }

        .forgot-password a {
            color: #1877f2;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function showError(message) {
            alert(message);
        }
    </script>
</head>
<body>

<div class="container">
    <img src="logo B.png" alt="Basebook Logo" class="logo"> <!-- Logo added here -->
    <h2>Welcome</h2>
    <?php if (!empty($error)): ?>
        <script>
            // Call the function with the error message
            showError("<?php echo addslashes($error); ?>");
        </script>
    <?php endif; ?>
    <form action="login.php" method="post">
        <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required> <!-- Retain username input -->
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Log In</button>
    </form>
    <div class="forgot-password">
        <a href="forgot.php">Forgot Password?</a>
    </div>
    <div class="footer">
        <p>Don't have an account? <a href ="signup.php">Sign Up</a></p>
        <p>
            <a href="privacy.php">Privacy Policy</a> | 
            <a href="term.php">Terms of Service</a>
        </p>
        <p>&copy; 2024 Basebook. All rights reserved.</p>
    </div>
</div>

</body>
</html>