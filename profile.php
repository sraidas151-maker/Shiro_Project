
# Create updated profile.php with save button and save count functionality

profile_php_content = '''<?php 
session_start();
require_once 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'rkumari510';
$full_name = $_SESSION['full_name'] ?? 'Roshani Kumari';
$user_avatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face';

// Fetch user's posts from database using $conn (mysqli) with real counts and save count
$user_posts = [];
$post_count = 0;

if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT p.*, 
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as real_likes,
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as real_comments,
                           (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as real_shares,
                           (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id) as save_count,
                           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
                           (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved
                           FROM posts p 
                           WHERE p.user_id = ? 
                           ORDER BY p.created_at DESC");
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_posts = $result->fetch_all(MYSQLI_ASSOC);
    $post_count = count($user_posts);
    $stmt->close();
}

// Helper function for time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($full_name); ?> · Shire Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .post-btn.liked { color: #ef4444; }
        .post-btn.liked i { font-weight: 900; }
        .post-btn.saved { color: #3b82f6; }
        .post-btn.saved i { font-weight: 900; }
        .comments-section { display: none; padding: 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .comment-input-container { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
        .comment-input { flex: 1; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 9999px; outline: none; font-family: 'Inter', sans-serif; }
        .comment-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-comment-submit { padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 9999px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-comment-submit:hover { background: #2563eb; transform: translateY(-2px); }
        .comments-list { max-height: 300px; overflow-y: auto; }
        .comment-item { display: flex; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
        .comment-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .comment-content { flex: 1; }
        .comment-author { font-weight: 600; color: #1e293b; font-size: 0.875rem; }
        .comment-text { color: #475569; font-size: 0.875rem; margin-top: 0.25rem; }
        .comment-time { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }
        .share-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .share-modal.active { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .share-content { background: white; padding: 2rem; border-radius: 1rem; max-width: 400px; width: 90%; text-align: center; }
        .share-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.5rem; }
        .share-option { padding: 1rem; border-radius: 0.75rem; cursor: pointer; border: 2px solid #e2e8f0; transition: all 0.3s; }
        .share-option:hover { border-color: #3b82f6; transform: translateY(-3px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
        .live-indicator { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #10b981; font-weight: 600; margin-left: 8px; }
        .live-dot { width: 6px; height: 6px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .user-post-highlight { border: 2px solid #3b82f6; box-shadow: 0 4px 20px rgba(59, 130, 246, 0.15); }
        .like-animation { animation: heartBeat 0.5s ease; }
        @keyframes heartBeat { 0%, 100% { transform: scale(1); } 25% { transform: scale(1.2); } 50% { transform: scale(1); } 75% { transform: scale(1.1); } }
        
        /* Save count styling */
        .save-count-display {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            color: #64748b;
            margin-left: 0.5rem;
        }
        .save-count-display i {
            color: #3b82f6;
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
        .stats-left, .stats-right {
            display: flex;
            align-items: center;
            gap: 1rem;
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
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i></a></li>
            </ul>

            <div class="nav-actions">
                <a href="upload.php"><button class="btn-create"><i class="fas fa-plus"></i> Create</button></a>
                <a href="logout.php"><button class="btn-logout">Logout</button></a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <!-- Cover Photo -->
        <div class="cover-photo">
            <img src="https://images.unsplash.com/photo-1647974562219-51a5864910af?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MjZ8fGJhY2tncm91bmQlMjBwaG90b3xlbnwwfHwwfHx8MA%3D%3D" alt="Cover">
            <button class="edit-cover"><i class="fas fa-camera"></i> Edit Cover</button>
        </div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-container">
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Profile" class="profile-avatar">
                <button class="edit-avatar"><i class="fas fa-camera"></i></button>
            </div>
            
            <div class="profile-actions">
                <button class="btn-message"><i class="fas fa-envelope"></i> Message</button>
                <button class="btn-follow">Follow</button>
                <button class="btn-more"><i class="fas fa-ellipsis-h"></i></button>
            </div>
        </div>

        <!-- Profile Info -->
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($full_name); ?> <i class="fas fa-check-circle verified"></i></h1>
            <p class="username">@<?php echo htmlspecialchars($username); ?></p>
            <p class="bio">📸 Photographer | 🌍 Traveler | ☕ Coffee enthusiast<br>Capturing moments and sharing stories. Based in NYC ✨</p>
            
            <div class="profile-meta">
                <span><i class="fas fa-map-marker-alt"></i> India, Siddarthnagar</span>
                <span><i class="fas fa-link"></i> roshanikumari.com</span>
                <span><i class="fas fa-calendar-alt"></i> Joined September 2019</span>
            </div>

            <div class="profile-stats">
                <div class="stat">
                    <strong>247</strong>
                    <span>Following</span>
                </div>
                <div class="stat">
                    <strong>489.5K</strong>
                    <span>Followers</span>
                </div>
                <div class="stat">
                    <strong><?php echo $post_count + 4; ?></strong>
                    <span>Posts</span>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="profile-nav">
            <a href="#" class="active">Posts</a>
            <a href="#">Replies</a>
            <a href="#">Media</a>
            <a href="#">Likes</a>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <div class="content-grid">
                
                <?php foreach ($user_posts as $post): 
    $time_ago = time_elapsed_string($post['created_at']);
    $is_just_now = ($time_ago === 'just now');
    $likes = $post['real_likes'] ?? 0;
    $comments = $post['real_comments'] ?? 0;
    $shares = $post['real_shares'] ?? 0;
    $save_count = $post['save_count'] ?? 0;
    $is_liked = $post['is_liked'] > 0;
    $is_saved = $post['is_saved'] > 0;
    ?>
    
    <div class="post <?php echo $first ? 'featured user-post-highlight' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
        <div class="post-header">
            <div class="post-author">
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Author" class="author-avatar">
                <div class="author-info">
                    <h4>
                        <?php echo htmlspecialchars($full_name); ?> 
                        <i class="fas fa-check-circle verified"></i>
                        <?php if($is_just_now && $first): ?>
                        <span class="live-indicator">
                            <span class="live-dot"></span> LIVE
                        </span>
                        <?php endif; ?>
                    </h4>
                    <span><?php echo $time_ago; ?> · <i class="fas fa-globe-americas"></i> <?php echo htmlspecialchars($post['location'] ?? 'Earth'); ?></span>
                </div>
            </div>
        </div>
        <div class="post-content">
            <p><?php echo nl2br(htmlspecialchars($post['caption'])); ?></p>
            <div class="post-image">
                <img src="<?php echo $post['image']; ?>" alt="Post">
            </div>
        </div>
        
        <!-- THIS IS WHERE THE STATS SECTION GOES - REPLACE EXISTING STATS WITH THIS -->
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
            <button class="post-btn btn-like <?php echo $is_liked ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" onclick="toggleLike(this)">
                <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                <span><?php echo $is_liked ? 'Liked' : 'Like'; ?></span>
            </button>
            <button class="post-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                <i class="far fa-comment"></i> Comment
            </button>
            <button class="post-btn" onclick="openShareModal(<?php echo $post['id']; ?>)">
                <i class="fas fa-share"></i> Share
            </button>
            <button class="post-btn btn-save <?php echo $is_saved ? 'saved' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" onclick="toggleSave(this)">
                <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                <span><?php echo $is_saved ? 'Saved' : 'Save'; ?></span>
            </button>
        </div>
        
        <!-- Comments Section -->
        <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
            <div class="comment-input-container">
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="comment-avatar">
                <input type="text" class="comment-input" id="comment-input-<?php echo $post['id']; ?>" 
                       placeholder="Write a comment..." onkeypress="handleCommentKeypress(event, <?php echo $post['id']; ?>)">
                <button class="btn-comment-submit" onclick="submitComment(<?php echo $post['id']; ?>)">Post</button>
            </div>
            <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>"></div>
        </div>
    </div>
    
    <?php $first = false; ?>
<?php endforeach; ?>


                <!-- STATIC POSTS (Original - With Dynamic Username) -->
                <!-- Featured Post -->
                <div class="post featured">
                    <div class="post-header">
                        <div class="post-author">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Author" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($full_name); ?> <i class="fas fa-check-circle verified"></i></h4>
                                <span>Dec 24 · <i class="fas fa-globe-americas"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <p>2023 has been an incredible year of growth and adventure. From the mountains of Switzerland to the beaches of Bali, grateful for every moment. Here's to more adventures in 2024! 🥂✨</p>
                        <div class="post-images grid-3">
                            <img src="https://images.unsplash.com/photo-1531366929518-7c912a455467?w=400&h=400&fit=crop" alt="Travel">
                            <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400&h=400&fit=crop" alt="Beach">
                            <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4cc801?w=400&h=400&fit=crop" alt="Nature">
                        </div>
                    </div>
                    <div class="post-stats-line">
                        <div class="stats-left">
                            <span><i class="fas fa-heart" style="color: #ef4444;"></i> 2,534,232 likes</span>
                        </div>
                        <div class="stats-right">
                            <span>1.2k comments</span>
                            <span>·</span>
                            <span>856 shares</span>
                            <span>·</span>
                            <span><i class="fas fa-bookmark" style="color: #3b82f6; font-size: 0.75rem;"></i> 3.2k saves</span>
                        </div>
                    </div>
                    <div class="post-buttons">
                        <button class="post-btn btn-like" onclick="toggleLike(this)"><i class="far fa-heart"></i> Like</button>
                        <button class="post-btn" onclick="toggleCommentsStatic(this)"><i class="far fa-comment"></i> Comment</button>
                        <button class="post-btn" onclick="openShareModalStatic()"><i class="fas fa-share"></i> Share</button>
                        <button class="post-btn btn-save" onclick="toggleSaveStatic(this)"><i class="far fa-bookmark"></i> Save</button>
                    </div>
                    <!-- Comments Section for Static Post -->
                    <div class="comments-section" style="display: none;">
                        <div class="comment-input-container">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="comment-avatar">
                            <input type="text" class="comment-input" placeholder="Write a comment...">
                            <button class="btn-comment-submit" onclick="alert('Comment added!')">Post</button>
                        </div>
                    </div>
                </div>

                <!-- Regular Posts -->
                <div class="post">
                    <div class="post-header">
                        <div class="post-author">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Author" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($full_name); ?> <i class="fas fa-check-circle verified"></i></h4>
                                <span>Dec 20 · <i class="fas fa-map-marker-alt"></i> Central Park</span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <p>Winter wonderland in Central Park ❄️ The snow makes everything look magical.</p>
                        <div class="post-image">
                            <img src="https://images.unsplash.com/photo-1773613007146-650c070ffc59?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxmZWF0dXJlZC1waG90b3MtZmVlZHwxOXx8fGVufDB8fHx8fA%3D%3D" alt="Winter">
                        </div>
                    </div>
                    <div class="post-stats-line">
                        <div class="stats-left">
                            <span><i class="fas fa-heart" style="color: #ef4444;"></i> 1.2k likes</span>
                        </div>
                        <div class="stats-right">
                            <span>89 comments</span>
                            <span>·</span>
                            <span>45 shares</span>
                            <span>·</span>
                            <span><i class="fas fa-bookmark" style="color: #3b82f6; font-size: 0.75rem;"></i> 234 saves</span>
                        </div>
                    </div>
                    <div class="post-buttons">
                        <button class="post-btn btn-like" onclick="toggleLike(this)"><i class="far fa-heart"></i> Like</button>
                        <button class="post-btn" onclick="toggleCommentsStatic(this)"><i class="far fa-comment"></i> Comment</button>
                        <button class="post-btn" onclick="openShareModalStatic()"><i class="fas fa-share"></i> Share</button>
                        <button class="post-btn btn-save" onclick="toggleSaveStatic(this)"><i class="far fa-bookmark"></i> Save</button>
                    </div>
                </div>

                <div class="post">
                    <div class="post-header">
                        <div class="post-author">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Author" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($full_name); ?> <i class="fas fa-check-circle verified"></i></h4>
                                <span>Dec 20 2024 · <i class="fas fa-map-marker-alt"></i> Sagarmatha</span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <p>Sagarmatha Giving me best feeling ever❄️</p>
                        <div class="post-image">
                            <img src="https://plus.unsplash.com/premium_photo-1661949391398-737f735067f2?w=600&h=600&fit=crop"  alt="Winter">
                        </div>
                    </div>
                    <div class="post-stats-line">
                        <div class="stats-left">
                            <span><i class="fas fa-heart" style="color: #ef4444;"></i> 1.1m likes</span>
                        </div>
                        <div class="stats-right">
                            <span>89k comments</span>
                            <span>·</span>
                            <span>45k shares</span>
                            <span>·</span>
                            <span><i class="fas fa-bookmark" style="color: #3b82f6; font-size: 0.75rem;"></i> 125k saves</span>
                        </div>
                    </div>
                    <div class="post-buttons">
                        <button class="post-btn btn-like" onclick="toggleLike(this)"><i class="far fa-heart"></i> Like</button>
                        <button class="post-btn" onclick="toggleCommentsStatic(this)"><i class="far fa-comment"></i> Comment</button>
                        <button class="post-btn" onclick="openShareModalStatic()"><i class="fas fa-share"></i> Share</button>
                        <button class="post-btn btn-save" onclick="toggleSaveStatic(this)"><i class="far fa-bookmark"></i> Save</button>
                    </div>
                </div>
                
                <div class="post">
                    <div class="post-header">
                        <div class="post-author">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Author" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($full_name); ?> <i class="fas fa-check-circle verified"></i></h4>
                                <span>January 22 · <i class="fas fa-map-marker-alt"></i> Srinagar</span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <p>Visiting in palpa ❄️ The Mountains everything look magical.</p>
                        <div class="post-image">
                            <img src="https://images.unsplash.com/photo-1773398348512-ed435cd0017e?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxmZWF0dXJlZC1waG90b3MtZmVlZHw5fHx8ZW58MHx8fHx8" alt="Winter">
                        </div>
                    </div>
                    <div class="post-stats-line">
                        <div class="stats-left">
                            <span><i class="fas fa-heart" style="color: #ef4444;"></i> 182k likes</span>
                        </div>
                        <div class="stats-right">
                            <span>8.2k comments</span>
                            <span>·</span>
                            <span>1.2k shares</span>
                            <span>·</span>
                            <span><i class="fas fa-bookmark" style="color: #3b82f6; font-size: 0.75rem;"></i> 15k saves</span>
                        </div>
                    </div>
                    <div class="post-buttons">
                        <button class="post-btn btn-like" onclick="toggleLike(this)"><i class="far fa-heart"></i> Like</button>
                        <button class="post-btn" onclick="toggleComments(<?php echo $post_id; ?>)">
                                <i class="far fa-comment"></i> Comment
                            </button>
                        <button class="post-btn" onclick="openShareModalStatic()"><i class="fas fa-share"></i> Share</button>
                        <button class="post-btn btn-save" onclick="toggleSaveStatic(this)"><i class="far fa-bookmark"></i> Save</button>
                    </div>
                </div>
            </div>
        </div>
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
            <button onclick="closeShareModal()" style="margin-top: 1.5rem; padding: 0.5rem 2rem; border: none; background: #e2e8f0; border-radius: 0.5rem; cursor: pointer;">Cancel</button>
        </div>
    </div>

    <script>
        let currentSharePostId = null;
        let autoLikeIntervals = {};

        // Like functionality - Real user likes only
        function toggleLike(btn) {
            const postId = btn.dataset.postId;
            
            // If no postId, it's a static post - just toggle visual
            if (!postId) {
                const icon = btn.querySelector('i');
                const textSpan = btn.querySelector('span');
                const isLiked = btn.classList.contains('liked');
                
                if (!isLiked) {
                    btn.classList.add('liked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    if (textSpan) textSpan.textContent = 'Liked';
                    icon.classList.add('like-animation');
                    setTimeout(() => icon.classList.remove('like-animation'), 500);
                } else {
                    btn.classList.remove('liked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    if (textSpan) textSpan.textContent = 'Like';
                }
                return;
            }
            
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');
            const isLiked = btn.classList.contains('liked');
            const action = isLiked ? 'unlike' : 'like';
            
            icon.classList.add('like-animation');
            setTimeout(() => icon.classList.remove('like-animation'), 500);
            
            fetch(`ajax_handler.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateLikeButton(btn, !isLiked, data.likes);
                }
            });
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

        // Save functionality - Saves to database and reflects in saved.php
        function toggleSave(btn) {
            const postId = btn.dataset.postId;
            if (!postId) return;
            
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');
            const isSaved = btn.classList.contains('saved');
            const action = isSaved ? 'unsave' : 'save';
            
            fetch(`ajax_handler.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (isSaved) {
                        btn.classList.remove('saved');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        if (textSpan) textSpan.textContent = 'Save';
                        showToast('Removed from saved posts', 'info');
                    } else {
                        btn.classList.add('saved');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        if (textSpan) textSpan.textContent = 'Saved';
                        showToast('Saved to your collection!', 'success');
                    }
                    // Update save count display
                    updateSaveCount(postId, data.save_count);
                }
            })
            .catch(err => console.error('Save error:', err));
        }

        // Static post save (visual only)
        function toggleSaveStatic(btn) {
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');
            const isSaved = btn.classList.contains('saved');
            
            if (!isSaved) {
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
        }

        function updateSaveCount(postId, count) {
            const saveCountSpan = document.querySelector(`.saves-count[data-post-id="${postId}"]`);
            if (saveCountSpan) {
                saveCountSpan.innerHTML = `<i class="fas fa-bookmark" style="color: #3b82f6; font-size: 0.75rem;"></i> ${count} saves`;
            }
        }

        // Comments functionality for database posts
        function toggleComments(postId) {
            const section = document.getElementById(`comments-${postId}`);
            const isVisible = section.style.display === 'block';
            section.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                loadComments(postId);
                document.getElementById(`comment-input-${postId}`).focus();
            }
        }

        function toggleCommentsStatic(btn) {
            const post = btn.closest('.post');
            const section = post.querySelector('.comments-section');
            if (section) {
                section.style.display = section.style.display === 'block' ? 'none' : 'block';
            }
        }

        function loadComments(postId) {
            fetch(`ajax_handler.php?action=get_comments&post_id=${postId}`)
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById(`comments-list-${postId}`);
                list.innerHTML = data.comments.length ? '' : '<p style="text-align:center;color:#94a3b8;">No comments yet</p>';
                data.comments.forEach(c => appendComment(list, c));
                const countSpan = document.querySelector(`.comments-count[data-post-id="${postId}"]`);
                if (countSpan) countSpan.textContent = data.count + ' comments';
            });
        }

        function handleCommentKeypress(e, postId) {
            if (e.key === 'Enter') submitComment(postId);
        }

        function submitComment(postId) {
            const input = document.getElementById(`comment-input-${postId}`);
            const content = input.value.trim();
            if (!content) return;
            
            fetch('ajax_handler.php?action=add_comment', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}&content=${encodeURIComponent(content)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    const list = document.getElementById(`comments-list-${postId}`);
                    if (list.children[0]?.tagName === 'P') list.innerHTML = '';
                    appendComment(list, data.comment, true);
                    const countSpan = document.querySelector(`.comments-count[data-post-id="${postId}"]`);
                    if (countSpan) {
                        const current = parseInt(countSpan.textContent) || 0;
                        countSpan.textContent = (current + 1) + ' comments';
                    }
                }
            });
        }

        function appendComment(container, comment, isNew) {
            const div = document.createElement('div');
            div.className = 'comment-item';
            if (isNew) div.style.animation = 'fadeIn 0.5s ease';
            div.innerHTML = `
                <img src="${comment.avatar || '<?php echo $user_avatar; ?>'}" class="comment-avatar">
                <div class="comment-content">
                    <div class="comment-author">${comment.author}</div>
                    <div class="comment-text">${comment.content}</div>
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

        function openShareModalStatic() {
            document.getElementById('shareModal').classList.add('active');
        }

        function closeShareModal(e) {
            if (!e || e.target.id === 'shareModal') {
                document.getElementById('shareModal').classList.remove('active');
                currentSharePostId = null;
            }
        }

        function shareTo(platform) {
            if (!currentSharePostId) {
                alert('Shared to ' + platform + '!');
                closeShareModal();
                return;
            }
            
            const url = `${window.location.origin}/post.php?id=${currentSharePostId}`;
            switch(platform) {
                case 'facebook': window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank'); break;
                case 'twitter': window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}`, '_blank'); break;
                case 'whatsapp': window.open(`https://wa.me/?text=${encodeURIComponent(url)}`, '_blank'); break;
                case 'copy': navigator.clipboard.writeText(url).then(() => alert('Link copied!')); break;
                case 'email': window.location.href = `mailto:?subject=Check this out&body=${encodeURIComponent(url)}`; break;
                case 'message': alert('Message feature coming soon!'); break;
            }
            
            fetch('ajax_handler.php?action=share', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${currentSharePostId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const countSpan = document.querySelector(`.shares-count[data-post-id="${currentSharePostId}"]`);
                    if (countSpan) countSpan.textContent = data.shares + ' shares';
                }
            });
            
            closeShareModal();
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                color: white;
                font-weight: 500;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                ${type === 'success' ? 'background: #22c55e;' : 'background: #3b82f6;'}
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

    
    // Save functionality - Saves to database and reflects in saved.php
    function toggleSave(btn) {
        const postId = btn.dataset.postId;
        if (!postId) return;
        
        const icon = btn.querySelector('i');
        const textSpan = btn.querySelector('span');
        const isSaved = btn.classList.contains('saved');
        const action = isSaved ? 'unsave' : 'save';
        
        fetch(`ajax_handler.php?action=${action}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `post_id=${postId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (isSaved) {
                    btn.classList.remove('saved');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    if (textSpan) textSpan.textContent = 'Save';
                    showToast('Removed from saved posts', 'info');
                } else {
                    btn.classList.add('saved');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    if (textSpan) textSpan.textContent = 'Saved';
                    showToast('Saved to your collection!', 'success');
                }
                // Update save count display
                if (data.save_count !== undefined) {
                    updateSaveCount(postId, data.save_count);
                } else {
                    fetchUpdatedSaveCount(postId);
                }
            }
        })
        .catch(err => console.error('Save error:', err));
    }

    function fetchUpdatedSaveCount(postId) {
        fetch(`ajax_handler.php?action=get_save_count&post_id=${postId}`)
            .then(res => res.json())
            .then(data => {
                if (data.save_count !== undefined) {
                    updateSaveCount(postId, data.save_count);
                }
            })
            .catch(err => console.error('Error fetching save count:', err));
    }

    function updateSaveCount(postId, count) {
        const saveCountSpan = document.querySelector(`.saves-count[data-post-id="${postId}"] .save-count-number`);
        if (saveCountSpan && count !== null && count !== undefined) {
            saveCountSpan.textContent = count;
        }
    }
    
    
        // Initialize auto-like intervals for visual effect only
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.post[data-post-id]').forEach(post => {
                const postId = post.dataset.postId;
                // Auto-increment for visual effect only (not affecting real likes)
                autoLikeIntervals[postId] = setInterval(() => {
                    const countSpan = document.querySelector(`.likes-count[data-post-id="${postId}"]`);
                    if (countSpan && Math.random() > 0.7) {
                        let text = countSpan.textContent;
                        let current = parseInt(text) || 0;
                        // Only update display, not actual database count
                        countSpan.textContent = text.replace(current, current + 1);
                    }
                }, 3000);
            });
        });

        // Add this function to check save status on page load
function checkSaveStatus(postId) {
    fetch(`ajax_handler.php?action=check_save&post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            if (data.saved) {
                const btn = document.querySelector(`.btn-save[data-post-id="${postId}"]`);
                if (btn) {
                    btn.classList.add('saved');
                    const icon = btn.querySelector('i');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    const textSpan = btn.querySelector('span');
                    if (textSpan) textSpan.textContent = 'Saved';
                }
            }
        })
        .catch(err => console.error('Check save error:', err));
}

// Initialize: Check save status for all posts on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.post[data-post-id]').forEach(post => {
        const postId = post.dataset.postId;
        checkSaveStatus(postId);
    });
});

        window.addEventListener('beforeunload', () => {
            Object.values(autoLikeIntervals).forEach(i => clearInterval(i));
        });

    </script>
</body>
</html>
