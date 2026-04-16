<?php
session_start();
require_once 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Users data with their personalities/topics
$users = [
    [
        'id' => 1,
        'name' => 'Alex Chen',
        'username' => 'alexchen',
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop',
        'status' => 'online',
        'topic' => 'photography',
        'replies' => [
            'photography' => ['Wow, that composition! 📸 Try golden hour next time', 'Use rule of thirds for better framing', 'Your ISO settings look perfect!', 'That bokeh effect is stunning ✨', 'Try shooting from lower angle next time'],
            'travel' => ['Which camera did you take this with?', 'Perfect travel shot! 🌍', 'The lighting here is magical', 'Did you use a tripod for this?', 'Frame this one! 🔥'],
            'general' => ['Thanks for sharing! 📷', 'Looking great!', 'Can\'t wait to see more', 'This is beautiful!', 'Amazing shot! 👏']
        ]
    ],
    [
        'id' => 2,
        'name' => 'Sarah Mitchell',
        'username' => 'sarahmitch',
        'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop',
        'status' => 'online',
        'topic' => 'travel',
        'replies' => [
            'travel' => ['That place looks amazing! 🗺️', 'Adding this to my bucket list!', 'How was the weather there?', 'Travel goals! ✈️', 'Which hotel did you stay at?'],
            'food' => ['Yummy! 😋 Did you try local cuisine?', 'Food looks delicious!', 'I need this recipe', 'Perfect food shot! 🍜', 'Where is this restaurant?'],
            'general' => ['Miss you! 💕', 'Let\'s meet soon', 'How have you been?', 'Great to hear from you!', 'Talk later! 👋']
        ]
    ],
    [
        'id' => 3,
        'name' => 'James Wilson',
        'username' => 'jamesw',
        'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop',
        'status' => 'offline',
        'topic' => 'tech',
        'replies' => [
            'tech' => ['Nice setup! 💻', 'Which lens is this?', 'Try this editing software', 'Great color grading!', 'Professional work! 👨‍💻'],
            'work' => ['Files received 📁', 'Working on it now', 'Will send update soon', 'Project looks good!', 'Deadline is Friday'],
            'general' => ['Got it, thanks!', 'Sounds good', 'Let me know', 'Okay 👍', 'Sure thing']
        ]
    ],
    [
        'id' => 4,
        'name' => 'Emma Lewis',
        'username' => 'emmalew',
        'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&h=150&fit=crop',
        'status' => 'online',
        'topic' => 'lifestyle',
        'replies' => [
            'lifestyle' => ['So aesthetic! ✨', 'Love your vibe', 'Goals! 💅', 'This is everything!', 'Living your best life! 🌸'],
            'shopping' => ['Where did you get this?', 'Need this in my life! 🛍️', 'So cute!', 'Adding to cart now', 'Style inspo! 👗'],
            'general' => ['Thanks babe! 💖', 'You too!', 'See you soon!', 'XOXO', 'Bye! 👋']
        ]
    ]
];

// Get selected user ID from URL
$selected_user_id = isset($_GET['user']) ? intval($_GET['user']) : 1;
$selected_user = null;

foreach ($users as $user) {
    if ($user['id'] === $selected_user_id) {
        $selected_user = $user;
        break;
    }
}

// If no user found, default to first
if (!$selected_user) {
    $selected_user = $users[0];
    $selected_user_id = 1;
}

// Generate last message time for each user
$last_messages = [
    '2m ago',
    '1h ago', 
    '3h ago',
    '1d ago'
];

// Generate preview messages
$previews = [
    'That sounds amazing! Can\'t wait to see the photos 📸',
    'Are we still meeting tomorrow?',
    'Sent you the files you requested',
    'Thanks for the recommendation!'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages · Shiro</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/messages.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SIRF ZAROORI CSS ADD KIYA -->
    <style>
        .conversation { cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; display: block; }
        .conversation:hover { background: #f1f5f9; }
        .conversation.active { background: #eff6ff; border-left: 3px solid #3b82f6; }
        .typing-indicator { display: none; padding: 10px 20px; font-size: 0.875rem; color: #64748b; font-style: italic; }
        .typing-indicator.show { display: block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; } }
        .message.sent { animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        .message.received { animation: slideInLeft 0.3s ease; }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .chat-messages { scroll-behavior: smooth; }
        .btn-attach, .btn-emoji, .btn-send { cursor: pointer; transition: all 0.2s; }
        .btn-send:hover { transform: scale(1.1); color: #3b82f6; }
        .btn-send.sending { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
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
                <li><a href="messages.php" class="active"><i class="fas fa-envelope"></i></a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>

            <div class="nav-actions">
                <a href="upload.php"><button class="btn-create"><i class="fas fa-plus"></i> Create</button></a>
                <a href="profile.php">
                    <div class="user-menu">
                        <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>" alt="User" class="user-avatar">
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <div class="messages-container">
        <!-- Conversations List -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h2>Messages</h2>
                <button class="btn-new-msg"><i class="fas fa-edit"></i></button>
            </div>
            
            <div class="conversations-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search messages...">
            </div>

            <div class="conversations-list">
                <?php foreach ($users as $index => $user): ?>
                <a href="?user=<?php echo $user['id']; ?>" class="conversation <?php echo $user['id'] === $selected_user_id ? 'active' : ''; ?>" data-user-id="<?php echo $user['id']; ?>">
                    <div class="conversation-avatar">
                        <img src="<?php echo $user['avatar']; ?>" alt="<?php echo $user['name']; ?>">
                        <span class="status <?php echo $user['status']; ?>"></span>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-header">
                            <h4><?php echo $user['name']; ?></h4>
                            <span class="time"><?php echo $last_messages[$index]; ?></span>
                        </div>
                        <p><?php echo $previews[$index]; ?></p>
                        <?php if ($index === 1): ?>
                        <span class="unread-badge">2</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header">
                <div class="chat-user">
                    <img src="<?php echo $selected_user['avatar']; ?>" alt="<?php echo $selected_user['name']; ?>">
                    <div class="chat-user-info">
                        <h4><?php echo $selected_user['name']; ?></h4>
                        <span class="status-text"><?php echo $selected_user['status'] === 'online' ? 'Active now' : 'Offline'; ?></span>
                    </div>
                </div>
                <div class="chat-actions">
                    <button><i class="fas fa-phone"></i></button>
                    <button><i class="fas fa-video"></i></button>
                    <button><i class="fas fa-info-circle"></i></button>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="message-date">Today</div>
                
                <!-- Default messages based on user -->
                <div class="message received" id="default-msg-1">
                    <img src="<?php echo $selected_user['avatar']; ?>" alt="<?php echo $selected_user['name']; ?>" class="message-avatar">
                    <div class="message-content">
                        <p id="default-text-1">Hey! How are you doing today? 😊</p>
                        <span class="message-time">10:30 AM</span>
                    </div>
                </div>

                <div class="message received" id="default-msg-2">
                    <img src="<?php echo $selected_user['avatar']; ?>" alt="<?php echo $selected_user['name']; ?>" class="message-avatar">
                    <div class="message-content">
                        <p id="default-text-2"><?php echo $selected_user['topic'] === 'photography' ? 'Saw your recent shots, they look amazing!' : ($selected_user['topic'] === 'travel' ? 'Planning any trips soon? ✈️' : 'Working on anything exciting?'); ?></p>
                        <span class="message-time">10:32 AM</span>
                    </div>
                </div>
                
                <!-- Typing indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <?php echo $selected_user['name']; ?> is typing...
                </div>
            </div>

            <div class="chat-input">
                <button class="btn-attach"><i class="fas fa-plus-circle"></i></button>
                <div class="input-container">
                    <input type="text" id="messageInput" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                    <button class="btn-emoji"><i class="far fa-smile"></i></button>
                </div>
                <button class="btn-send" id="sendBtn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    
    <!-- SIRF ZAROORI JAVASCRIPT ADD KIYA -->
    <script>
        // User data from PHP
        const currentUser = {
            id: <?php echo $current_user_id; ?>,
            name: '<?php echo $_SESSION['full_name'] ?? 'You'; ?>',
            avatar: '<?php echo $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face'; ?>'
        };
        
        const selectedUser = {
            id: <?php echo $selected_user['id']; ?>,
            name: '<?php echo $selected_user['name']; ?>',
            avatar: '<?php echo $selected_user['avatar']; ?>',
            topic: '<?php echo $selected_user['topic']; ?>',
            replies: <?php echo json_encode($selected_user['replies']); ?>
        };
        
        let messageCount = 0;
        let conversationHistory = [];

        function handleKeyPress(e) {
            if (e.key === 'Enter') sendMessage();
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const content = input.value.trim();
            
            if (!content) return;
            
            // Add sent message
            addMessage(content, 'sent');
            input.value = '';
            
            // Save to history for context
            conversationHistory.push({ type: 'user', text: content });
            
            // Show typing indicator
            showTyping();
            
            // Generate contextual reply after delay
            setTimeout(() => {
                hideTyping();
                const reply = generateReply(content);
                addMessage(reply, 'received');
                conversationHistory.push({ type: 'bot', text: reply });
            }, 1500 + Math.random() * 1000); // Random delay 1.5-2.5s
        }

        function addMessage(text, type) {
            const container = document.getElementById('chatMessages');
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const div = document.createElement('div');
            div.className = `message ${type}`;
            
            if (type === 'sent') {
                div.innerHTML = `
                    <div class="message-content">
                        <p>${escapeHtml(text)}</p>
                        <span class="message-time">${time}</span>
                    </div>
                `;
            } else {
                div.innerHTML = `
                    <img src="${selectedUser.avatar}" alt="${selectedUser.name}" class="message-avatar">
                    <div class="message-content">
                        <p>${escapeHtml(text)}</p>
                        <span class="message-time">${time}</span>
                    </div>
                `;
            }
            
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            messageCount++;
        }

        function showTyping() {
            document.getElementById('typingIndicator').classList.add('show');
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        }

        function hideTyping() {
            document.getElementById('typingIndicator').classList.remove('show');
        }

        function generateReply(userMessage) {
            const msg = userMessage.toLowerCase();
            let category = 'general';
            
            // Detect topic from user message
            if (msg.includes('photo') || msg.includes('camera') || msg.includes('shoot') || msg.includes('pic') || msg.includes('image')) {
                category = 'photography';
            } else if (msg.includes('travel') || msg.includes('trip') || msg.includes('place') || msg.includes('visit') || msg.includes('go') || msg.includes('where')) {
                category = 'travel';
            } else if (msg.includes('food') || msg.includes('eat') || msg.includes('restaurant') || msg.includes('dinner') || msg.includes('lunch')) {
                category = 'food';
            } else if (msg.includes('tech') || msg.includes('computer') || msg.includes('laptop') || msg.includes('code') || msg.includes('software')) {
                category = 'tech';
            } else if (msg.includes('work') || msg.includes('file') || msg.includes('project') || msg.includes('job')) {
                category = 'work';
            } else if (msg.includes('shop') || msg.includes('buy') || msg.includes('dress') || msg.includes('outfit') || msg.includes('style')) {
                category = 'shopping';
            } else if (msg.includes('life') || msg.includes('happy') || msg.includes('feel') || msg.includes('good') || msg.includes('great')) {
                category = 'lifestyle';
            }
            
            // Get replies for category or fallback to general
            const replies = selectedUser.replies[category] || selectedUser.replies['general'];
            
            // Pick random reply
            const reply = replies[Math.floor(Math.random() * replies.length)];
            
            // Add some personalization
            const personalizations = [
                '',
                ' By the way,',
                ' Also,',
                ' Oh, and'
            ];
            
            const prefix = personalizations[Math.floor(Math.random() * personalizations.length)];
            
            // If we have conversation history, make it more contextual
            if (conversationHistory.length > 2 && Math.random() > 0.7) {
                return `I was thinking about what you said earlier... ${reply}`;
            }
            
            return prefix ? `${prefix} ${reply}` : reply;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto-scroll to bottom on load
        window.addEventListener('load', () => {
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        });

        // Handle conversation switching - store messages in memory before switch
        document.querySelectorAll('.conversation').forEach(conv => {
            conv.addEventListener('click', function(e) {
                // Let the link work normally, but we could add localStorage here if needed
                // For now, simple page reload with new user param works fine
            });
        });
    </script>
</body>
</html>