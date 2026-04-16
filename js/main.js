// js/main.js - Enhanced JavaScript

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all functionality
    initMobileMenu();
    initLikeButtons();
    initFollowButtons();
    initStories();
    initSmoothScroll();
    initAnimations();
    initSearchFocus();
    initPostInput();
    initInfiniteScroll();
    initImageLazyLoad();
    initTooltips();
    
    console.log('🚀 Shiro Social Media Platform Loaded Successfully!');
    console.log('✨ Enhanced with beautiful animations and interactions');
});

// Mobile Menu Toggle
function initMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebarLeft = document.querySelector('.sidebar-left');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenuBtn.classList.toggle('active');
            sidebarLeft.classList.toggle('active');
            
            // Animate hamburger to X
            const spans = mobileMenuBtn.querySelectorAll('span');
            if (mobileMenuBtn.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity = '1';
                spans[2].style.transform = '';
            }
        });
    }
}

// Like Button Animation with Heart Pop
function initLikeButtons() {
    const likeButtons = document.querySelectorAll('.post-btn');
    
    likeButtons.forEach(btn => {
        if (btn.innerHTML.includes('fa-heart')) {
            btn.addEventListener('click', function(e) {
                const icon = this.querySelector('i');
                
                // Create floating hearts animation
                createFloatingHearts(e.clientX, e.clientY);
                
                if (icon.classList.contains('far')) {
                    // Like
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = '#ef4444';
                    
                    // Add pop animation
                    icon.style.transform = 'scale(1.4)';
                    setTimeout(() => {
                        icon.style.transform = 'scale(1)';
                    }, 200);
                    
                    // Add heartbeat animation
                    icon.classList.add('heart-animation');
                    setTimeout(() => {
                        icon.classList.remove('heart-animation');
                    }, 1500);
                    
                    // Update text
                    const text = this.querySelector('span') || document.createElement('span');
                    if (!this.querySelector('span')) {
                        text.textContent = ' Liked';
                        this.appendChild(text);
                    }
                } else {
                    // Unlike
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '';
                    
                    const text = this.querySelector('span');
                    if (text) text.remove();
                }
            });
        }
    });
}

// Create floating hearts animation
function createFloatingHearts(x, y) {
    for (let i = 0; i < 5; i++) {
        const heart = document.createElement('div');
        heart.innerHTML = '❤️';
        heart.style.position = 'fixed';
        heart.style.left = x + 'px';
        heart.style.top = y + 'px';
        heart.style.fontSize = '20px';
        heart.style.pointerEvents = 'none';
        heart.style.zIndex = '9999';
        heart.style.animation = `floatUp ${1 + Math.random()}s ease-out forwards`;
        
        // Random spread
        const spreadX = (Math.random() - 0.5) * 100;
        heart.style.transform = `translateX(${spreadX}px)`;
        
        document.body.appendChild(heart);
        
        setTimeout(() => heart.remove(), 2000);
    }
}

// Add float up animation
const style = document.createElement('style');
style.textContent = `
    @keyframes floatUp {
        0% { opacity: 1; transform: translateY(0) scale(1); }
        100% { opacity: 0; transform: translateY(-100px) scale(0.5); }
    }
`;
document.head.appendChild(style);

// Follow Button Toggle with Animation
function initFollowButtons() {
    const followButtons = document.querySelectorAll('.btn-follow');
    
    followButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.textContent === 'Follow') {
                this.textContent = 'Following';
                this.style.background = 'linear-gradient(135deg, var(--primary-color), var(--accent-color))';
                this.style.color = 'white';
                this.style.borderColor = 'transparent';
                
                // Success animation
                this.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
            } else {
                this.textContent = 'Follow';
                this.style.background = '';
                this.style.color = '';
                this.style.borderColor = '';
            }
        });
    });
}

// Stories Click Handler with View Animation
function initStories() {
    const stories = document.querySelectorAll('.story-item');
    
    stories.forEach(story => {
        story.addEventListener('click', () => {
            const ring = story.querySelector('.story-ring');
            
            if (ring && !ring.classList.contains('viewed')) {
                // Add viewed class with animation
                ring.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    ring.classList.add('viewed');
                    ring.style.transform = 'scale(1)';
                }, 300);
                
                // Show story viewer (simulated)
                showStoryViewer(story);
            }
        });
    });
}

// Simulate Story Viewer
function showStoryViewer(story) {
    const viewer = document.createElement('div');
    viewer.className = 'story-viewer';
    viewer.innerHTML = `
        <div class="story-content">
            <img src="${story.querySelector('img').src}" alt="Story">
            <div class="story-progress"></div>
        </div>
    `;
    viewer.style.cssText = `
        position: fixed;
        inset: 0;
        background: black;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    
    document.body.appendChild(viewer);
    
    // Auto close after 3 seconds
    setTimeout(() => {
        viewer.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => viewer.remove(), 300);
    }, 3000);
    
    viewer.addEventListener('click', () => {
        viewer.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => viewer.remove(), 300);
    });
}

// Smooth Scroll
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Intersection Observer for Animations
function initAnimations() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, observerOptions);

    // Observe posts with staggered animation
    document.querySelectorAll('.post').forEach((post, index) => {
        post.style.opacity = '0';
        post.style.transform = 'translateY(30px)';
        post.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(post);
    });
}

// Search Focus Effect
function initSearchFocus() {
    const searchInputs = document.querySelectorAll('input[type="text"]');
    
    searchInputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.style.transform = 'scale(1.02)';
            input.parentElement.style.zIndex = '10';
        });
        
        input.addEventListener('blur', () => {
            input.parentElement.style.transform = '';
            input.parentElement.style.zIndex = '';
        });
    });
}

// Post Input Enhancement
function initPostInput() {
    const postInput = document.querySelector('.post-input input');
    
    if (postInput) {
        postInput.addEventListener('focus', () => {
            postInput.placeholder = 'Share your story with the world...';
            postInput.parentElement.style.transform = 'scale(1.02)';
        });
        
        postInput.addEventListener('blur', () => {
            postInput.placeholder = 'What\'s on your mind, Sarah?';
            postInput.parentElement.style.transform = '';
        });
        
        // Expand on type
        postInput.addEventListener('input', function() {
            if (this.value.length > 50) {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            }
        });
    }
}

// Infinite Scroll Simulation
function initInfiniteScroll() {
    let loading = false;
    const postsContainer = document.querySelector('.posts-container');
    
    window.addEventListener('scroll', () => {
        if (loading) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const bodyHeight = document.body.offsetHeight;
        
        if (scrollPosition >= bodyHeight - 1000) {
            loading = true;
            
            // Show loading indicator
            const loader = document.createElement('div');
            loader.className = 'post loading-post';
            loader.innerHTML = '<div class="skeleton" style="height: 400px;"></div>';
            loader.style.opacity = '0.5';
            postsContainer.appendChild(loader);
            
            // Simulate loading
            setTimeout(() => {
                loader.remove();
                loading = false;
                
                // Add new posts (clone existing for demo)
                const posts = document.querySelectorAll('.post');
                if (posts.length > 0) {
                    const clone = posts[0].cloneNode(true);
                    clone.style.opacity = '0';
                    clone.style.transform = 'translateY(30px)';
                    postsContainer.appendChild(clone);
                    
                    setTimeout(() => {
                        clone.style.transition = 'all 0.5s ease';
                        clone.style.opacity = '1';
                        clone.style.transform = 'translateY(0)';
                    }, 100);
                }
            }, 1500);
        }
    });
}

// Image Lazy Loading with Blur Effect
function initImageLazyLoad() {
    const images = document.querySelectorAll('img');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // Add loading state
                img.style.filter = 'blur(10px)';
                img.style.transition = 'filter 0.5s ease';
                
                img.onload = () => {
                    img.style.filter = 'blur(0)';
                };
                
                // Handle already loaded images
                if (img.complete) {
                    img.style.filter = 'blur(0)';
                }
                
                imageObserver.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px'
    });

    images.forEach(img => imageObserver.observe(img));
}

// Tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = el.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--text-primary);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: var(--radius-md);
                font-size: 0.875rem;
                z-index: 1000;
                pointer-events: none;
                animation: fadeIn 0.2s ease;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = el.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
            el.tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', () => {
            if (el.tooltip) {
                el.tooltip.remove();
                el.tooltip = null;
            }
        });
    });
}

// Add fade animations
const animStyle = document.createElement('style');
animStyle.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(animStyle);

// Prevent Default on Empty Links
document.querySelectorAll('a[href="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
    });
});

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Press 'N' to create new post
    if (e.key === 'n' && e.target.tagName !== 'INPUT') {
        document.querySelector('.post-input input')?.focus();
    }
    
    // Press '/' to search
    if (e.key === '/' && e.target.tagName !== 'INPUT') {
        e.preventDefault();
        document.querySelector('.nav-search input')?.focus();
    }
    
    // Press 'ESC' to close modals
    if (e.key === 'Escape') {
        document.querySelector('.story-viewer')?.remove();
    }
});