// Upload JavaScript - File handling, preview, and upload functionality

// Global variables
let selectedFiles = [];
let currentFileIndex = 0;
let cropper = null;
let currentRotation = 0;

// DOM Elements
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const previewContainer = document.getElementById('previewContainer');
const mediaPreview = document.getElementById('mediaPreview');
const editTools = document.getElementById('editTools');
const videoControls = document.getElementById('videoControls');
const fileNav = document.getElementById('fileNav');
const aspectRatioSelector = document.getElementById('aspectRatioSelector');
const shareBtn = document.getElementById('shareBtn');
const dragOverlay = document.getElementById('dragOverlay');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initDragAndDrop();
    initFileInput();
    initCaption();
});

// ==================== DRAG AND DROP ====================

function initDragAndDrop() {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    uploadArea.addEventListener('drop', handleDrop, false);
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight() {
    dragOverlay.classList.add('active');
}

function unhighlight() {
    dragOverlay.classList.remove('active');
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}

// ==================== FILE INPUT ====================

function initFileInput() {
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

function handleFiles(files) {
    if (files.length === 0) return;
    
    const validFiles = Array.from(files).filter(file => {
        if (!file.type.match(/^(image|video)\//)) {
            showToast(`${file.name} is not a valid image or video`, 'error');
            return false;
        }
        
        if (file.size > 100 * 1024 * 1024) { // 100MB limit
            showToast(`${file.name} is too large (max 100MB)`, 'error');
            return false;
        }
        
        return true;
    });

    if (validFiles.length === 0) return;

    // Add to selected files
    selectedFiles = [...selectedFiles, ...validFiles.map(file => ({
        file: file,
        edited: false,
        cropped: false,
        cropData: null,
        rotation: 0
    }))];

    updateUI();
    showToast(`${validFiles.length} file(s) added`, 'success');
}

// ==================== UI UPDATES ====================

function updateUI() {
    if (selectedFiles.length === 0) {
        uploadPlaceholder.style.display = 'flex';
        previewContainer.style.display = 'none';
        aspectRatioSelector.style.display = 'none';
        shareBtn.disabled = true;
        uploadArea.classList.remove('has-file');
        return;
    }

    uploadPlaceholder.style.display = 'none';
    previewContainer.style.display = 'flex';
    aspectRatioSelector.style.display = 'flex';
    shareBtn.disabled = false;
    uploadArea.classList.add('has-file');

    // Show file navigation if multiple files
    if (selectedFiles.length > 1) {
        fileNav.style.display = 'flex';
        document.getElementById('fileCounter').textContent = 
            `${currentFileIndex + 1} / ${selectedFiles.length}`;
    } else {
        fileNav.style.display = 'none';
    }

    displayCurrentFile();
    updatePostTypeIndicator();
}

function displayCurrentFile() {
    const currentFile = selectedFiles[currentFileIndex];
    const file = currentFile.file;
    const isVideo = file.type.startsWith('video/');

    mediaPreview.innerHTML = '';
    editTools.style.display = isVideo ? 'none' : 'flex';
    videoControls.style.display = isVideo ? 'flex' : 'none';

    if (isVideo) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.controls = false;
        video.loop = true;
        video.muted = true;
        video.id = 'previewVideo';
        
        // Add trim controls
        video.addEventListener('loadedmetadata', () => {
            initVideoTrim(video);
        });
        
        mediaPreview.appendChild(video);
    } else {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.id = 'previewImage';
        img.style.maxHeight = '100%';
        img.style.maxWidth = '100%';
        img.style.objectFit = 'contain';
        
        // Apply rotation if any
        if (currentFile.rotation) {
            img.style.transform = `rotate(${currentFile.rotation}deg)`;
        }
        
        mediaPreview.appendChild(img);
    }
}

function updatePostTypeIndicator() {
    const indicator = document.getElementById('postTypeIndicator');
    const hasVideo = selectedFiles.some(f => f.file.type.startsWith('video/'));
    const hasImage = selectedFiles.some(f => f.file.type.startsWith('image/'));
    
    if (hasVideo && hasImage) {
        indicator.innerHTML = '<i class="fas fa-images"></i><span>Mixed Media</span>';
    } else if (hasVideo) {
        indicator.innerHTML = '<i class="fas fa-video"></i><span>Reel</span>';
    } else {
        indicator.innerHTML = '<i class="fas fa-image"></i><span>Photo Post</span>';
    }
}

// ==================== FILE NAVIGATION ====================

function nextFile() {
    if (currentFileIndex < selectedFiles.length - 1) {
        currentFileIndex++;
        updateUI();
    }
}

function prevFile() {
    if (currentFileIndex > 0) {
        currentFileIndex--;
        updateUI();
    }
}

function removeCurrentFile() {
    selectedFiles.splice(currentFileIndex, 1);
    
    if (currentFileIndex >= selectedFiles.length) {
        currentFileIndex = Math.max(0, selectedFiles.length - 1);
    }
    
    updateUI();
    showToast('File removed', 'success');
}

// ==================== IMAGE EDITING ====================

function rotateImage(degrees) {
    const currentFile = selectedFiles[currentFileIndex];
    if (!currentFile || currentFile.file.type.startsWith('video/')) return;

    currentFile.rotation = (currentFile.rotation || 0) + degrees;
    
    const img = document.getElementById('previewImage');
    if (img) {
        img.style.transform = `rotate(${currentFile.rotation}deg)`;
        img.style.transition = 'transform 0.3s ease';
    }
    
    currentFile.edited = true;
}

function resetEdit() {
    const currentFile = selectedFiles[currentFileIndex];
    if (!currentFile) return;

    currentFile.rotation = 0;
    currentFile.cropped = false;
    currentFile.cropData = null;
    
    displayCurrentFile();
    showToast('Reset to original', 'success');
}

// ==================== CROPPING ====================

function toggleCrop() {
    const currentFile = selectedFiles[currentFileIndex];
    if (!currentFile || currentFile.file.type.startsWith('video/')) return;

    const img = document.getElementById('previewImage');
    if (!img) return;

    // Open cropper modal
    const modal = document.getElementById('cropperModal');
    const cropperImg = document.getElementById('cropperImage');
    
    cropperImg.src = img.src;
    modal.style.display = 'flex';

    // Initialize cropper after image loads
    cropperImg.onload = () => {
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(cropperImg, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.8,
            responsive: true,
            background: false
        });
    };
}

function closeCropper() {
    const modal = document.getElementById('cropperModal');
    modal.style.display = 'none';
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
}

function applyCrop() {
    if (!cropper) return;

    const canvas = cropper.getCroppedCanvas({
        maxWidth: 4096,
        maxHeight: 4096
    });

    const currentFile = selectedFiles[currentFileIndex];
    
    canvas.toBlob((blob) => {
        // Create new file from blob
        const croppedFile = new File([blob], currentFile.file.name, {
            type: 'image/jpeg',
            lastModified: Date.now()
        });

        currentFile.file = croppedFile;
        currentFile.cropped = true;
        currentFile.cropData = cropper.getData();
        
        displayCurrentFile();
        closeCropper();
        showToast('Image cropped', 'success');
    }, 'image/jpeg', 0.95);
}

function setAspectRatio(ratio) {
    // Update UI
    document.querySelectorAll('.ratio-btn').forEach(btn => {
        btn.classList.remove('active');
        if (parseFloat(btn.dataset.ratio) === ratio) {
            btn.classList.add('active');
        }
    });

    // Apply to cropper if active
    if (cropper) {
        cropper.setAspectRatio(ratio);
    }

    // Apply to video if video
    const currentFile = selectedFiles[currentFileIndex];
    if (currentFile && currentFile.file.type.startsWith('video/')) {
        const video = document.getElementById('previewVideo');
        if (video) {
            video.style.aspectRatio = ratio;
        }
    }
}

// ==================== VIDEO CONTROLS ====================

function initVideoTrim(video) {
    const trimStart = document.getElementById('trimStart');
    const trimEnd = document.getElementById('trimEnd');
    
    trimStart.max = video.duration;
    trimEnd.max = video.duration;
    trimEnd.value = video.duration;
}

function togglePlay() {
    const video = document.getElementById('previewVideo');
    const icon = document.getElementById('playIcon');
    
    if (!video) return;
    
    if (video.paused) {
        video.play();
        icon.classList.remove('fa-play');
        icon.classList.add('fa-pause');
    } else {
        video.pause();
        icon.classList.remove('fa-pause');
        icon.classList.add('fa-play');
    }
}

// ==================== CAPTION & DETAILS ====================

function initCaption() {
    const caption = document.getElementById('caption');
    caption.addEventListener('input', updateCharCount);
}

function updateCharCount() {
    const caption = document.getElementById('caption');
    const count = document.getElementById('charCount');
    count.textContent = caption.value.length;
    
    if (caption.value.length > 2000) {
        count.style.color = 'var(--warning-color)';
    } else if (caption.value.length >= 2200) {
        count.style.color = 'var(--danger-color)';
    } else {
        count.style.color = 'var(--text-tertiary)';
    }
}

// ==================== LOCATION & TAGS ====================

function searchLocation(query) {
    const suggestions = document.getElementById('locationSuggestions');
    
    if (query.length < 2) {
        suggestions.innerHTML = '';
        return;
    }

    // Mock location suggestions
    const mockLocations = [
        'New York, NY',
        'Los Angeles, CA',
        'London, UK',
        'Tokyo, Japan',
        'Paris, France',
        'Sydney, Australia'
    ];

    const filtered = mockLocations.filter(loc => 
        loc.toLowerCase().includes(query.toLowerCase())
    );

    suggestions.innerHTML = filtered.map(loc => `
        <div class="tag-item" onclick="selectLocation('${loc}')" style="cursor: pointer;">
            <i class="fas fa-map-marker-alt"></i>
            <span>${loc}</span>
        </div>
    `).join('');
}

function selectLocation(location) {
    document.getElementById('location').value = location;
    document.getElementById('locationSuggestions').innerHTML = '';
}

function searchPeople(query) {
    const taggedPeople = document.getElementById('taggedPeople');
    
    if (query.length < 2) return;

    // Mock people suggestions - in real app, this would be an API call
    if (query.endsWith(' ')) {
        const username = query.trim();
        if (username) {
            addTaggedPerson(username);
            document.getElementById('tagPeople').value = '';
        }
    }
}

function addTaggedPerson(username) {
    const container = document.getElementById('taggedPeople');
    const tag = document.createElement('div');
    tag.className = 'tag-item';
    tag.innerHTML = `
        <i class="fas fa-user"></i>
        <span>@${username}</span>
        <button onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(tag);
}

// ==================== ADVANCED SETTINGS ====================

function toggleSettings() {
    const content = document.getElementById('settingsContent');
    const toggle = document.querySelector('.settings-toggle');
    const icon = document.getElementById('settingsIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'flex';
        toggle.classList.add('active');
    } else {
        content.style.display = 'none';
        toggle.classList.remove('active');
    }
}

// ==================== UPLOAD ACTIONS ====================

function saveDraft() {
    const draft = {
        files: selectedFiles.map(f => f.file.name),
        caption: document.getElementById('caption').value,
        location: document.getElementById('location').value,
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('shiro_draft', JSON.stringify(draft));
    showToast('Draft saved', 'success');
}

function sharePost() {
    if (selectedFiles.length === 0) {
        showToast('Please select at least one file', 'error');
        return;
    }

    const modal = document.getElementById('loadingModal');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('uploadProgress');
    
    modal.style.display = 'flex';
    
    // Simulate upload progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 100) progress = 100;
        
        progressFill.style.width = `${progress}%`;
        progressText.textContent = `${Math.round(progress)}%`;
        
        if (progress === 100) {
            clearInterval(interval);
            setTimeout(() => {
                modal.style.display = 'none';
                showSuccessModal();
            }, 500);
        }
    }, 200);
}

function showSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.style.display = 'flex';
    
    // Clear form
    selectedFiles = [];
    currentFileIndex = 0;
    document.getElementById('caption').value = '';
    document.getElementById('location').value = '';
    document.getElementById('taggedPeople').innerHTML = '';
    updateCharCount();
    updateUI();
}

function goHome() {
    window.location.href = 'index.html';
}

// ==================== TOAST NOTIFICATIONS ====================

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    
    // Remove existing classes
    toast.className = 'toast';
    toast.classList.add(type);
    
    // Set icon
    let icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    
    // Show
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ==================== KEYBOARD SHORTCUTS ====================

document.addEventListener('keydown', (e) => {
    // Escape to close modals
    if (e.key === 'Escape') {
        closeCropper();
        document.getElementById('loadingModal').style.display = 'none';
    }
    
    // Ctrl/Cmd + Enter to post
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        if (!shareBtn.disabled) {
            sharePost();
        }
    }
});

// ==================== LOAD DRAFT ====================

window.addEventListener('load', () => {
    const draft = localStorage.getItem('shiro_draft');
    if (draft) {
        const data = JSON.parse(draft);
        if (data.caption) {
            document.getElementById('caption').value = data.caption;
            updateCharCount();
        }
        if (data.location) {
            document.getElementById('location').value = data.location;
        }
    }
});