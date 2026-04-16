<?php
session_start();

// Redirect to login if not authenticated

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore · Shiro</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/explore.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="explore.php" class="active"><i class="fas fa-compass"></i></a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i></a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>                
            </ul>

            <div class="nav-actions">
                <a href="upload.php"><button class="btn-create"><i class="fas fa-plus"></i> Create</button></a>
                <a href="profile.php">
                    
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop" alt="User" class="user-avatar">
                </a>
            </div>
        </div>
    </nav>

    <div class="explore-container">
        <!-- Category Filter -->
        <div class="category-bar">
            <button class="category-btn active">For You</button>
            <button class="category-btn">Trending</button>
            <button class="category-btn">Photography</button>
            <button class="category-btn">Travel</button>
            <button class="category-btn">Food</button>
            <button class="category-btn">Art</button>
            <button class="category-btn">Technology</button>
            <button class="category-btn">Fashion</button>
            <button class="category-btn">Music</button>
        </div>

        <!-- Masonry Grid -->
        <div class="explore-grid">
            <div class="grid-item tall">
                <img src="https://images.pexels.com/photos/17892539/pexels-photo-17892539.jpeg?auto=compress&cs=tinysrgb&h=800&fit=crop" alt="Explore">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 12.5k</span>
                        <span><i class="fas fa-comment"></i> 342</span>
                    </div>
                </div>
            </div>
            <div class="grid-item">
                <img src="https://plus.unsplash.com/premium_photo-1661949391398-737f735067f2?w=600&h=600&fit=crop" alt="Explore">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 8.2k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item wide">
                <img src="https://plus.unsplash.com/premium_photo-1664474619075-644dd191935f?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8aW1hZ2V8ZW58MHx8MHx8fDA%3D" alt="Explore">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 15.1k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item">
                <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&h=600&fit=crop" alt="Food">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 6.7k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item tall">
                <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=800&fit=crop" alt="Fashion">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 22.3k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item">
                <img src="https://images.unsplash.com/photo-1566438480900-0609be27a4be?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8aW1hZ2V8ZW58MHx8MHx8fDA%3D" alt="Music">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 4.5k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item">
                <img src="https://images.unsplash.com/photo-1633621412960-6df85eff8c85?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8aW1hZ2V8ZW58MHx8MHx8fDA%3D" alt="Art">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 9.8k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item wide">
                <img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&h=400&fit=crop" alt="Nature">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 18.9k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item">
                <img src="https://images.unsplash.com/photo-1613323593608-abc90fec84ff?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Nnx8aW1hZ2V8ZW58MHx8MHx8fDA%3D" alt="Product">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 3.2k</span>
                    </div>
                </div>
            </div>
            <div class="grid-item">
                <img src="https://images.unsplash.com/photo-1547219469-75c19c0bd220?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTh8fGltYWdlfGVufDB8fDB8fHww" alt="Camera">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 7.6k</span>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <img src="https://images.unsplash.com/photo-1615109398623-88346a601842?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8bWFufGVufDB8fDB8fHww" alt="Camera">
                <div class="overlay">
                    <div class="overlay-stats">
                        <span><i class="fas fa-heart"></i> 79.6k</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>