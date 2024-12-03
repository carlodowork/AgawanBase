<?php 
session_start(); 
include 'db.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); // Redirect to login if not logged in 
    exit(); 
} 

// Fetch user data from the database
$userId = $_SESSION['user_id']; 
$stmt = $conn->prepare("SELECT username, email, profile_picture, bio FROM users WHERE id = ?"); 
$stmt->execute([$userId]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC); 

// Check if user data was retrieved
if (!$user) { 
    die("User  not found."); 
}

// Handle form submission to update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];

    // Update the user information in the database
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
    $stmt->execute([$username, $email, $bio, $userId]); // Update the user data
    header("Location: dashboard.php"); // Redirect to the dashboard after updating
    exit();
}
?>

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="edit.css"> 
    <title>Edit Profile - Basebook</title> 
    <style>
        .return-button {
            position: absolute; /* Position it absolutely */
            top: 15px; /* Distance from the top */
            left: 20px; /* Distance from the left */
            padding: 10px 15px;
            background-color: #4267B2; /* Match the header color */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            z-index: 1000; /* Ensure it's above other elements */
        }
        .return-button:hover {
            background-color: #365eab; /* Darker shade on hover */
        }
    </style>
</head> 
<body> 
    <header> 
        <div class="container"> 
            <h1>Edit Your Profile</h1>
        </div> 
        <a href="javascript:history.back()" class="return-button">
            &#8592; Return
        </a>
    </header>

    <main>
        <div class="container">
            <section class="edit-profile">
                <h2>Your Information</h2>
                <form method="POST" action="">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    <br>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <br>

                    <label for="bio">Bio:</label>
                    <textarea id="bio" name="bio" rows="4" cols="50"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    <br>

                    <button type="submit">Update Profile</button>
                </form>
            </section>
        </div>
    </main>

    <footer> 
        <div class="container"> 
            <p>&copy; 2024 Basebook. All rights reserved.</p> 
        </div> 
    </footer>
</body> 
</html>