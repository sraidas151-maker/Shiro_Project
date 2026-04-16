

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

// Fetch all saved posts for current user
$sql_saved = "SELECT p.*, u.username, u.full_name, u.avatar,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as real_likes,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as real_comments,
              (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as real_shares,
              (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id) as save_count,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
              sp.created_at as saved_at
              FROM saved_posts sp
              JOIN posts p ON sp.post_id = p.id
              JOIN users u ON p.user_id = u.id
              WHERE sp.user_id = ?
              ORDER BY sp.created_at DESC";
$stmt = $conn->prepare($sql_saved);
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$saved_posts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total saved count
$total_saved = count($saved_posts);

function time_elapsed_string($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0)
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0)
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0)
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0)
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0)
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Posts - Shiro</title>
    <link rel="stylesheet" href="css/style.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Layout */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding-top: 90px;
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr var(--right-sidebar-width);
            gap: 2.5rem;
        
            min-height: 100vh;
        }

        .main-feed {
            margin-left: calc(var(--sidebar-width) + 2.5rem);
            margin-right: calc(var(--right-sidebar-width) + 2.5rem);
            width: 580px;
            height: fit-content;
        }

        /* Saved Header */
        .saved-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
        }

        .saved-header i {
            font-size: 2rem;
            opacity: 0.9;
        }

        .saved-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .saved-header p {
            margin: 0.25rem 0 0 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .saved-count-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 1rem;
        }

        /* Post Styles */
        .post {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .post:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .post-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .author-info h4 .verified {
            color: #3b82f6;
            font-size: 0.8rem;
        }

        .author-info span {
            font-size: 0.8rem;
            color: #64748b;
        }

        .post-more {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: background 0.2s;
        }

        .post-more:hover {
            background: #f1f5f9;
        }

        .post-content {
            padding: 0 1.25rem 1rem;
        }

        .post-content p {
            color: #334155;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .post-image {
            width: 100%;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .post-image img {
            width: 100%;
            height: auto;
            display: block;
        }

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

        .stats-left,
        .stats-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .post-buttons {
            padding: 0.5rem 1.25rem;
            display: flex;
            justify-content: space-between;
        }

        .post-btn {
            flex: 1;
            padding: 0.75rem;
            background: none;
            border: none;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .post-btn:hover {
            background: #f1f5f9;
            transform: scale(1.02);
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* Comments Section */
        .comments-section {
            display: none;
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .comment-input-container {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            outline: none;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
        }

        .comment-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-comment-submit {
            padding: 0.6rem 1.25rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            font-weight: 500;
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
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .comment-content {
            flex: 1;
        }

        .comment-author {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.85rem;
        }

        .comment-text {
            color: #475569;
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        .comment-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.2rem;
        }

        /* Share Modal */
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
            transition: all 0.2s;
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

        /* Like Animation */
        .like-animation {
            animation: heartBeat 0.5s ease;
        }

        @keyframes heartBeat {

            0%,
            100% {
                transform: scale(1);
            }

            25% {
                transform: scale(1.2);
            }

            50% {
                transform: scale(1);
            }

            75% {
                transform: scale(1.1);
            }
        }

        /* Toast Notification */
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

        .toast.success {
            background: #22c55e;
        }

        .toast.info {
            background: #3b82f6;
        }

        .toast.error {
            background: #ef4444;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-container {
                grid-template-columns: 240px 1fr 300px;
            }
        }

        @media (max-width: 992px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .sidebar-left,
            .sidebar-right {
                display: none;
            }

            .main-feed {
                margin: 0 auto;
                padding: 0 1rem;
            }
        }

        /* Saved label on posts */
        .saved-label {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 10;
        }
        
        .post {
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
                <li><a href="index.php"><i class="fas fa-home"></i><span>Home</span></a></li>
                <li><a href="explore.php"><i class="fas fa-compass"></i><span>Explore</span></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i><span>Notifications</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
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
                <a href="index.php" class="menu-item">
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
                <a href="saved.php" class="menu-item active">
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

        <!-- Main Content -->
        <main class="main-feed">
            <!-- Saved Header -->
            <div class="saved-header">
                <i class="fas fa-bookmark"></i>
                <div>
                    <h1>Saved Posts</h1>
                    <p>Posts you save from anywhere on Shiro will appear here</p>
                </div>
                <div class="saved-count-badge">
                    <i class="fas fa-bookmark"></i> <?php echo $total_saved; ?> saved
                </div>
            </div>

            <!-- Posts Feed -->
            <div class="posts-container">
                <?php if (empty($saved_posts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bookmark"></i>
                        <h3>No saved posts yet</h3>
                        <p>When you save a post from your feed or profile, it will appear here.</p>
                        <a href="index.php" class="btn-primary">
                            <i class="fas fa-home"></i> Go to Feed
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($saved_posts as $post):
                        $time_ago = time_elapsed_string($post['created_at']);
                        $likes = $post['real_likes'] ?? 0;
                        $comments = $post['real_comments'] ?? 0;
                        $shares = $post['real_shares'] ?? 0;
                        $save_count = $post['save_count'] ?? 0;
                        $is_liked = $post['is_liked'] > 0;
                        ?>
                        <article class="post" data-post-id="<?php echo $post['id']; ?>">
                            <div class="saved-label">
                                <i class="fas fa-bookmark"></i> Saved
                            </div>
                            <div class="post-header">
                                <div class="post-author">
                                    <img src="<?php echo $post['avatar']; ?>" alt="Author" class="author-avatar">
                                    <div class="author-info">
                                        <h4>
                                            <?php echo htmlspecialchars($post['full_name']); ?>
                                            <i class="fas fa-check-circle verified"></i>
                                        </h4>
                                        <span><?php echo $time_ago; ?> · <i class="fas fa-globe-americas"></i>
                                            <?php echo htmlspecialchars($post['location'] ?? 'Earth'); ?></span>
                                    </div>
                                </div>
                                <button class="post-more" onclick="showPostOptions(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </div>

                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['caption'])); ?></p>
                                <?php if (!empty($post['image'])): ?>
                                <div class="post-image">
                                    <img src="<?php echo $post['image']; ?>" alt="Post Image" loading="lazy">
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Stats with Save Count -->
                            <div class="post-stats-line">
                                <div class="stats-left">
                                    <span class="reactions">
                                        <i class="fas fa-heart" style="color: #ef4444;"></i>
                                        <span class="likes-count" data-post-id="<?php echo $post['id']; ?>"><?php echo $likes; ?> likes</span>
                                    </span>
                                </div>
                                <div class="stats-right">
                                    <span class="comments-count" data-post-id="<?php echo $post['id']; ?>"><?php echo $comments; ?> comments</span>
                                    <span>·</span>
                                    <span class="shares-count" data-post-id="<?php echo $post['id']; ?>"><?php echo $shares; ?> shares</span>
                                    <span>·</span>
                                    <span class="saves-count" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-bookmark" style="color: #3b82f6; font-size: 0.75rem;"></i>
                                        <span class="save-count-number"><?php echo $save_count; ?></span> saves
                                    </span>
                                </div>
                            </div>

                            <div class="post-buttons">
                                <button class="post-btn btn-like <?php echo $is_liked ? 'liked' : ''; ?>"
                                    data-post-id="<?php echo $post['id']; ?>" onclick="toggleLike(this)">
                                    <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span><?php echo $is_liked ? 'Liked' : 'Like'; ?></span>
                                </button>
                                <button class="post-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                    <i class="far fa-comment"></i> Comment
                                </button>
                                <button class="post-btn" onclick="openShareModal(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-share"></i> Share
                                </button>
                                <button class="post-btn btn-save saved" data-post-id="<?php echo $post['id']; ?>"
                                    onclick="toggleSave(this)">
                                    <i class="fas fa-bookmark"></i> Saved
                                </button>
                            </div>

                            <!-- Comments Section -->
                            <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                                <div class="comment-input-container">
                                    <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>"
                                        class="comment-avatar">
                                    <input type="text" class="comment-input" id="comment-input-<?php echo $post['id']; ?>"
                                        placeholder="Write a comment..."
                                        onkeypress="handleCommentKeypress(event, <?php echo $post['id']; ?>)">
                                    <button class="btn-comment-submit"
                                        onclick="submitComment(<?php echo $post['id']; ?>)">Post</button>
                                </div>
                                <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>">
                                    <!-- Comments loaded dynamically -->
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                    <div class="trend-item">
                        <span class="trend-category">Art · Trending</span>
                        <h4>#DigitalArt</h4>
                        <span class="trend-posts">67K posts</span>
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
                    <div class="suggestion-item">
                        <img src="https://i.pravatar.cc/150?img=12" alt="User">
                        <div class="suggestion-info">
                            <h4>David Kim</h4>
                            <span>@dkim</span>
                        </div>
                        <button class="btn-follow">Follow</button>
                    </div>
                    <div class="suggestion-item">
                        <img src="https://i.pravatar.cc/150?img=8" alt="User">
                        <div class="suggestion-info">
                            <h4>Lisa Anderson</h4>
                            <span>@landerson</span>
                        </div>
                        <button class="btn-follow">Follow</button>
                    </div>
                </div>
            </div>
            
            <div class="widget">
                <div class="widget-header">
                    <h3>Tips</h3>
                </div>
                <div class="trending-list">
                    <div class="trend-item">
                        <i class="fas fa-bookmark" style="color: #3b82f6;"></i>
                        <span>Click the bookmark icon on any post to save it</span>
                    </div>
                    <div class="trend-item">
                        <i class="fas fa-heart" style="color: #ef4444;"></i>
                        <span>Like posts to show appreciation</span>
                    </div>
                    <div class="trend-item">
                        <i class="fas fa-comment" style="color: #10b981;"></i>
                        <span>Comment to engage with creators</span>
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
        // Global variables
        let currentSharePostId = null;

        // Like functionality
        function toggleLike(btn) {
            const postId = btn.dataset.postId;
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');
            const isLiked = btn.classList.contains('liked');

            icon.classList.add('like-animation');
            setTimeout(() => icon.classList.remove('like-animation'), 400);

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
            const countSpan = document.querySelector(`.likes-count[data-post-id="${btn.dataset.postId}"]`);

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

            if (countSpan && count !== null) {
                countSpan.textContent = `${count} likes`;
            }
        }

        // Save functionality - Remove from saved page when unsaved
        function toggleSave(btn) {
            const postId = btn.dataset.postId;
            const post = btn.closest('.post');

            fetch(`ajax_handler.php?action=unsave`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Animate and remove the post from saved page
                    post.style.opacity = '0';
                    post.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        post.remove();
                        showToast('Removed from saved posts', 'info');
                        
                        // Check if no posts left and show empty state
                        const remainingPosts = document.querySelectorAll('.post').length;
                        if (remainingPosts === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            })
            .catch(err => console.error('Unsave error:', err));
        }

        // Comments functionality
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
                    showToast('Comment added!', 'success');
                }
            })
            .catch(err => console.error('Submit comment error:', err));
        }

        function appendCommentToDOM(container, comment, isNew = false) {
            const div = document.createElement('div');
            div.className = 'comment-item';
            if (isNew) div.style.animation = 'fadeIn 0.5s ease';

            div.innerHTML = `
                <img src="${comment.avatar || 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'}" class="comment-avatar">
                <div class="comment-content">
                    <div class="comment-author">${escapeHtml(comment.author)}</div>
                    <div class="comment-text">${escapeHtml(comment.content)}</div>
                    <div class="comment-time">${comment.time}</div>
                </div>
            `;
            container.insertBefore(div, container.firstChild);
        }

        // Share functionality
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
                    navigator.clipboard.writeText(postUrl).then(() => showToast('Link copied to clipboard!', 'success'));
                    break;
                case 'email':
                    window.location.href = `mailto:?subject=Check out this post&body=${encodeURIComponent(postUrl)}`;
                    break;
                case 'message':
                    showToast('Message feature coming soon!', 'info');
                    break;
            }

            // Record the share
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

        // Post options
        function showPostOptions(postId) {
            showToast('Post options coming soon!', 'info');
        }

        // Toast notification helper
        function showToast(message, type = 'success') {
            // Remove existing toast
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

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto-refresh saved posts every 30 seconds to show new saves
        let autoRefreshInterval;
        
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                location.reload();
            }, 30000);
        }
        
        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }
        
        // Start auto-refresh when page is active
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            startAutoRefresh();
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
        });
    </script>
</body>

</html>