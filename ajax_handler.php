    <?php
    // Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
    session_start();
    require_once 'db.php';
    header('Content-Type: application/json');

    // Enable error reporting for debugging (REMOVE IN PRODUCTION)
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Don't display to browser, but log them

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated', 'success' => false]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    // Log all requests for debugging
    error_log("AJAX Request - Action: $action, User: $user_id, POST: " . print_r($_POST, true));

    // Helper functions
    function logUserActivity($conn, $user_id, $activity_type, $reference_id = null, $reference_type = null, $details = '') {
        try {
            $stmt = $conn->prepare("SELECT activity_table_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result && $result['activity_table_name']) {
                $tableName = $result['activity_table_name'];
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                
                $logStmt = $conn->prepare("INSERT INTO `{$tableName}` (activity_type, reference_id, reference_type, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $logStmt->bind_param("sissss", $activity_type, $reference_id, $reference_type, $details, $ip, $userAgent);
                $logStmt->execute();
            }
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    function createNotification($conn, $recipient_id, $sender_id, $type, $post_id = null, $comment_id = null, $message = '') {
        try {
            $stmt = $conn->prepare("INSERT INTO notifications (recipient_id, sender_id, type, post_id, comment_id, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
            $stmt->bind_param("iisiis", $recipient_id, $sender_id, $type, $post_id, $comment_id, $message);
            $stmt->execute();
            return $stmt->insert_id;
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }

    function time_elapsed_string($datetime) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->y > 0) return $diff->y . 'y ago';
        if ($diff->m > 0) return $diff->m . 'mo ago';
        if ($diff->d > 0) return $diff->d . 'd ago';
        if ($diff->h > 0) return $diff->h . 'h ago';
        if ($diff->i > 0) return $diff->i . 'm ago';
        return 'just now';
    }

    // ============================================
    // MAIN SWITCH - ALL CASES MUST BE INSIDE
    // ============================================
    switch ($action) {
        case 'like':
            handleLike($conn, $user_id);
            break;
        case 'unlike':
            handleUnlike($conn, $user_id);
            break;
        case 'check_like':
            checkLike($conn, $user_id);
            break;
            
        // SAVE CASES - CRITICAL PART
        case 'save':
            handleSave($conn, $user_id);
            break;
        case 'unsave':
            handleUnsave($conn, $user_id);
            break;
        case 'check_save':
            checkSave($conn, $user_id);
            break;
        case 'get_save_count':
            getSaveCount($conn);
            break;
        
        case 'delete_post':
            handleDeletePost($conn, $user_id);
            break;
            
        case 'add_comment':
            addComment($conn, $user_id);
            break;
        case 'get_comments':
            getComments($conn, intval($_GET['post_id'] ?? 0));
            break;
        case 'share':
            handleShare($conn, $user_id);
            break;
        case 'follow':
            handleFollow($conn, $user_id);
            break;
        case 'unfollow':
            handleUnfollow($conn, $user_id);
            break;
        case 'check_follow':
            checkFollow($conn, $user_id);
            break;
        case 'search_users':
            searchUsers($conn, $user_id);
            break;
        case 'get_notifications':
            getNotifications($conn, $user_id);
            break;
        case 'mark_notification_read':
            markNotificationRead($conn, $user_id);
            break;
        case 'mark_all_notifications_read':
            markAllNotificationsRead($conn, $user_id);
            break;
        case 'get_unread_count':
            getUnreadCount($conn, $user_id);
            break;
        case 'send_message':
            sendMessage($conn, $user_id);
            break;
        case 'get_messages':
            getMessages($conn, $user_id, intval($_GET['other_user_id'] ?? 0));
            break;
        case 'get_user_activity':
            getUserActivity($conn, $user_id);
            break;
            
        default:
            error_log("Invalid action received: $action");
            echo json_encode(['error' => 'Invalid action: ' . $action, 'success' => false]);
            break;
    }

    // ============================================
    // SAVE FUNCTIONS - FIXED AND TESTED
    // ============================================
    function handleSave($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        error_log("handleSave called: post_id=$post_id, user_id=$user_id");
        
        if ($post_id === 0) {
            error_log("Save failed: Invalid post_id");
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }
        
        // Check if already saved
        $check = $conn->prepare("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?");
        if (!$check) {
            error_log("Save check prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            return;
        }
        
        $check->bind_param("ii", $post_id, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            error_log("Save failed: Already saved post_id=$post_id by user_id=$user_id");
            echo json_encode(['success' => false, 'message' => 'Already saved']);
            return;
        }
        
        // Insert save
        $stmt = $conn->prepare("INSERT INTO saved_posts (post_id, user_id, created_at) VALUES (?, ?, NOW())");
        if (!$stmt) {
            error_log("Save insert prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param("ii", $post_id, $user_id);
        
        if ($stmt->execute()) {
            error_log("Save SUCCESS: post_id=$post_id, user_id=$user_id");
            
            // Get updated save count
            $countStmt = $conn->prepare("SELECT COUNT(*) as save_count FROM saved_posts WHERE post_id = ?");
            $countStmt->bind_param("i", $post_id);
            $countStmt->execute();
            $save_count = $countStmt->get_result()->fetch_assoc()['save_count'] ?? 0;
            
            logUserActivity($conn, $user_id, 'save', $post_id, 'post', 'Saved post #' . $post_id);
            
            echo json_encode([
                'success' => true, 
                'saved' => true,
                'save_count' => $save_count,
                'message' => 'Post saved successfully'
            ]);
        } else {
            error_log("Save execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $stmt->error]);
        }
    }

    function handleUnsave($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        error_log("handleUnsave called: post_id=$post_id, user_id=$user_id");
        
        $stmt = $conn->prepare("DELETE FROM saved_posts WHERE post_id = ? AND user_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            return;
        }
        
        $stmt->bind_param("ii", $post_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            error_log("Unsave SUCCESS: post_id=$post_id");
            
            // Get updated save count
            $countStmt = $conn->prepare("SELECT COUNT(*) as save_count FROM saved_posts WHERE post_id = ?");
            $countStmt->bind_param("i", $post_id);
            $countStmt->execute();
            $save_count = $countStmt->get_result()->fetch_assoc()['save_count'] ?? 0;
            
            logUserActivity($conn, $user_id, 'unsave', $post_id, 'post', 'Unsaved post #' . $post_id);
            
            echo json_encode([
                'success' => true, 
                'saved' => false,
                'save_count' => $save_count
            ]);
        } else {
            error_log("Unsave failed or not found: post_id=$post_id");
            echo json_encode(['success' => false, 'message' => 'Not saved or already removed']);
        }
    }

    function checkSave($conn, $user_id) {
        $post_id = intval($_GET['post_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['saved' => $stmt->get_result()->num_rows > 0]);
    }

    function getSaveCount($conn) {
        $post_id = intval($_GET['post_id'] ?? 0);
        
        if ($post_id === 0) {
            echo json_encode(['save_count' => 0]);
            return;
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as save_count FROM saved_posts WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['save_count' => $result['save_count'] ?? 0]);
    }

    // ============================================
    // LIKE FUNCTIONS
    // ============================================
    function handleLike($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }
        
        $check = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $check->bind_param("ii", $post_id, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Already liked']);
            return;
        }
        
        $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $ownerStmt->bind_param("i", $post_id);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result()->fetch_assoc();
        $post_owner_id = $ownerResult['user_id'] ?? 0;
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
            
            $update = $conn->prepare("UPDATE posts SET likes_count = likes_count WHERE id = ?");
            $update->bind_param("i", $post_id);
            $update->execute();
            
            $result = $conn->query("SELECT likes_count FROM posts WHERE id = $post_id");
            $count = $result->fetch_assoc()['likes_count'] ?? 0;
            
            if ($post_owner_id && $post_owner_id != $user_id) {
                $sender = $conn->query("SELECT full_name FROM users WHERE id = $user_id")->fetch_assoc();
                $message = $sender['full_name'] . ' liked your post';
                createNotification($conn, $post_owner_id, $user_id, 'like', $post_id, null, $message);
            }
            
            logUserActivity($conn, $user_id, 'like', $post_id, 'post', 'Liked post #' . $post_id);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'likes' => $count, 
                'liked' => true,
                'message' => 'Liked successfully'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    function handleUnlike($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }
        
        $check = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $check->bind_param("ii", $post_id, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Not liked yet']);
            return;
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
            
            $update = $conn->prepare("UPDATE posts SET likes_count = GREATEST(likes_count, 0) WHERE id = ?");
            $update->bind_param("i", $post_id);
            $update->execute();
            
            $result = $conn->query("SELECT likes_count FROM posts WHERE id = $post_id");
            $count = $result->fetch_assoc()['likes_count'] ?? 0;
            
            logUserActivity($conn, $user_id, 'unlike', $post_id, 'post', 'Unliked post #' . $post_id);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'likes' => $count, 
                'liked' => false,
                'message' => 'Unliked successfully'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    function checkLike($conn, $user_id) {
        $post_id = intval($_GET['post_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['liked' => $stmt->get_result()->num_rows > 0]);
    }

    // ============================================
    // COMMENT FUNCTIONS
    // ============================================
    function addComment($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        
        if ($post_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }
        
        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Empty comment']);
            return;
        }
        
        $ownerStmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $ownerStmt->bind_param("i", $post_id);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result()->fetch_assoc();
        $post_owner_id = $ownerResult['user_id'] ?? 0;
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $post_id, $user_id, $content);
            $stmt->execute();
            
            $comment_id = $stmt->insert_id;
            
            $update = $conn->prepare("UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?");
            $update->bind_param("i", $post_id);
            $update->execute();
            
            $user = $conn->query("SELECT full_name, avatar FROM users WHERE id = $user_id")->fetch_assoc();
            
            if ($post_owner_id && $post_owner_id != $user_id) {
                $message = $user['full_name'] . ' commented: ' . substr($content, 0, 50) . (strlen($content) > 50 ? '...' : '');
                createNotification($conn, $post_owner_id, $user_id, 'comment', $post_id, $comment_id, $message);
            }
            
            logUserActivity($conn, $user_id, 'comment', $comment_id, 'comment', 'Commented on post #' . $post_id);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'comment_id' => $comment_id,
                'comment' => [
                    'id' => $comment_id,
                    'content' => htmlspecialchars($content),
                    'author' => $user['full_name'],
                    'avatar' => $user['avatar'],
                    'time' => 'just now'
                ],
                'message' => 'Comment added successfully'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    function getComments($conn, $post_id) {
        $post_id = intval($post_id);
        
        if ($post_id === 0) {
            echo json_encode(['comments' => [], 'count' => 0]);
            return;
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, u.full_name, u.avatar 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = [
                'id' => $row['id'],
                'content' => htmlspecialchars($row['content']),
                'author' => $row['full_name'],
                'avatar' => $row['avatar'],
                'time' => time_elapsed_string($row['created_at'])
            ];
        }
        
        $count_result = $conn->query("SELECT comments_count FROM posts WHERE id = $post_id");
        $count = $count_result->fetch_assoc()['comments_count'] ?? count($comments);
        
        echo json_encode(['comments' => $comments, 'count' => $count]);
    }

    // ============================================
    // SHARE FUNCTIONS
    // ============================================
    function handleShare($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }
        
        $check = $conn->prepare("SELECT id FROM shares WHERE post_id = ? AND user_id = ?");
        $check->bind_param("ii", $post_id, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO shares (post_id, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
            
            $update = $conn->prepare("UPDATE posts SET shares_count = shares_count + 1 WHERE id = ?");
            $update->bind_param("i", $post_id);
            $update->execute();
            
            logUserActivity($conn, $user_id, 'share', $post_id, 'post', 'Shared post #' . $post_id);
        }
        
        $result = $conn->query("SELECT shares_count FROM posts WHERE id = $post_id");
        $count = $result->fetch_assoc()['shares_count'] ?? 0;
        
        echo json_encode(['success' => true, 'shares' => $count]);
    }

    // ============================================
    // FOLLOW FUNCTIONS
    // ============================================
    function handleFollow($conn, $user_id) {
        $following_id = intval($_POST['user_id'] ?? 0);
        
        if ($following_id === 0 || $following_id === $user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            return;
        }
        
        $check = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $check->bind_param("ii", $user_id, $following_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Already following']);
            return;
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $following_id);
            $stmt->execute();
            
            $sender = $conn->query("SELECT full_name FROM users WHERE id = $user_id")->fetch_assoc();
            $message = $sender['full_name'] . ' started following you';
            createNotification($conn, $following_id, $user_id, 'follow', null, null, $message);
            
            logUserActivity($conn, $user_id, 'follow', $following_id, 'user', 'Followed user #' . $following_id);
            
            $conn->commit();
            
            echo json_encode(['success' => true, 'following' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    function handleUnfollow($conn, $user_id) {
        $following_id = intval($_POST['user_id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $user_id, $following_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            logUserActivity($conn, $user_id, 'unfollow', $following_id, 'user', 'Unfollowed user #' . $following_id);
            echo json_encode(['success' => true, 'following' => false]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    function checkFollow($conn, $user_id) {
        $following_id = intval($_GET['user_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $user_id, $following_id);
        $stmt->execute();
        
        echo json_encode(['following' => $stmt->get_result()->num_rows > 0]);
    }

    // ============================================
    // SEARCH FUNCTIONS
    // ============================================
    function searchUsers($conn, $user_id) {
        $query = trim($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            echo json_encode(['users' => []]);
            return;
        }
        
        $search = "%{$query}%";
        $stmt = $conn->prepare("
            SELECT id, username, full_name, avatar, is_verified,
            (SELECT COUNT(*) FROM followers WHERE following_id = u.id) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = u.id) as is_following
            FROM users u
            WHERE (username LIKE ? OR full_name LIKE ?) AND id != ?
            LIMIT 20
        ");
        $stmt->bind_param("issi", $user_id, $search, $search, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'full_name' => $row['full_name'],
                'avatar' => $row['avatar'],
                'is_verified' => (bool)$row['is_verified'],
                'followers_count' => $row['followers_count'],
                'is_following' => (bool)$row['is_following']
            ];
        }
        
        echo json_encode(['users' => $users, 'count' => count($users)]);
    }

    // ============================================
    // NOTIFICATION FUNCTIONS
    // ============================================
    function getNotifications($conn, $user_id) {
        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);
        
        $stmt = $conn->prepare("
            SELECT n.*, 
                u.username, u.full_name, u.avatar,
                p.image as post_image
            FROM notifications n
            JOIN users u ON n.sender_id = u.id
            LEFT JOIN posts p ON n.post_id = p.id
            WHERE n.recipient_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'message' => $row['message'],
                'is_read' => (bool)$row['is_read'],
                'created_at' => $row['created_at'],
                'time_ago' => time_elapsed_string($row['created_at']),
                'sender' => [
                    'id' => $row['sender_id'],
                    'username' => $row['username'],
                    'full_name' => $row['full_name'],
                    'avatar' => $row['avatar']
                ],
                'post_id' => $row['post_id'],
                'post_image' => $row['post_image'],
                'comment_id' => $row['comment_id']
            ];
        }
        
        $unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND is_read = 0");
        $unreadStmt->bind_param("i", $user_id);
        $unreadStmt->execute();
        $unreadCount = $unreadStmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total_count' => count($notifications)
        ]);
    }

    function markNotificationRead($conn, $user_id) {
        $notification_id = intval($_POST['notification_id'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    }

    function markAllNotificationsRead($conn, $user_id) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'marked_count' => $stmt->affected_rows]);
    }

    function getUnreadCount($conn, $user_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode(['unread_count' => $count]);
    }

    // ============================================
    // MESSAGE FUNCTIONS
    // ============================================
    function sendMessage($conn, $user_id) {
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        
        if ($receiver_id === 0 || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $receiver_id, $content);
        
        if ($stmt->execute()) {
            $message_id = $stmt->insert_id;
            
            $user = $conn->query("SELECT full_name, avatar FROM users WHERE id = $user_id")->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message_id' => $message_id,
                'message' => [
                    'id' => $message_id,
                    'content' => htmlspecialchars($content),
                    'sender' => $user['full_name'],
                    'avatar' => $user['avatar'],
                    'time' => date('g:i A'),
                    'is_sent' => true
                ]
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    function getMessages($conn, $user_id, $other_user_id) {
        $other_user_id = intval($other_user_id);
        
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name, u.avatar 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
            OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'id' => $row['id'],
                'content' => htmlspecialchars($row['content']),
                'sender' => $row['full_name'],
                'avatar' => $row['avatar'],
                'time' => date('g:i A', strtotime($row['created_at'])),
                'is_sent' => $row['sender_id'] == $user_id
            ];
        }
        
        echo json_encode(['messages' => $messages]);
    }

    // ============================================
    // ACTIVITY FUNCTIONS
    // ============================================
    function getUserActivity($conn, $user_id) {
        try {
            $stmt = $conn->prepare("SELECT activity_table_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result || !$result['activity_table_name']) {
                echo json_encode(['activities' => []]);
                return;
            }
            
            $tableName = $result['activity_table_name'];
            
            $activityStmt = $conn->prepare("SELECT * FROM `{$tableName}` ORDER BY created_at DESC LIMIT 50");
            $activityStmt->execute();
            $activities = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['activities' => $activities]);
        } catch (Exception $e) {
            echo json_encode(['activities' => [], 'error' => $e->getMessage()]);
        }
    }

      // ============================================
    // DELETE POST FUNCTION
    // ============================================
    function handleDeletePost($conn, $user_id) {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }
        
        // Verify post belongs to current user
        $check = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $check->bind_param("i", $post_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if (!$result || $result['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Delete related records first
        $conn->query("DELETE FROM likes WHERE post_id = $post_id");
        $conn->query("DELETE FROM comments WHERE post_id = $post_id");
        $conn->query("DELETE FROM shares WHERE post_id = $post_id");
        $conn->query("DELETE FROM saved_posts WHERE post_id = $post_id");
        $conn->query("DELETE FROM notifications WHERE post_id = $post_id");
        
        // Delete the post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            logUserActivity($conn, $user_id, 'delete_post', $post_id, 'post', 'Deleted post #' . $post_id);
            echo json_encode(['success' => true, 'message' => 'Post deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete']);
        }
    }
    ?>