<?php 
session_start(); 
include 'db.php';  


if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php");  
    exit(); 
} 

// Fetch user data from the database 
$userId = $_SESSION['user_id']; 
$stmt = $conn->prepare("SELECT username, email, profile_picture, bio FROM users WHERE id = ?"); 
$stmt->execute([$userId]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC); 


if (!$user) { 
    die("User  not found."); 
} 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $bio = htmlspecialchars($_POST['bio']);
    
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileType = $_FILES['profile_picture']['type'];
        
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($fileType, $allowedTypes) && $fileSize < 2000000) { 
            $uploadPath = 'uploads/' . basename($fileName);
            move_uploaded_file($fileTmpPath, $uploadPath);
        }
    } else {
        $uploadPath = $user['profile_picture']; 
    }

    
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ?, profile_picture = ? WHERE id = ?");
    $stmt->execute([$username, $email, $bio, $uploadPath, $userId]);

    
    header("Location: profile.php"); 
    exit();
}
?>

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="dash.css"> <!-- Link to your existing CSS file --> 
    <title>User Profile - Basebook</title> 
</head> 
<body> 
    <header> 
        <div class="container"> 
            <h1>Your Profile</h1> 
            <a href="dashboard.php">Back to Dashboard</a>
        </div> 
    </header>

    <main>
        <div class="container">
            <form method="POST" enctype="multipart/form-data">
                <div>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" style="width:100px; height:100px; border-radius:50%;">
                </div>
                <div>
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div>
                    <label for="bio">Bio:</label>
                    <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>
                <div>
                    <label for="profile_picture">Profile Picture:</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                </div>
                <div>
                    <button type="submit">Update Profile</button>
                </div>
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