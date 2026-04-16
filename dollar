<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Fetch user's most recent post with real counts INCLUDING SAVE COUNT
$sql_user = "SELECT p.*, u.username, u.full_name, u.avatar,
             (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as real_likes,
             (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as real_comments,
             (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as real_shares,
             (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id) as save_count,
             (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved
             FROM posts p 
             JOIN users u ON p.user_id = u.id 
             WHERE p.user_id = ? 
             ORDER BY p.created_at DESC 
             LIMIT 1";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$user_post = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user liked their own post
if ($user_post) {
    $check_like_sql = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_like_sql);
    $check_stmt->bind_param("ii", $user_post['id'], $current_user_id);
    $check_stmt->execute();
    $user_post['is_liked'] = $check_stmt->get_result()->num_rows > 0;
    $check_stmt->close();
}

// Check if demo posts already exist in database
$demo_posts_created = false;
$check_demo_stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE caption LIKE '%Demo Post%'");
$check_demo_stmt->execute();
$demo_check = $check_demo_stmt->get_result()->fetch_assoc();
$check_demo_stmt->close();

// Create demo posts in database if they don't exist
if ($demo_check['count'] == 0) {
    $demo_captions_list = [
        "Just captured this amazing moment! 📸✨ #photography #life",
        "Nature never fails to amaze me 🌿🌸 #nature #peace",
        "Weekend vibes with the best crew! 🎉👥 #friends #weekend",
        "Exploring new places, creating new memories 🗺️✈️ #travel #adventure",
        "Coffee and contemplation ☕🤔 #morning #thoughts",
        "Sunsets are proof that endings can be beautiful too 🌅❤️ #sunset #inspiration",
        "Good food, good mood 🍜😋 #foodie #delicious",
        "Working on my passion project today 💻🎨 #creative #work",
    ];

    $demo_locations = ['New York, USA', 'Tokyo, Japan', 'London, UK', 'Paris, France', 'Sydney, Australia'];
    $demo_images = [
        'https://picsum.photos/seed/demo1/800/600',
        'https://picsum.photos/seed/demo2/800/600',
        'https://picsum.photos/seed/demo3/800/600',
        'https://picsum.photos/seed/demo4/800/600',
        'https://picsum.photos/seed/demo5/800/600',
        'https://picsum.photos/seed/demo6/800/600',
        'https://picsum.photos/seed/demo7/800/600',
        'https://picsum.photos/seed/demo8/800/600',
    ];

    for ($i = 0; $i < 8; $i++) {
        $demo_caption = $demo_captions_list[$i] . " (Demo Post)";
        $demo_image = $demo_images[$i];
        $demo_location = $demo_locations[array_rand($demo_locations)];
        
        $insert_stmt = $conn->prepare("INSERT INTO posts (user_id, caption, image, location, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert_stmt->bind_param("isss", $current_user_id, $demo_caption, $demo_image, $demo_location);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
}

// Fetch ALL demo posts from database (real posts now)
$sql_demo_posts = "SELECT p.*, u.username, u.full_name, u.avatar,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as real_likes,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as real_comments,
                   (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as real_shares,
                   (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id) as save_count,
                   (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
                   FROM posts p 
                   JOIN users u ON p.user_id = u.id 
                   WHERE p.caption LIKE '%Demo Post%'
                   ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql_demo_posts);
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$demo_posts_result = $stmt->get_result();
$demo_posts = $demo_posts_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper function to check if post is by current user
function isMyPost($post_user_id) {
    global $current_user_id;
    return $post_user_id == $current_user_id;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shiro - Connect & Share Your World</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Additional styles for interactive features */
        .post-btn {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .post-btn:hover {
            transform: scale(1.05);
        }

        .post-btn.liked {
            color: #ef4444;
        }

        .post-btn.liked i {
            font-weight: 900;
        }

        .post-btn.saved {
            color: #3b82f6;
        }

        .post-btn.saved i {
            font-weight: 900;
        }

        .comments-section {
            display: none;
            padding: 1rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .comment-input-container {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .comment-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            outline: none;
            font-family: 'Inter', sans-serif;
        }

        .comment-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-comment-submit {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-comment-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .comments-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .comment-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-content {
            flex: 1;
        }

        .comment-author {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .comment-text {
            color: #475569;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .comment-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .share-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .share-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .share-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .share-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .share-option {
            padding: 1rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
        }

        .share-option:hover {
            border-color: #3b82f6;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .share-option i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .like-animation {
            animation: heartBeat 0.5s ease;
        }

        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(1); }
            75% { transform: scale(1.1); }
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #10b981;
            font-weight: 600;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Demo post styling */
        .demo-post {
            position: relative;
        }
        
        .demo-post::after {
            content: 'Demo Post';
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 10;
        }

        /* Stats display styling */
        .post-stats-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #64748b;
        }

        .stats-left, .stats-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .saves-count-display {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .saves-count-display i {
            color: #3b82f6;
            font-size: 0.75rem;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }

        .toast.success { background: #22c55e; }
        .toast.info { background: #3b82f6; }
        .toast.error { background: #ef4444; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* 3-Dot Menu Styles */
        .post-more {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .post-more:hover {
            background: #f1f5f9;
        }

        .post-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-width: 150px;
            z-index: 100;
            overflow: hidden;
        }

        .post-menu.active {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        .post-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.875rem;
            color: #475569;
        }

        .post-menu-item:hover {
            background: #f8fafc;
        }

        .post-menu-item.delete {
            color: #ef4444;
        }

        .post-menu-item.delete:hover {
            background: #fef2f2;
        }

        .post-header {
            position: relative;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
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
                <li><a href="index.php" class="active"><i class="fas fa-home"></i><span>Home</span></a></li>
                <li><a href="explore.php"><i class="fas fa-compass"></i><span>Explore</span></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i><span>Notifications</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i></a></li>
            </ul>

            <div class="nav-actions">
                <a href="upload.php"><button class="btn-create"><i class="fas fa-plus"></i> Create</button></a>
                <a href="profile.php">
                    <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>"
                        alt="User" class="user-avatar"
                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                </a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <!-- Left Sidebar -->
        <aside class="sidebar-left">
            <div class="sidebar-menu">
                <a href="index.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Feed</span>
                </a>
                <a href="explore.php" class="menu-item">
                    <i class="fas fa-compass"></i>
                    <span>Explore</span>
                </a>
                <a href="trending.php" class="menu-item">
                    <i class="fas fa-fire"></i>
                    <span>Trending</span>
                </a>
                <a href="saved.php" class="menu-item">
                    <i class="fas fa-bookmark"></i>
                    <span>Saved</span>
                </a>
                <a href="messages.php" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge">3</span>
                </a>
                <a href="notifications.php" class="menu-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="badge">5</span>
                </a>
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="?logout=true" class="menu-item logout-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Feed -->
        <main class="main-feed">
            <!-- Stories -->
            <div class="stories-container">
                <div class="story-item add-story">
                    <div class="story-ring">
                        <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>"
                            alt="Add Story">
                        <div class="add-icon"><i class="fas fa-plus"></i></div>
                    </div>
                    <span>Your Story</span>
                </div>
                <?php
                $usernames = ['alexchen', 'sarahmitch', 'jamesw', 'emmalew', 'marcusjohnson', 'davidkim', 'lisaanderson', 'ninapatel', 'alexrivera', 'emmajohnson'];
                ?>
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="story-item">
                        <div class="story-ring">
                            <img src="https://i.pravatar.cc/150?img=<?php echo rand(1, 70); ?>" alt="Story">
                        </div>
                        <span><?php echo $usernames[rand(0, count($usernames) - 1)]; ?></span>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Create Post -->
            <div class="create-post">
                <div class="post-input">
                    <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>"
                        alt="User" class="user-avatar">
                    <input type="text"
                        placeholder="What's on your mind, <?php echo explode(' ', $_SESSION['full_name'] ?? 'Friend')[0]; ?>?"
                        onclick="window.location.href='upload.php'">
                </div>
                <div class="post-actions">
                    <button class="post-type" onclick="window.location.href='upload.php'">
                        <i class="fas fa-image" style="color: #4ade80;"></i> Photo
                    </button>
                    <button class="post-type" onclick="window.location.href='upload.php'">
                        <i class="fas fa-video" style="color: #f472b6;"></i> Video
                    </button>
                    <button class="post-type"><i class="fas fa-map-marker-alt" style="color: #f87171;"></i>
                        Location</button>
                    <button class="post-type"><i class="fas fa-smile" style="color: #fbbf24;"></i> Feeling</button>
                </div>
            </div>

            <!-- Posts Feed -->
            <div class="posts-container">

                <?php if ($user_post):
                    $likes = $user_post['real_likes'] ?? 0;
                    $comments = $user_post['real_comments'] ?? 0;
                    $shares = $user_post['real_shares'] ?? 0;
                    $save_count = $user_post['save_count'] ?? 0;
                    $is_liked = $user_post['is_liked'] ?? false;
                    $is_saved = $user_post['is_saved'] ?? false;
                    $is_owner = isMyPost($user_post['user_id']);
                    ?>
                    <!-- User's Real Post -->
                    <article class="post user-post-highlight" 
                        data-post-id="<?php echo $user_post['id']; ?>" 
                        data-is-real="true"
                        data-created-at="<?php echo $user_post['created_at']; ?>"
                        data-is-new="true">
                        
                        <div class="post-header">
                            <div class="post-author">
                                <img src="<?php echo $user_post['avatar']; ?>" alt="Author" class="author-avatar">
                                <div class="author-info">
                                    <h4>
                                        <?php echo htmlspecialchars($user_post['full_name']); ?>
                                        <i class="fas fa-check-circle verified"></i>
                                        <span style="color: #3b82f6; font-size: 0.75rem; margin-left: 5px;">(You)</span>
                                        <span class="live-indicator" style="margin-left: 8px; display: none;">
                                            <span class="live-dot"></span> LIVE
                                        </span>
                                    </h4>
                                    <span class="post-time-display">
                                        <span class="time-text">just now</span> · <i class="fas fa-globe-americas"></i>
                                        <?php echo htmlspecialchars($user_post['location'] ?? 'Earth'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- 3-Dot Menu -->
                            <div style="position: relative;">
                                <button class="post-more" onclick="togglePostMenu(<?php echo $user_post['id']; ?>)">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="post-menu" id="menu-<?php echo $user_post['id']; ?>">
                                    <?php if ($is_owner): ?>
                                        <button class="post-menu-item delete" onclick="deletePost(<?php echo $user_post['id']; ?>)">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <button class="post-menu-item" onclick="reportPost(<?php echo $user_post['id']; ?>)">
                                            <i class="fas fa-flag"></i> Report
                                        </button>
                                    <?php endif; ?>
                                    <button class="post-menu-item" onclick="copyLink(<?php echo $user_post['id']; ?>)">
                                        <i class="fas fa-link"></i> Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($user_post['caption'])); ?></p>
                            <div class="post-image">
                                <img src="<?php echo $user_post['image']; ?>" alt="Post Image" loading="lazy">
                            </div>
                        </div>

                        <div class="post-stats-line">
                            <div class="stats-left">
                                <span class="reactions">
                                    <i class="fas fa-heart" style="color: #ef4444;"></i>
                                    <span class="likes-count" data-post-id="<?php echo $user_post['id']; ?>">
                                        <?php echo $likes; ?> likes
                                    </span>
                                </span>
                            </div>
                            <div class="stats-right">
                                <span class="comments-count" data-post-id="<?php echo $user_post['id']; ?>">
                                    <?php echo $comments; ?> comments
                                </span>
                                <span>·</span>
                                <span class="shares-count" data-post-id="<?php echo $user_post['id']; ?>">
                                    <?php echo $shares; ?> shares
                                </span>
                                <span>·</span>
                                <span class="saves-count-display saves-count" data-post-id="<?php echo $user_post['id']; ?>">
                                    <i class="fas fa-bookmark"></i>
                                    <span class="save-count-number"><?php echo $save_count; ?></span> saves
                                </span>
                            </div>
                        </div>

                        <div class="post-buttons">
                            <button class="post-btn btn-like <?php echo $is_liked ? 'liked' : ''; ?>" 
                                data-post-id="<?php echo $user_post['id']; ?>"
                                onclick="toggleLike(this)">
                                <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                <span><?php echo $is_liked ? 'Liked' : 'Like'; ?></span>
                            </button>
                            <button class="post-btn" onclick="toggleComments(<?php echo $user_post['id']; ?>)">
                                <i class="far fa-comment"></i> Comment
                            </button>
                            <button class="post-btn" onclick="openShareModal(<?php echo $user_post['id']; ?>)">
                                <i class="fas fa-share"></i> Share
                            </button>
                            <button class="post-btn btn-save <?php echo $is_saved ? 'saved' : ''; ?>" 
                                data-post-id="<?php echo $user_post['id']; ?>" 
                                onclick="toggleSave(this)">
                                <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                                <?php echo $is_saved ? 'Saved' : 'Save'; ?>
                            </button>
                        </div>

                        <div class="comments-section" id="comments-<?php echo $user_post['id']; ?>">
                            <div class="comment-input-container">
                                <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>"
                                    class="comment-avatar">
                                <input type="text" class="comment-input" id="comment-input-<?php echo $user_post['id']; ?>"
                                    placeholder="Write a comment..."
                                    onkeypress="handleCommentKeypress(event, <?php echo $user_post['id']; ?>)">
                                <button class="btn-comment-submit"
                                    onclick="submitComment(<?php echo $user_post['id']; ?>)">Post</button>
                            </div>
                            <div class="comments-list" id="comments-list-<?php echo $user_post['id']; ?>"></div>
                        </div>
                    </article>
                <?php endif; ?>

                <!-- Demo Posts -->
                <?php foreach ($demo_posts as $post):
                    $likes = $post['real_likes'] ?? 0;
                    $comments = $post['real_comments'] ?? 0;
                    $shares = $post['real_shares'] ?? 0;
                    $save_count = $post['save_count'] ?? 0;
                    $is_liked = ($post['is_liked'] ?? 0) > 0;
                    $is_saved = ($post['is_saved'] ?? 0) > 0;
                    $post_id = $post['id'];
                    $is_owner = isMyPost($post['user_id']);
                    ?>
                    <article class="post demo-post" 
                        data-post-id="<?php echo $post_id; ?>" 
                        data-is-real="true"
                        data-created-at="<?php echo $post['created_at']; ?>">
                        
                        <div class="post-header">
                            <div class="post-author">
                                <img src="<?php echo $post['avatar']; ?>" alt="Author" class="author-avatar">
                                <div class="author-info">
                                    <h4>
                                        <?php echo htmlspecialchars($post['full_name']); ?>
                                        <i class="fas fa-check-circle verified"></i>
                                    </h4>
                                    <span class="post-time-display">
                                        <span class="time-text">just now</span> · <i class="fas fa-globe-americas"></i>
                                        <?php echo htmlspecialchars($post['location'] ?? 'Earth'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- 3-Dot Menu -->
                            <div style="position: relative;">
                                <button class="post-more" onclick="togglePostMenu(<?php echo $post_id; ?>)">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="post-menu" id="menu-<?php echo $post_id; ?>">
                                    <?php if ($is_owner): ?>
                                        <button class="post-menu-item delete" onclick="deletePost(<?php echo $post_id; ?>)">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <button class="post-menu-item" onclick="reportPost(<?php echo $post_id; ?>)">
                                            <i class="fas fa-flag"></i> Report
                                        </button>
                                    <?php endif; ?>
                                    <button class="post-menu-item" onclick="copyLink(<?php echo $post_id; ?>)">
                                        <i class="fas fa-link"></i> Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($post['caption'])); ?></p>
                            <div class="post-image">
                                <img src="<?php echo $post['image']; ?>" alt="Post Image" loading="lazy">
                            </div>
                        </div>

                        <div class="post-stats-line">
                            <div class="stats-left">
                                <span class="reactions">
                                    <i class="fas fa-heart" style="color: #ef4444;"></i>
                                    <span class="likes-count" data-post-id="<?php echo $post_id; ?>">
                                        <?php echo $likes; ?> likes
                                    </span>
                                </span>
                            </div>
                            <div class="stats-right">
                                <span class="comments-count" data-post-id="<?php echo $post_id; ?>">
                                    <?php echo $comments; ?> comments
                                </span>
                                <span>·</span>
                                <span class="shares-count" data-post-id="<?php echo $post_id; ?>">
                                    <?php echo $shares; ?> shares
                                </span>
                                <span>·</span>
                                <span class="saves-count-display saves-count" data-post-id="<?php echo $post_id; ?>">
                                    <i class="fas fa-bookmark"></i>
                                    <span class="save-count-number"><?php echo $save_count; ?></span> saves
                                </span>
                            </div>
                        </div>

                        <div class="post-buttons">
                            <button class="post-btn btn-like <?php echo $is_liked ? 'liked' : ''; ?>" 
                                data-post-id="<?php echo $post_id; ?>"
                                onclick="toggleLike(this)">
                                <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                <span><?php echo $is_liked ? 'Liked' : 'Like'; ?></span>
                            </button>
                            <button class="post-btn" onclick="toggleComments(<?php echo $post_id; ?>)">
                                <i class="far fa-comment"></i> Comment
                            </button>
                            <button class="post-btn" onclick="openShareModal(<?php echo $post_id; ?>)">
                                <i class="fas fa-share"></i> Share
                            </button>
                            <button class="post-btn btn-save <?php echo $is_saved ? 'saved' : ''; ?>" 
                                data-post-id="<?php echo $post_id; ?>" 
                                onclick="toggleSave(this)">
                                <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                                <?php echo $is_saved ? 'Saved' : 'Save'; ?>
                            </button>
                        </div>

                        <div class="comments-section" id="comments-<?php echo $post_id; ?>">
                            <div class="comment-input-container">
                                <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>"
                                    class="comment-avatar">
                                <input type="text" class="comment-input" id="comment-input-<?php echo $post_id; ?>"
                                    placeholder="Write a comment..."
                                    onkeypress="handleCommentKeypress(event, <?php echo $post_id; ?>)">
                                <button class="btn-comment-submit"
                                    onclick="submitComment(<?php echo $post_id; ?>)">Post</button>
                            </div>
                            <div class="comments-list" id="comments-list-<?php echo $post_id; ?>"></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="sidebar-right">
            <div class="widget">
                <div class="widget-header">
                    <h3>Trends for you</h3>
                    <a href="settings.php" style="text-decoration: none;">
                        <button><i class="fas fa-cog"></i></button>
                    </a>
                </div>
                <div class="trending-list">
                    <div class="trend-item">
                        <span class="trend-category">Photography · Trending</span>
                        <h4>#GoldenHour</h4>
                        <span class="trend-posts">125K posts</span>
                    </div>
                    <div class="trend-item">
                        <span class="trend-category">Travel · Trending</span>
                        <h4>#HiddenGems</h4>
                        <span class="trend-posts">89K posts</span>
                    </div>
                </div>
            </div>

            <div class="widget">
                <div class="widget-header">
                    <h3>Who to follow</h3>
                </div>
                <div class="suggestions-list">
                    <div class="suggestion-item">
                        <img src="https://i.pravatar.cc/150?img=5" alt="User">
                        <div class="suggestion-info">
                            <h4>Sarah Chen</h4>
                            <span>@schen</span>
                        </div>
                        <button class="btn-follow">Follow</button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Share Modal -->
    <div class="share-modal" id="shareModal" onclick="closeShareModal(event)">
        <div class="share-content" onclick="event.stopPropagation()">
            <h3>Share to...</h3>
            <div class="share-options">
                <div class="share-option" onclick="shareTo('facebook')">
                    <i class="fab fa-facebook" style="color: #1877f2;"></i>
                    <div>Facebook</div>
                </div>
                <div class="share-option" onclick="shareTo('twitter')">
                    <i class="fab fa-twitter" style="color: #1da1f2;"></i>
                    <div>Twitter</div>
                </div>
                <div class="share-option" onclick="shareTo('whatsapp')">
                    <i class="fab fa-whatsapp" style="color: #25d366;"></i>
                    <div>WhatsApp</div>
                </div>
                <div class="share-option" onclick="shareTo('copy')">
                    <i class="fas fa-link" style="color: #6366f1;"></i>
                    <div>Copy Link</div>
                </div>
                <div class="share-option" onclick="shareTo('email')">
                    <i class="fas fa-envelope" style="color: #ea4335;"></i>
                    <div>Email</div>
                </div>
                <div class="share-option" onclick="shareTo('message')">
                    <i class="fas fa-comment-dots" style="color: #8b5cf6;"></i>
                    <div>Message</div>
                </div>
            </div>
            <button onclick="closeShareModal()"
                style="margin-top: 1.5rem; padding: 0.5rem 2rem; border: none; background: #e2e8f0; border-radius: 0.5rem; cursor: pointer;">Cancel</button>
        </div>
    </div>

    <script>
        // ==========================================
        // INDIAN TIME TRACKER - JAVASCRIPT ONLY
        // ==========================================
        class IndianTimeTracker {
            constructor() {
                this.posts = new Map();
                this.istOffset = 330 * 60 * 1000; // IST is UTC+5:30
                this.init();
            }

            getIndianTime() {
                const now = new Date();
                const utc = now.getTime() + (now.getTimezoneOffset() * 60 * 1000);
                return new Date(utc + this.istOffset);
            }

            toIST(date) {
                if (typeof date === 'string') date = new Date(date);
                const utc = date.getTime() + (date.getTimezoneOffset() * 60 * 1000);
                return new Date(utc + this.istOffset);
            }

            init() {
                this.initializePosts();
                this.startUpdater();
            }

            initializePosts() {
                document.querySelectorAll('article.post[data-post-id]').forEach(postElement => {
                    const postId = postElement.dataset.postId;
                    const createdAt = postElement.dataset.createdAt;
                    const isNew = postElement.dataset.isNew === 'true';
                    
                    let postTime = isNew || !createdAt ? this.getIndianTime() : this.toIST(createdAt);

                    this.posts.set(postId, {
                        createdAt: postTime,
                        isNew: isNew,
                        element: postElement,
                        timeDisplay: postElement.querySelector('.time-text'),
                        liveIndicator: postElement.querySelector('.live-indicator')
                    });

                    if (isNew) this.showLiveIndicator(postId);
                    this.updatePostTime(postId);
                });
            }

            getTimeAgo(date) {
                const now = this.getIndianTime();
                const diffInSeconds = Math.floor((now - date) / 1000);

                if (diffInSeconds < 60) return 'just now';
                if (diffInSeconds < 3600) {
                    const minutes = Math.floor(diffInSeconds / 60);
                    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
                }
                if (diffInSeconds < 86400) {
                    const hours = Math.floor(diffInSeconds / 3600);
                    return `${hours} hour${hours > 1 ? 's' : ''} ago`;
                }
                if (diffInSeconds < 2592000) {
                    const days = Math.floor(diffInSeconds / 86400);
                    return `${days} day${days > 1 ? 's' : ''} ago`;
                }
                if (diffInSeconds < 31536000) {
                    const months = Math.floor(diffInSeconds / 2592000);
                    return `${months} month${months > 1 ? 's' : ''} ago`;
                }
                const years = Math.floor(diffInSeconds / 31536000);
                return `${years} year${years > 1 ? 's' : ''} ago`;
            }

            updatePostTime(postId) {
                const post = this.posts.get(postId);
                if (!post || !post.timeDisplay) return;

                const timeAgo = this.getTimeAgo(post.createdAt);
                post.timeDisplay.textContent = timeAgo;

                if (post.isNew && post.liveIndicator) {
                    const now = this.getIndianTime();
                    const diffInSeconds = Math.floor((now - post.createdAt) / 1000);
                    if (diffInSeconds > 120) {
                        post.liveIndicator.style.display = 'none';
                        post.isNew = false;
                    }
                }
            }

            updateAllPosts() {
                this.posts.forEach((post, postId) => this.updatePostTime(postId));
            }

            startUpdater() {
                this.updateAllPosts();
                setInterval(() => this.updateAllPosts(), 1000);
            }

            showLiveIndicator(postId) {
                const post = this.posts.get(postId);
                if (post && post.liveIndicator) post.liveIndicator.style.display = 'inline-flex';
            }
        }

        // Initialize time tracker
        const timeTracker = new IndianTimeTracker();

        // ==========================================
        // POST MENU (3 DOTS) - DELETE FUNCTIONALITY
        // ==========================================
        function togglePostMenu(postId) {
            // Close all other menus
            document.querySelectorAll('.post-menu').forEach(menu => {
                if (menu.id !== `menu-${postId}`) menu.classList.remove('active');
            });
            
            const menu = document.getElementById(`menu-${postId}`);
            menu.classList.toggle('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.post-more')) {
                document.querySelectorAll('.post-menu').forEach(menu => {
                    menu.classList.remove('active');
                });
            }
        });

        function deletePost(postId) {
            if (!confirm('Are you sure you want to delete this post?')) return;

            fetch(`ajax_handler.php?action=delete_post`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const postElement = document.querySelector(`article[data-post-id="${postId}"]`);
                    if (postElement) {
                        postElement.style.opacity = '0';
                        postElement.style.transform = 'scale(0.9)';
                        setTimeout(() => postElement.remove(), 300);
                    }
                    showToast('Post deleted successfully', 'success');
                } else {
                    showToast(data.message || 'Failed to delete post', 'error');
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                showToast('Network error. Please try again.', 'error');
            });
        }

        function reportPost(postId) {
            showToast('Post reported. Thank you for your feedback.', 'info');
            togglePostMenu(postId);
        }

        function copyLink(postId) {
            const url = `${window.location.origin}/post.php?id=${postId}`;
            navigator.clipboard.writeText(url).then(() => {
                showToast('Link copied to clipboard!', 'success');
            });
            togglePostMenu(postId);
        }

        // ==========================================
        // LIKE, COMMENT, SHARE, SAVE - PHP HANDLED
        // ==========================================
        let currentSharePostId = null;

        function toggleLike(btn) {
            const postId = btn.dataset.postId;
            const isLiked = btn.classList.contains('liked');
            const action = isLiked ? 'unlike' : 'like';

            fetch(`ajax_handler.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateLikeButton(btn, !isLiked, data.likes);
                }
            })
            .catch(err => console.error('Like error:', err));
        }

        function updateLikeButton(btn, liked, count) {
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');
            const postId = btn.dataset.postId;
            const countSpan = document.querySelector(`.likes-count[data-post-id="${postId}"]`);

            if (liked) {
                btn.classList.add('liked');
                icon.classList.remove('far');
                icon.classList.add('fas');
                if (textSpan) textSpan.textContent = 'Liked';
            } else {
                btn.classList.remove('liked');
                icon.classList.remove('fas');
                icon.classList.add('far');
                if (textSpan) textSpan.textContent = 'Like';
            }

            if (countSpan && count !== undefined) {
                countSpan.textContent = `${count} likes`;
            }
        }

        function toggleSave(btn) {
            const postId = btn.dataset.postId;
            const isSaved = btn.classList.contains('saved');
            const action = isSaved ? 'unsave' : 'save';

            fetch(`ajax_handler.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateSaveButton(btn, !isSaved, data.save_count);
                }
            })
            .catch(err => console.error('Save error:', err));
        }

        function updateSaveButton(btn, saved, count) {
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');
            const postId = btn.dataset.postId;
            const countSpan = document.querySelector(`.saves-count[data-post-id="${postId}"] .save-count-number`);

            if (saved) {
                btn.classList.add('saved');
                icon.classList.remove('far');
                icon.classList.add('fas');
                if (textSpan) textSpan.textContent = 'Saved';
                showToast('Saved to your collection!', 'success');
            } else {
                btn.classList.remove('saved');
                icon.classList.remove('fas');
                icon.classList.add('far');
                if (textSpan) textSpan.textContent = 'Save';
                showToast('Removed from saved posts', 'info');
            }

            if (countSpan && count !== undefined) {
                countSpan.textContent = count;
            }
        }

        function toggleComments(postId) {
            const section = document.getElementById(`comments-${postId}`);
            const isVisible = section.style.display === 'block';
            section.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                loadComments(postId);
                setTimeout(() => {
                    const input = document.getElementById(`comment-input-${postId}`);
                    if (input) input.focus();
                }, 100);
            }
        }

        function loadComments(postId) {
            fetch(`ajax_handler.php?action=get_comments&post_id=${postId}`)
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById(`comments-list-${postId}`);
                    if (!list) return;
                    
                    list.innerHTML = '';
                    if (data.comments && data.comments.length > 0) {
                        data.comments.forEach(comment => appendCommentToDOM(list, comment));
                    } else {
                        list.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 1rem;">No comments yet. Be the first!</p>';
                    }
                    
                    const countSpan = document.querySelector(`.comments-count[data-post-id="${postId}"]`);
                    if (countSpan) countSpan.textContent = `${data.count} comments`;
                })
                .catch(err => console.error('Load comments error:', err));
        }

        function handleCommentKeypress(event, postId) {
            if (event.key === 'Enter') submitComment(postId);
        }

        function submitComment(postId) {
            const input = document.getElementById(`comment-input-${postId}`);
            const content = input.value.trim();
            if (!content) return;

            fetch('ajax_handler.php?action=add_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}&content=${encodeURIComponent(content)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    const list = document.getElementById(`comments-list-${postId}`);
                    if (list.children.length === 1 && list.children[0].tagName === 'P') list.innerHTML = '';
                    appendCommentToDOM(list, data.comment, true);
                    
                    const countSpan = document.querySelector(`.comments-count[data-post-id="${postId}"]`);
                    const currentCount = parseInt(countSpan?.textContent || 0);
                    if (countSpan) countSpan.textContent = `${currentCount + 1} comments`;
                }
            })
            .catch(err => console.error('Submit comment error:', err));
        }

        function appendCommentToDOM(container, comment, isNew = false) {
            const div = document.createElement('div');
            div.className = 'comment-item';
            if (isNew) div.style.animation = 'fadeIn 0.5s ease';

            div.innerHTML = `
                <img src="${comment.avatar || '<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>'}" class="comment-avatar">
                <div class="comment-content">
                    <div class="comment-author">${escapeHtml(comment.author)}</div>
                    <div class="comment-text">${escapeHtml(comment.content)}</div>
                    <div class="comment-time">${comment.time}</div>
                </div>
            `;
            container.insertBefore(div, container.firstChild);
        }

        function openShareModal(postId) {
            currentSharePostId = postId;
            document.getElementById('shareModal').classList.add('active');
        }

        function closeShareModal(event) {
            if (!event || event.target.id === 'shareModal') {
                document.getElementById('shareModal').classList.remove('active');
                currentSharePostId = null;
            }
        }

        function shareTo(platform) {
            if (!currentSharePostId) return;
            const postUrl = `${window.location.origin}/post.php?id=${currentSharePostId}`;

            switch (platform) {
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}`, '_blank');
                    break;
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(postUrl)}`, '_blank');
                    break;
                case 'whatsapp':
                    window.open(`https://wa.me/?text=${encodeURIComponent(postUrl)}`, '_blank');
                    break;
                case 'copy':
                    navigator.clipboard.writeText(postUrl).then(() => showToast('Link copied!', 'success'));
                    break;
                case 'email':
                    window.location.href = `mailto:?subject=Check out this post&body=${encodeURIComponent(postUrl)}`;
                    break;
                case 'message':
                    showToast('Message feature coming soon!', 'info');
                    break;
            }

            fetch('ajax_handler.php?action=share', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${currentSharePostId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const countSpan = document.querySelector(`.shares-count[data-post-id="${currentSharePostId}"]`);
                    if (countSpan) countSpan.textContent = `${data.shares} shares`;
                }
            })
            .catch(err => console.error('Share error:', err));

            closeShareModal();
        }

        function showToast(message, type = 'success') {
            const existingToast = document.querySelector('.toast');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>