<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    $error_message = '';
    $success_message = '';
    
    // Validation
    if (empty($full_name)) {
        $error_message = "Full name is required";
    } elseif (empty($username)) {
        $error_message = "Username is required";
    } elseif (strlen($username) < 3) {
        $error_message = "Username must be at least 3 characters";
    } else {
        // Check if username already exists (excluding current user)
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $username, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Username already taken. Please choose another one.";
        } else {
            // Update database
            $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, bio = ?, website = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $full_name, $username, $bio, $website, $user_id);
            
            if ($update_stmt->execute()) {
                // Update session variables
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $username;
                $_SESSION['bio'] = $bio;
                $_SESSION['website'] = $website;
                
                $success_message = "Profile updated successfully!";
                
                // Refresh the page to show updated values
                header("Refresh:0");
                exit();
            } else {
                $error_message = "Database error: " . $conn->error;
            }
        }
    }
}

// Get current user data from database
$stmt = $conn->prepare("SELECT username, full_name, bio, website FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if ($user_data) {
    $username = $user_data['username'];
    $full_name = $user_data['full_name'];
    $bio = $user_data['bio'] ?? '';
    $website = $user_data['website'] ?? '';
} else {
    $username = $_SESSION['username'] ?? 'user';
    $full_name = $_SESSION['full_name'] ?? 'User';
    $bio = $_SESSION['bio'] ?? '';
    $website = $_SESSION['website'] ?? '';
}

$user_avatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face';

// Logout functionality
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Shiro</title>
    <link rel="stylesheet" href="css/style.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/settings.css">
    
    <style>
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-success {
            background: #22c55e20;
            color: #22c55e;
            border: 1px solid #22c55e40;
        }
        .alert-error {
            background: #ef444420;
            color: #ef4444;
            border: 1px solid #ef444440;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <div class="logo-circle"></div>
                    <div class="logo-dot"></div>
                </div>
                <span class="logo-text">shiro</span>
            </a>

            <div class="nav-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Shiro...">
            </div>

            <ul class="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i></a></li>
                <li><a href="explore.php"><i class="fas fa-compass"></i></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i></a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i></a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>

            <div class="nav-actions">
                <a href="upload.php"><button class="btn-create"><i class="fas fa-plus"></i> Create</button></a>
                <a href="profile.php">
                    <div class="user-menu">
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User" class="user-avatar">
                    </div>
                </a>
            </div>

            <button class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <div class="settings-container">
        <div class="settings-header">
            <h2>Account Settings</h2>
        </div>

        <!-- Show success/error messages -->
        <?php if (isset($success_message) && !empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="settings-section">
            <h3>Profile Information</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>"
                        placeholder="Your name" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>"
                        placeholder="@username" required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Write a short bio about yourself"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($website); ?>" placeholder="https://yourwebsite.com">
                </div>

                <button type="submit" name="update_profile" class="btn-save">
                    <i class="fas fa-check"></i> Save Changes
                </button>
            </form>
        </div>

        <div class="settings-section">
            <h3>Privacy Settings</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="postVisibility">Post Visibility</label>
                    <select id="postVisibility" name="post_visibility">
                        <option value="public">Public - Anyone can see</option>
                        <option value="followers">Followers Only</option>
                        <option value="private">Private - Only you</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="commentsEnabled">Comments</label>
                    <select id="commentsEnabled" name="comments_enabled">
                        <option value="enabled">Enabled</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>

                <button type="submit" name="update_privacy" class="btn-save">
                    <i class="fas fa-check"></i> Save Changes
                </button>
            </form>
        </div>

        <div class="settings-section">
            <h3>Notification Preferences</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="emailNotifications">Email Notifications</label>
                    <select id="emailNotifications" name="email_notifications">
                        <option value="all">All Notifications</option>
                        <option value="important">Important Only</option>
                        <option value="none">None</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pushNotifications">Push Notifications</label>
                    <select id="pushNotifications" name="push_notifications">
                        <option value="all">All Notifications</option>
                        <option value="important">Important Only</option>
                        <option value="none">None</option>
                    </select>
                </div>

                <button type="submit" name="update_notifications" class="btn-save">
                    <i class="fas fa-check"></i> Save Changes
                </button>
            </form>
        </div>

        <div class="settings-section" style="border: 1px solid rgba(239, 68, 68, 0.2);">
            <h3 style="color: var(--danger-color);">Danger Zone</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">Once you delete your account, there is no
                going back. Please be certain.</p>
            <button type="button" class="btn-save"
                style="background: var(--danger-color); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);"
                onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Delete Account
            </button>
        </div>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone!')) {
                alert('Account deletion request sent');
            }
        }
    </script>
</body>

</html>