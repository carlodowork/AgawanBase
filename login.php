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
    <title>Login - Basebook</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #666362;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #D3D3D3;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #1877f2;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            background-color: #1877f2;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #165eab;
        }

        p {
            margin-top: 20px;
            font-size: 14px;
        }

        a {
            color: #1877f2;
            text-decoration: none;
        }

        a:hover {
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
    <h2>Login</h2>
    <?php if (!empty($error)): ?>
        <script>
            
            showError("<?php echo addslashes($error); ?>");
        </script>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required> <!-- Retain username input -->
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up here</a>.</p>
</div>

</body>
</html>