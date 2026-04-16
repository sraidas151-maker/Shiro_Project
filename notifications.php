<?php
session_start();
require_once 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'user';
$full_name = $_SESSION['full_name'] ?? 'User';
$user_avatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face';

// Mark all as read when page loads (optional - can be removed if you want manual marking)
// $conn->query("UPDATE notifications SET is_read = 1 WHERE recipient_id = $user_id AND is_read = 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications · Shiro</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional Notification Styles */
        .notifications-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .notifications-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .btn-mark-all {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-mark-all:hover {
            background: #2563eb;
        }
        
        .notifications-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .tab-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 9999px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .tab-btn.active {
            background: #3b82f6;
            color: white;
        }
        
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
            cursor: pointer;
            position: relative;
        }
        
        .notification-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .notification-item.unread {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
        }
        
        .notification-avatar {
            position: relative;
            flex-shrink: 0;
        }
        
        .notification-avatar img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notification-icon {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.625rem;
            color: white;
            border: 2px solid white;
        }
        
        .notification-icon.like { background: #ef4444; }
        .notification-icon.comment { background: #3b82f6; }
        .notification-icon.follow { background: #10b981; }
        .notification-icon.mention { background: #8b5cf6; }
        .notification-icon.share { background: #f59e0b; }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-text {
            color: #334155;
            font-size: 0.9375rem;
            line-height: 1.5;
            margin-bottom: 0.25rem;
        }
        
        .notification-text strong {
            color: #1e293b;
            font-weight: 600;
        }
        
        .notification-time {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        .notification-preview {
            width: 44px;
            height: 44px;
            border-radius: 0.5rem;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-follow-back {
            padding: 0.375rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-follow-back:hover {
            background: #2563eb;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }
        
        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        
        /* Real-time notification indicator */
        .nav-link-notifications {
            position: relative;
        }
        
        .notification-dot {
            position: absolute;
            top: 0;
            right: 0;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.1); }
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
                <input type="text" id="searchInput" placeholder="Search users..." autocomplete="off">
                <div id="searchResults" class="search-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:white;border-radius:0.5rem;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;max-height:300px;overflow-y:auto;"></div>
            </div>

            <ul class="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i></a></li>
                <li><a href="explore.php"><i class="fas fa-compass"></i></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i></a></li>
                <li>
                    <a href="notifications.php" class="active nav-link-notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot" id="navNotificationDot" style="display:none;"></span>
                    </a>
                </li>
                <li><a href="profile.php"><i class="fas fa-user"></i></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i></a></li>
            </ul>

            <div class="nav-actions">
                <a href="upload.php"><button class="btn-create"><i class="fas fa-plus"></i> Create</button></a>
                <a href="profile.php">
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User" class="user-avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                </a>               
            </div>
        </div>
    </nav>

    <div class="notifications-container">
        <div class="notifications-header">
            <h1>Notifications</h1>
            <button class="btn-mark-all" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i> Mark all as read
            </button>
        </div>

        <div class="notifications-tabs">
            <button class="tab-btn active" onclick="filterNotifications('all')">All</button>
            <button class="tab-btn" onclick="filterNotifications('like')">Likes</button>
            <button class="tab-btn" onclick="filterNotifications('comment')">Comments</button>
            <button class="tab-btn" onclick="filterNotifications('follow')">Follows</button>
            <button class="tab-btn" onclick="filterNotifications('mention')">Mentions</button>
        </div>

        <div class="notifications-list" id="notificationsList">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Loading notifications...
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Global variables
        let allNotifications = [];
        let currentFilter = 'all';
        let unreadCount = 0;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            updateUnreadCount();
            
            // Poll for new notifications every 30 seconds
            setInterval(() => {
                updateUnreadCount();
                if (currentFilter === 'all') {
                    loadNotifications();
                }
            }, 30000);
            
            // Setup search
            setupSearch();
        });

        // Load notifications
        function loadNotifications() {
            fetch('ajax_handler.php?action=get_notifications&limit=50')
            .then(res => res.json())
            .then(data => {
                if (data.notifications) {
                    allNotifications = data.notifications;
                    unreadCount = data.unread_count;
                    renderNotifications();
                    updateNavBadge();
                }
            })
            .catch(err => console.error('Error loading notifications:', err));
        }

        // Render notifications
        function renderNotifications() {
            const container = document.getElementById('notificationsList');
            
            let filtered = allNotifications;
            if (currentFilter !== 'all') {
                filtered = allNotifications.filter(n => n.type === currentFilter);
            }
            
            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications</h3>
                        <p>You're all caught up!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filtered.map(notification => createNotificationHTML(notification)).join('');
        }

        // Create notification HTML
        function createNotificationHTML(n) {
            const iconMap = {
                'like': 'fa-heart',
                'comment': 'fa-comment',
                'follow': 'fa-user-plus',
                'mention': 'fa-at',
                'share': 'fa-share'
            };
            
            const iconClass = iconMap[n.type] || 'fa-bell';
            
            let actionButton = '';
            if (n.type === 'follow') {
                actionButton = `<button class="btn-follow-back" onclick="followBack(${n.sender.id}, this)">Follow back</button>`;
            }
            
            let previewImage = '';
            if (n.post_image) {
                previewImage = `<img src="${n.post_image}" alt="Post" class="notification-preview">`;
            }
            
            return `
                <div class="notification-item ${n.is_read ? '' : 'unread'}" 
                     data-id="${n.id}"
                     onclick="handleNotificationClick(${n.id}, '${n.type}', ${n.post_id || 'null'})">
                    <div class="notification-avatar">
                        <img src="${n.sender.avatar}" alt="${n.sender.full_name}">
                        <div class="notification-icon ${n.type}">
                            <i class="fas ${iconClass}"></i>
                        </div>
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <strong>${n.sender.full_name}</strong> ${n.message.replace(n.sender.full_name, '')}
                        </div>
                        <div class="notification-time">${n.time_ago}</div>
                    </div>
                    ${previewImage}
                    ${actionButton}
                </div>
            `;
        }

        // Handle notification click
        function handleNotificationClick(notificationId, type, postId) {
            // Mark as read
            markAsRead(notificationId);
            
            // Navigate based on type
            if (postId) {
                window.location.href = `profile.php?post_id=${postId}`;
            } else if (type === 'follow') {
                window.location.href = `profile.php?user_id=${notificationId}`;
            }
        }

        // Mark single notification as read
        function markAsRead(notificationId) {
            fetch('ajax_handler.php?action=mark_notification_read', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `notification_id=${notificationId}`
            })
            .then(() => {
                const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                updateUnreadCount();
            });
        }

        // Mark all as read
        function markAllAsRead() {
            fetch('ajax_handler.php?action=mark_all_notifications_read', {
                method: 'POST'
            })
            .then(() => {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                updateUnreadCount();
            });
        }

        // Filter notifications
        function filterNotifications(type) {
            currentFilter = type;
            
            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase().includes(type) || (type === 'all' && btn.textContent === 'All')) {
                    btn.classList.add('active');
                }
            });
            
            renderNotifications();
        }

        // Update unread count
        function updateUnreadCount() {
            fetch('ajax_handler.php?action=get_unread_count')
            .then(res => res.json())
            .then(data => {
                unreadCount = data.unread_count;
                updateNavBadge();
            });
        }

        // Update navigation badge
        function updateNavBadge() {
            const dot = document.getElementById('navNotificationDot');
            if (dot) {
                dot.style.display = unreadCount > 0 ? 'block' : 'none';
            }
        }

        // Follow back
        function followBack(userId, btn) {
            fetch('ajax_handler.php?action=follow', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `user_id=${userId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.textContent = 'Following';
                    btn.disabled = true;
                    btn.style.background = '#10b981';
                }
            });
        }

        // Search functionality
        function setupSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    fetch(`ajax_handler.php?action=search_users&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.users && data.users.length > 0) {
                            searchResults.innerHTML = data.users.map(user => `
                                <div class="search-result-item" 
                                     style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;cursor:pointer;border-bottom:1px solid #f1f5f9;"
                                     onclick="window.location.href='profile.php?user_id=${user.id}'">
                                    <img src="${user.avatar}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <div style="flex:1;">
                                        <div style="font-weight:600;color:#1e293b;">${user.full_name}</div>
                                        <div style="font-size:0.875rem;color:#64748b;">@${user.username}</div>
                                    </div>
                                    ${user.is_verified ? '<i class="fas fa-check-circle" style="color:#3b82f6;"></i>' : ''}
                                </div>
                            `).join('');
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div style="padding:1rem;text-align:center;color:#94a3b8;">No users found</div>';
                            searchResults.style.display = 'block';
                        }
                    });
                }, 300);
            });
            
            // Close search when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-search')) {
                    searchResults.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>