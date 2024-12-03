<?php 
session_start(); 
include 'db.php'; 

// Redirect to login if user is not authenticated
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

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) { 
    $postContent = trim($_POST['post_content']); 
    if (!empty($postContent)) { 
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)"); 
        $stmt->execute([$userId, $postContent]); 
    } 
} 

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) { 
    if ($_FILES['image']['error'] == 0) { 
        $image = $_FILES['image']['tmp_name']; 
        $imgContent = file_get_contents($image); 
        
        // Insert image data into database as BLOB 
        $sql = "UPDATE users SET profile_picture = ? WHERE id = ?"; 
        $stmt = $conn->prepare($sql); 
        $stmt->execute([$imgContent, $userId]); 
        
        if ($stmt) { 
            echo "Profile picture uploaded successfully."; 
            
            // Refresh user data 
            $stmt = $conn->prepare("SELECT username, email, profile_picture, bio FROM users WHERE id = ?"); 
            $stmt->execute([$userId]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC); 
        } else { 
            echo "Image upload failed, please try again."; 
        } 
    } else { 
        echo "Please select an image file to upload."; 
    } 
} 

// Handle like functionality
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['like'])) { 
    $postId = (int) $_GET['like']; 
    $stmt = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?"); 
    $stmt->execute([$postId, $userId]); 
    
    if ($stmt->rowCount() === 0) { 
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)"); 
        $stmt->execute([$postId, $userId]); 
    } else { 
        $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?"); 
        $stmt->execute([$postId, $userId]); 
    } 
} 

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) { 
    $postId = (int) $_GET['delete']; 
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?"); 
    $stmt->execute([$postId, $userId]); 
    header("Location: dashboard.php"); // Redirect to avoid resubmission 
    exit(); 
} 

// Fetch posts with user profile pictures
$stmt = $conn->prepare("SELECT p.id, p.content, p.created_at, u.username, u.profile_picture, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC"); 
$stmt->execute(); 
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC); 

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) { 
    $receiverId = $_POST['receiver_id']; 
    $subject = $_POST['subject']; 
    $body = $_POST['body']; 
    
    // Check if subject and body are not empty 
    if (!empty($subject) && !empty($body)) { 
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)"); 
        $stmt->execute([$userId, $receiverId, $subject, $body]); 
        echo "Message sent successfully!"; 
    } else { 
        echo "Subject and body cannot be empty."; 
    } 
} 

// ```php
// Fetch users for messaging 
$stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ?"); 
$stmt->execute([$userId]); 
$users = $stmt->fetchAll(PDO::FETCH_ASSOC); 

// Fetch received messages 
$stmt = $conn->prepare("SELECT m.*, u.username AS sender_username FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC"); 
$stmt->execute([$userId]); 
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC); 

// Send Friend Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $receiverId = $_POST['receiver_id'];
    $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
    $stmt->execute([$userId, $receiverId]);
}

// Accept or Reject Friend Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_request'])) {
    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    // Fetch the friend request
    $stmt = $conn->prepare("SELECT * FROM friend_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request && $request['receiver_id'] == $userId) {
        if ($action === 'accept') {
            // Add to friends table
            $stmt = $conn->prepare("INSERT INTO friends (user1_id, user2_id) VALUES (?, ?)");
            $stmt->execute([$request['sender_id'], $request['receiver_id']]);
        }

        // Update request status
        $stmt = $conn->prepare("UPDATE friend_requests SET status = ? WHERE id = ?");
        $stmt->execute([$action, $requestId]);
    }
}

// Fetch Friend Requests
$stmt = $conn->prepare("SELECT fr.id, u.username AS sender_username 
                        FROM friend_requests fr
                        JOIN users u ON fr.sender_id = u.id
                        WHERE fr.receiver_id = ? AND fr.status = 'pending'");
$stmt->execute([$userId]);
$friendRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Friends List
$stmt = $conn->prepare("SELECT u.username 
                        FROM friends f
                        JOIN users u ON (f.user1_id = u.id OR f.user2_id = u.id)
                        WHERE (f.user1_id = ? OR f.user2_id = ?) AND u.id != ? AND f.status = 'accepted'");
$stmt->execute([$userId, $userId, $userId]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Dashboard - Basebook</title> 
    <style> 
        /* Basebook CSS Styles */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f0f2f5; /* Light gray background */ 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        header { 
            background-color: #4267B2; /* Facebook blue */ 
            color: white; 
            padding: 15px 20px; 
        }
        .logo { 
            width: 60px; 
            height: 60px; /* Adjust logo size */ 
        }
        .menu-container { 
            position: absolute; 
            top: 15px;
            right: 15px; 
        }
        .hamburger { 
            background: none; 
            border: none; 
            color: white; 
            font-size: 24px; 
            cursor: pointer; 
        }
        .menu { 
            position: absolute; 
            right: 20; 
            background-color: white; 
            border: 1px solid #ccc; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
            z-index: 1000; 
        }
        .menu a { 
            display: block; padding: 10px 15px; 
            color: #333; 
            text-decoration: none; 
        }
        .menu a:hover { 
            background-color: #f0f2f5; 
        }
        ```php
        h1 { 
            text-align: center; 
            margin: 0; 
        }
        h2 { 
            margin: 10px 0; 
        }
        .dashboard-info, .post-section, .message-section, .messages-section, .posts-section, .friend-requests, .friends-list { 
            background-color: white; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
        }
        img { 
            max-width: 100%; 
            height: auto; 
        }
        textarea, input[type="text"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            margin-bottom: 10px; 
        }
        button { 
            background-color: #4267B2; 
            color: white; 
            border: none; 
            padding: 10px 15px; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        button:hover { 
            background-color: #365eab; 
        }
        .post { 
            border: 1px solid #e0e0e0; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 15px; 
        }
        .post-header { 
            display: flex; 
            align-items: center; 
        }
        .post-header img { 
            margin-right: 10px; 
        }
        .post-time { 
            font-size: 0.9em; 
            color: #888; 
        }
        .post-actions { 
            margin-top: 10px; 
        }
        .post-actions a { 
            text-decoration: none; 
            color: #4267B2; 
            margin-right: 15px; 
        }
        footer { 
            background-color: #4267B2; 
            color: white; 
            text-align: center; 
            padding: 10px 0; 
            margin-top: 20px; 
        }
        /* Responsive Styles */ 
        @media (max-width: 768px) { 
            header { 
                flex-direction: column; 
                align-items: flex-start; 
            } 
            .menu { 
                width: 100%; 
            } 
        } 
    </style>
</head> 
<body> 
<header> 
    <div class="container"> 
        <img src="logo B.png" alt="Basebook Logo" class="logo"> 
        <h1>Kumusta ang buhay buhay, <?php echo htmlspecialchars($user['username']); ?>!</h1> 
        <div class="menu-container"> 
            <button class="hamburger" onclick="toggleMenu()" aria-expanded="false">☰</button> 
            <div class="menu" id="dropdownMenu" style="display: none;"> 
                <a href="editprofile.php">Edit Profile</a> 
                <a href="setting.php">Settings</a> 
                <a href="logout.php" onclick="confirmLogout()">Log Out</a> 
            </div> 
        </div> 
    </div> 
</header> 

<main> 
    <div class="container"> 
        <section class="dashboard-info"> 
            <h2>Your Information</h2> 
            <img src="data:image/jpeg;base64,<?php echo base64_encode($user['profile_picture']); ?>" alt ="Profile Picture" style="width:100px; height:100px; border-radius:50%;"> 
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p> 
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p> 
            <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($user['bio'])); ?></p> 
        </section>

        <section class="post-section"> 
            <h2>Upload Profile Picture</h2> 
            <form method="POST" action="" enctype="multipart/form-data"> 
                <input type="file" name="image" accept="image/*" required> 
                <button type="submit">Upload</button> 
            </form> 
        </section>

        <section class="post-section "> 
            <h2>Create a Post</h2> 
            <form method="POST" action=""> 
                <textarea name="post_content" rows="4" required></textarea> 
                <button type="submit">Post</button> 
            </form> 
        </section>

        <section class="message-section"> 
            <h2>Send a Message</h2> 
            <form method="POST" action=""> 
                <label for="receiver_id">To:</label> 
                <select name="receiver_id" required> 
                    <?php foreach ($users as $user): ?> 
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option> 
                    <?php endforeach; ?> 
                </select> 
                <br> 
                <label for="subject">Subject:</label> 
                <input type="text" name="subject" required> 
                <br> 
                <label for="body">Message:</label> 
                <textarea name="body" required></textarea> 
                <br> 
                <button type="submit" name="send_message">Send Message</button> 
            </form> 
        </section>

        <section class="friend-requests">
            <h2>Send Friend Request</h2>
            <form method="POST" action="">
                <label for="receiver_id">To:</label>
                <select name="receiver_id" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="send_request">Send Request</button>
            </form>
        </section>

        <section class="friend-requests">
            <h2>Pending Friend Requests</h2>
            <?php foreach ($friendRequests as $request): ?>
                <div class="request">
                    <p>From: <?php echo htmlspecialchars($request['sender_username']); ?></p>
                    <form method="POST" action="">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" name="respond_request" value="accept">Accept</button>
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" name="respond_request" value="reject">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="friends-list">
            <h2>Your Friends</h2>
            <?php foreach ($friends as $friend): ?>
                <p><?php echo htmlspecialchars($friend['username']); ?></p>
            <?php endforeach; ?>
        </section>

        <section class="messages-section"> 
            <h2>Your Inbox</h2> 
            <?php foreach ($messages as $message): ?> 
                <div class="message"> 
                    <strong>From: <?php echo htmlspecialchars($message['sender_username']); ?></strong> 
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($message['subject']); ?></p> 
                    <p><?php echo nl2br(htmlspecialchars($message['body'])); ?></p> 
                    <p><em>Received on: <?php echo htmlspecialchars($message['created_at']); ?></em></p> 
                </div> 
            <?php endforeach; ?> 
        </section>

        <section class="posts-section"> 
            <h2>Posts</h2> 
            <?php foreach ($posts as $post): ?> 
                <div class="post"> 
                    <div class="post-header"> 
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($post['profile_picture']); ?>" alt="Profile Picture" style="width:50px; height:50px; border-radius:50%;"> 
                        <strong><?php echo htmlspecialchars($post['username']); ?></strong> <span class="post-time">(<?php echo htmlspecialchars($post['created_at']); ?>)</span> 
                    </div> 
                    <p><?php echo htmlspecialchars($post['content']); ?></p> 
                    <div class="post-actions"> 
                        <a href="?like=<?php echo $post['id']; ?>"> 
                            <img src="like.png" alt="Like" style="width: 20px; height: 30px;"> 
                            <span>(<?php echo $post['likes_count']; ?>)</span> 
                        </a> 
                        <a href="?delete=<?php echo $post['id']; ?>" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                    </div> 
                </div> 
            <?php endforeach; ?> 
        </section> 
    </div> 
</main> 

<footer> 
    <div class="container"> 
        <p>&copy; 2024 Basebook. All rights reserved.</p> 
    </div> 
</footer> 

<script> 
    function toggleMenu() { 
        var menu = document.getElementById("dropdownMenu"); 
        var isVisible = menu.style.display === "block"; 
        menu.style.display = isVisible ? "none" : "block"; 
        document.querySelector('.hamburger').setAttribute('aria-expanded', !isVisible); 
    } 

    window.onclick = function(event) { 
        var menu = document.getElementById("dropdownMenu"); 
        if (!event.target.matches('.hamburger') && menu.style.display === "block") { 
            menu.style.display = "none"; 
            document.querySelector('.hamburger').setAttribute('aria-expanded', 'false'); 
        } 
    } 

    function confirmLogout() { 
        if (confirm("Are you sure you want to log out?")) { 
            window.location.href = 'logout.php'; // Redirect to logout page 
        } 
    } 
</script> 

</body> 
</html>