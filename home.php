<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "connectionzim");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch posts
$sql = "SELECT * FROM post ORDER BY date DESC";
$result = $conn->query($sql);

if (!$result) {
    error_log("Query failed: " . $conn->error);
    die("Error fetching posts");
}

// Debug - print first row
if ($result->num_rows > 0) {
    $first_row = $result->fetch_assoc();
    error_log("First row data: " . print_r($first_row, true));
    // Reset pointer to beginning
    $result->data_seek(0);
}

// Debug
error_log("Found " . $result->num_rows . " posts");
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<body>
    <!-- Loader code -->
    <div class="loader-container">
        <div class="loader">
            <div class="logo-animation">
                <i class="bi bi-box logo-icon"></i>
                <div class="logo-text">
                    <span style="animation-delay: 0.1s">C</span>
                    <span style="animation-delay: 0.2s">O</span>
                    <span style="animation-delay: 0.3s">N</span>
                    <span style="animation-delay: 0.4s">N</span>
                    <span style="animation-delay: 0.5s">E</span>
                    <span style="animation-delay: 0.6s">C</span>
                    <span style="animation-delay: 0.7s">T</span>
                    <span style="animation-delay: 0.8s">I</span>
                    <span style="animation-delay: 0.9s">O</span>
                    <span style="animation-delay: 1.0s">N</span>
                    <span style="animation-delay: 1.1s">Z</span>
                    <span style="animation-delay: 1.2s">I</span>
                    <span style="animation-delay: 1.3s">M</span>
                </div>
            </div>
            <div class="loading-bar"></div>
        </div>
    </div>

    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Stories/Highlights Section -->
            <section class="stories-section">
                <div class="stories-container">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- Add Story Button -->
                        <div class="story-card add-story" onclick="showStoryUpload()">
                            <div class="story-add-icon">
                                <i class="bi bi-plus-circle-fill"></i>
                            </div>
                            <span>Add Story</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    try {
                        // Fetch stories from database
                        $stories_sql = "SELECT s.*, u.username, u.profile_pic 
                                FROM stories s 
                                JOIN users u ON s.user_id = u.id 
                                WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                                ORDER BY s.created_at DESC";
                        $stories_result = $conn->query($stories_sql);
                        
                        if ($stories_result && $stories_result->num_rows > 0) {
                            while($story = $stories_result->fetch_assoc()) {
                                // Make sure story ID exists and is numeric
                                $story_id = isset($story['id']) ? (int)$story['id'] : 0;
                                ?>
                                <div class="story-card" onclick="viewStory(<?php echo $story_id; ?>)">
                                    <img src="<?php echo htmlspecialchars($story['media_url']); ?>" alt="Story" class="story-image">
                                    <div class="story-info">
                                        <div class="story-avatar">
                                            <img src="<?php echo !empty($story['profile_pic']) ? htmlspecialchars($story['profile_pic']) : 'default_avatar.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($story['username']); ?>">
                                        </div>
                                        <span><?php echo htmlspecialchars($story['username']); ?></span>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            if(!isset($_SESSION['user_id'])) {
                                echo "<div class='no-stories'>Login to view and share stories</div>";
                            } else {
                                echo "<div class='no-stories'>No stories yet. Be the first to share!</div>";
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error in stories section: " . $e->getMessage());
                        echo "<div class='no-stories'>Error loading stories</div>";
                    }
                    ?>
                </div>
            </section>

            <!-- Replace floating create button with LinkedIn style input box -->
            <section class="create-post-box">
                <div class="post-input-container" onclick="showCreatePost()">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Your avatar" class="user-avatar">
                    <div class="start-post-button">
                        <span>Start a post</span>
                    </div>
                </div>
                <div class="quick-post-options">
                    <button type="button" onclick="showCreatePost('media')" class="quick-post-btn">
                        <i class="bi bi-image text-primary"></i>
                        <span>Media</span>
                    </button>
                    <button type="button" onclick="showCreatePost('event')" class="quick-post-btn">
                        <i class="bi bi-calendar-event text-warning"></i>
                        <span>Event</span>
                    </button>
                    <button type="button" onclick="showCreatePost('article')" class="quick-post-btn">
                        <i class="bi bi-file-text text-success"></i>
                        <span>Write article</span>
                    </button>
                </div>
            </section>

            <!-- Feed Filters -->
            <section class="feed-filters">
                <div class="filters-scroll">
                    <button class="filter-btn active" data-filter="foryou" onclick="filterPosts('foryou')">
                        <i class="bi bi-stars"></i>
                        <span>For You</span>
                    </button>
                    <button class="filter-btn" data-filter="trending" onclick="filterPosts('trending')">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Trending</span>
                    </button>
                    <button class="filter-btn" data-filter="popular" onclick="filterPosts('popular')">
                        <i class="bi bi-fire"></i>
                        <span>Popular</span>
                    </button>
                    <button class="filter-btn" data-filter="recent" onclick="filterPosts('recent')">
                        <i class="bi bi-clock"></i>
                        <span>Recent</span>
                    </button>
                </div>
            </section>

            <!-- Feed Content -->
            <section class="feed-content">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Get post author's profile pic
                        $author_query = "SELECT profile_pic FROM users WHERE id = ?";
                        $stmt = $conn->prepare($author_query);
                        $stmt->bind_param("i", $row['user_id']);
                        $stmt->execute();
                        $author_result = $stmt->get_result();
                        $author_data = $author_result->fetch_assoc();
                        $author_pic = $author_data['profile_pic'] ?? 'default_avatar.jpg';
                        ?>
                        <article class="feed-card">
                            <div class="card-header">
                                <div class="user-info">
                                    <img src="<?php echo htmlspecialchars($author_pic); ?>" alt="User" class="user-avatar">
                                    <div class="user-details">
                                        <div class="user-name">
                                            <?php echo htmlspecialchars($row['name'] ?? ''); ?>
                                            <span class="badge-pro">PRO</span>
                                        </div>
                                        <div class="post-meta">
                                            <span class="user-title"><?php echo htmlspecialchars($row['title'] ?? ''); ?></span>
                                            <span class="post-time"><?php echo isset($row['date']) ? date('M d, Y', strtotime($row['date'])) : ''; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn-icon">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                            </div>

                            <div class="card-content">
                                <div class="content-text">
                                    <p><?php echo htmlspecialchars($row['content'] ?? ''); ?></p>
                                </div>
                                <?php if(isset($row['media_file']) && !empty($row['media_file'])): ?>
                                    <div class="content-preview">
                                        <?php if($row['media_type'] == 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($row['media_file']); ?>" 
                                                 alt="Post Image" 
                                                 style="max-width: 100%; height: auto;">
                                        <?php elseif($row['media_type'] == 'video'): ?>
                                            <video controls style="max-width: 100%; height: auto;">
                                                <source src="<?php echo htmlspecialchars($row['media_file']); ?>" 
                                                        type="video/<?php echo pathinfo($row['media_file'], PATHINFO_EXTENSION); ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card stats section add karein card-content ke baad -->
                            <div class="card-stats">
                                <div class="stat-group">
                                    <?php
                                    // Check if user has liked this post
                                    $liked = false;
                                    $like_count = 0;
                                    
                                    if (isset($_SESSION['user_id'])) {
                                        $like_check = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
                                        $like_check->bind_param("ii", $row['id'], $_SESSION['user_id']);
                                        $like_check->execute();
                                        $liked = $like_check->get_result()->num_rows > 0;
                                    }
                                    
                                    // Get total likes
                                    $count_query = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
                                    $count_query->bind_param("i", $row['id']);
                                    $count_query->execute();
                                    $like_count = $count_query->get_result()->fetch_assoc()['count'];
                                    ?>
                                    
                                    <button class="stat-btn like-btn <?php echo $liked ? 'liked' : ''; ?>" 
                                            onclick="handleLike(this)" 
                                            data-post-id="<?php echo $row['id']; ?>">
                                        <i class="bi <?php echo $liked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                        <span class="like-count"><?php echo $like_count; ?></span>
                                    </button>
                                    <?php
                                    // Get comment count
                                    $comment_query = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
                                    $comment_query->bind_param("i", $row['id']);
                                    $comment_query->execute();
                                    $comment_count = $comment_query->get_result()->fetch_assoc()['count'];
                                    ?>
                                    <button class="stat-btn" onclick="showComments(<?php echo $row['id']; ?>)" data-post-id="<?php echo $row['id']; ?>">
                                        <i class="bi bi-chat"></i>
                                        <span class="comment-count" data-post-id="<?php echo $row['id']; ?>"><?php echo $comment_count; ?></span>
                                    </button>
                                    <button class="stat-btn" onclick="sharePost('<?php echo htmlspecialchars($row['id'] ?? ''); ?>')">
                                        <i class="bi bi-share"></i>
                                        <span>Share</span>
                                    </button>
                                </div>
                                <button class="stat-btn" onclick="savePost('<?php echo htmlspecialchars($row['id'] ?? ''); ?>')">
                                    <i class="bi bi-bookmark"></i>
                                </button>
                            </div>
                        </article>
                        <?php
                    }
                } else {
                    echo "<p class='no-posts'>No posts found</p>";
                }
                ?>
            </section>

            <!-- Story Upload Modal -->
            <div id="storyUploadModal" class="popup-overlay">
                <div class="popup-content">
                    <button class="close-popup" onclick="closeStoryUpload()">×</button>
                    <h2>Add Story</h2>
                    <form id="storyForm" action="upload_story.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="story_media">Choose Media:</label>
                            <input type="file" id="story_media" name="story_media" accept="image/*,video/*" required>
                        </div>
                        <div class="form-group">
                            <label for="story_caption">Caption (optional):</label>
                            <input type="text" id="story_caption" name="caption" maxlength="100">
                        </div>
                        <button type="submit" class="btn-submit">Share Story</button>
                    </form>
                </div>
            </div>

            <!-- Create Post Modal -->
            <div id="createPostModal" class="popup-overlay">
                <div class="popup-content create-post-popup">
                    <div class="popup-header">
                        <h3>Create a post</h3>
                        <button class="close-popup" onclick="closeCreatePost()">×</button>
                    </div>
                    
                    <div class="post-creator">
                        <div class="creator-info">
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Your avatar" class="user-avatar">
                            <div class="creator-details">
                                <span class="creator-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <button class="privacy-selector">
                                    <i class="bi bi-globe"></i>
                                    <span>Anyone</span>
                                    <i class="bi bi-caret-down-fill"></i>
                                </button>
                            </div>
                        </div>
                        
                        <form id="postForm" method="POST" action="save_post.php" enctype="multipart/form-data">
                            <textarea name="content" placeholder="What do you want to talk about?" required></textarea>
                            
                            <div id="mediaPreview"></div>
                            
                            <div class="post-options">
                                <span class="option-label">Add to your post</span>
                                <div class="option-buttons">
                                    <label class="option-btn" title="Media">
                                        <input type="file" name="photo" id="post_media" accept="image/*,video/*" hidden>
                                        <i class="bi bi-image"></i>
                                    </label>
                                    <button type="button" class="option-btn" title="Mention">
                                        <i class="bi bi-at"></i>
                                    </button>
                                    <button type="button" class="option-btn" title="Emoji">
                                        <i class="bi bi-emoji-smile"></i>
                                    </button>
                                    <button type="button" class="option-btn" title="Event">
                                        <i class="bi bi-calendar-event"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="post-submit-wrapper">
                                <button type="submit" name="submit" class="post-submit-btn">Post</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Instagram-style Story Viewer -->
            <div id="storyViewer" class="story-viewer">
                <div class="story-container">
                    <!-- Story Progress Bar -->
                    <div class="story-progress">
                        <div class="story-progress-bar"></div>
                    </div>

                    <!-- Story Content -->
                    <div class="story-content">
                        <img id="storyImage" src="" alt="" class="story-image">
                        
                        <!-- Story Header -->
                        <div class="story-header">
                            <div class="story-user">
                                <img id="storyUserAvatar" src="" alt="" class="story-avatar">
                                <span id="storyUsername" class="story-username"></span>
                                <span id="storyTimestamp" class="story-time"></span>
                            </div>
                            <button class="story-close" onclick="closeStoryViewer()">×</button>
                        </div>
                    </div>

                    <!-- Story Navigation -->
                    <div class="story-nav">
                        <div class="story-nav-item prev" onclick="previousStory()"></div>
                        <div class="story-nav-item next" onclick="nextStory()"></div>
                    </div>
                </div>
            </div>

            <!-- Event Creation Modal -->
            <div id="createEventModal" class="popup-overlay">
                <div class="popup-content create-event-popup">
                    <div class="popup-header">
                        <h3>Create an event</h3>
                        <button class="close-popup" onclick="closeEventModal()">×</button>
                    </div>
                    
                    <div class="event-creator">
                        <form id="eventForm" method="POST" action="save_event.php" enctype="multipart/form-data">
                            <div class="event-banner-upload">
                                <label for="event_banner">
                                    <div class="banner-placeholder">
                                        <i class="bi bi-image"></i>
                                        <span>Add event banner</span>
                                    </div>
                                    <input type="file" id="event_banner" name="banner" accept="image/*" hidden>
                                </label>
                            </div>

                            <div class="event-fields">
                                <div class="form-group">
                                    <label>Event name*</label>
                                    <input type="text" name="title" required placeholder="Enter event name">
                                </div>

                                <div class="time-slots">
                                    <div class="form-group">
                                        <label>Start time*</label>
                                        <input type="datetime-local" name="start_time" required>
                                    </div>
                                    <div class="form-group">
                                        <label>End time*</label>
                                        <input type="datetime-local" name="end_time" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Event type*</label>
                                    <div class="event-type-selector">
                                        <label class="event-type">
                                            <input type="radio" name="event_type" value="online" checked>
                                            <i class="bi bi-laptop"></i>
                                            <span>Online</span>
                                        </label>
                                        <label class="event-type">
                                            <input type="radio" name="event_type" value="in_person">
                                            <i class="bi bi-geo-alt"></i>
                                            <span>In-person</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group location-input" style="display: none;">
                                    <label>Location</label>
                                    <input type="text" name="location" placeholder="Add location">
                                </div>

                                <div class="form-group">
                                    <label>Description*</label>
                                    <textarea name="description" required placeholder="What is your event about?"></textarea>
                                </div>
                            </div>

                            <div class="event-submit-wrapper">
                                <button type="submit" class="event-submit-btn">Create event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Article Creation Modal -->
            <div id="createArticleModal" class="popup-overlay">
                <div class="popup-content create-article-popup">
                    <div class="popup-header">
                        <h3>Write an article</h3>
                        <button class="close-popup" onclick="closeArticleModal()">×</button>
                    </div>
                    
                    <div class="article-creator">
                        <form id="articleForm" method="POST" action="save_article.php" enctype="multipart/form-data">
                            <div class="article-cover-upload">
                                <label for="article_cover">
                                    <div class="cover-placeholder">
                                        <i class="bi bi-image"></i>
                                        <span>Add cover image</span>
                                    </div>
                                    <input type="file" id="article_cover" name="cover" accept="image/*" hidden>
                                </label>
                            </div>

                            <div class="article-fields">
                                <div class="form-group">
                                    <input type="text" name="title" class="article-title" placeholder="Article Title" required>
                                </div>

                                <div class="form-group">
                                    <textarea name="content" class="article-content" placeholder="Write your article content here..." required></textarea>
                                </div>

                                <div class="article-tools">
                                    <button type="button" class="tool-btn" title="Bold">
                                        <i class="bi bi-type-bold"></i>
                                    </button>
                                    <button type="button" class="tool-btn" title="Italic">
                                        <i class="bi bi-type-italic"></i>
                                    </button>
                                    <button type="button" class="tool-btn" title="Link">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                    <button type="button" class="tool-btn" title="List">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="article-submit-wrapper">
                                <button type="submit" class="article-submit-btn">Publish article</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'right_sidebar.php'; ?>
    </div>

    <!-- ... JavaScript code ... -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loader = document.querySelector('.loader-container');
                loader.classList.add('fade-out');
                
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 500);
            }, 2000);
        });

        function handleLike(button) {
            const postId = button.getAttribute('data-post-id');
            const likeIcon = button.querySelector('i');
            const likeCount = button.querySelector('.like-count');

            // Show loading state
            button.disabled = true;

            fetch('handle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Like response:', data); // Debug log
                
                if (data.success) {
                    // Update like count
                    likeCount.textContent = data.count;
                    
                    // Update button appearance
                    if (data.liked) {
                        likeIcon.classList.remove('bi-heart');
                        likeIcon.classList.add('bi-heart-fill');
                        button.classList.add('liked');
                    } else {
                        likeIcon.classList.remove('bi-heart-fill');
                        likeIcon.classList.add('bi-heart');
                        button.classList.remove('liked');
                    }
                } else {
                    if (data.message === 'Please login first') {
                        showLoginModal();
                    } else {
                        alert(data.message || 'Error processing like');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing like');
            })
            .finally(() => {
                button.disabled = false;
            });
        }

        function showComments(postId) {
            // Create modal HTML
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'flex';
            modal.innerHTML = `
                <div class="modal-content comments-modal">
                    <div class="modal-header">
                        <h3>Comments</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="comments-container">
                        <div class="loading">Loading comments...</div>
                    </div>
                    <form class="comment-form" onsubmit="submitComment(event, ${postId})">
                        <textarea name="content" placeholder="Write a comment..." required></textarea>
                        <button type="submit">Post Comment</button>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Load comments
            fetch(`get_comments.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    const container = modal.querySelector('.comments-container');
                    container.innerHTML = '';
                    
                    if (data.success && data.comments.length > 0) {
                        data.comments.forEach(comment => {
                            container.innerHTML += `
                                <div class="comment-item">
                                    <img src="${comment.profile_pic}" alt="${comment.username}" class="comment-avatar">
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <strong>${comment.username}</strong>
                                            <span class="comment-time">${formatDate(comment.created_at)}</span>
                                        </div>
                                        <p>${comment.content}</p>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        container.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modal.querySelector('.comments-container').innerHTML = 
                        '<div class="error">Error loading comments</div>';
                });
        }

        function submitComment(event, postId) {
            event.preventDefault();
            const form = event.target;
            const content = form.content.value;
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Disable submit button while processing
            submitButton.disabled = true;
            
            fetch('add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&content=${encodeURIComponent(content)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = form.previousElementSibling;
                    const noComments = container.querySelector('.no-comments');
                    if (noComments) {
                        container.innerHTML = '';
                    }
                    
                    // Add new comment to container
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment-item';
                    commentElement.innerHTML = `
                        <img src="${data.comment.profile_pic}" alt="${data.comment.username}" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-header">
                                <strong>${data.comment.username}</strong>
                                <span class="comment-time">Just now</span>
                            </div>
                            <p>${data.comment.content}</p>
                        </div>
                    `;
                    container.insertBefore(commentElement, container.firstChild);
                    form.reset();
                    
                    // Update all comment count displays for this post
                    document.querySelectorAll(`.comment-count[data-post-id="${postId}"]`).forEach(countElement => {
                        countElement.textContent = data.count;
                    });
                } else {
                    alert(data.message || 'Error posting comment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error posting comment');
            })
            .finally(() => {
                submitButton.disabled = false;
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return `${Math.floor(diff/60000)} minutes ago`;
            if (diff < 86400000) return `${Math.floor(diff/3600000)} hours ago`;
            if (diff < 604800000) return `${Math.floor(diff/86400000)} days ago`;
            
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function sharePost(postId) {
            // Add your share functionality here
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this post',
                    text: 'I found this interesting post on ConnectionZim',
                    url: window.location.href
                })
                .catch(error => console.log('Error sharing:', error));
            } else {
                alert('Share feature coming soon!');
            }
        }

        function savePost(postId) {
            // Add your save functionality here
            const button = event.currentTarget;
            const icon = button.querySelector('i');
            icon.classList.toggle('bi-bookmark');
            icon.classList.toggle('bi-bookmark-fill');
            alert('Post saved!');
        }

        // Story functions
        function showStoryUpload() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'flex';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Add Story</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <form id="storyUploadForm">
                        <div class="form-group">
                            <label for="storyMedia" class="upload-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose Photo/Video</span>
                            </label>
                            <input type="file" id="storyMedia" name="story_media" accept="image/*,video/mp4" hidden>
                        </div>
                        <div class="preview-container">
                            <img id="storyPreview" style="display: none; max-width: 100%; max-height: 300px;">
                            <video id="videoPreview" style="display: none; max-width: 100%; max-height: 300px;" controls></video>
                        </div>
                        <button type="submit" class="btn-primary" disabled>Share Story</button>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            const form = modal.querySelector('#storyUploadForm');
            const fileInput = modal.querySelector('#storyMedia');
            const submitBtn = modal.querySelector('button[type="submit"]');
            const imgPreview = modal.querySelector('#storyPreview');
            const videoPreview = modal.querySelector('#videoPreview');

            fileInput.onchange = function() {
                const file = this.files[0];
                if (file) {
                    submitBtn.disabled = false;
                    const reader = new FileReader();

                    if (file.type.startsWith('image/')) {
                        reader.onload = function(e) {
                            imgPreview.src = e.target.result;
                            imgPreview.style.display = 'block';
                            videoPreview.style.display = 'none';
                        }
                    } else if (file.type.startsWith('video/')) {
                        reader.onload = function(e) {
                            videoPreview.src = e.target.result;
                            videoPreview.style.display = 'block';
                            imgPreview.style.display = 'none';
                        }
                    }
                    reader.readAsDataURL(file);
                }
            };

            form.onsubmit = async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Uploading...';

                try {
                    const response = await fetch('add_story.php', {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    console.log('Raw response:', responseText); // Debug log

                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (error) {
                        console.error('JSON parse error:', error);
                        throw new Error('Server returned invalid JSON: ' + responseText);
                    }

                    if (data.success) {
                        alert('Story uploaded successfully!');
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Upload failed');
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('Error uploading story: ' + error.message);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Share Story';
                }
            };
        }

        function closeStoryUpload() {
            document.getElementById('storyUploadModal').style.display = 'none';
        }

        function viewStory(storyId) {
            const modal = document.createElement('div');
            modal.className = 'modal story-view-modal';
            modal.style.display = 'flex';
            modal.innerHTML = `
                <div class="story-viewer">
                    <div class="story-progress-bar">
                        <div class="progress"></div>
                    </div>
                    <div class="story-header">
                        <div class="story-user-info">
                            <div class="story-avatar"></div>
                            <div class="story-user-details">
                                <span class="story-username"></span>
                                <span class="story-time"></span>
                            </div>
                        </div>
                        <div class="story-actions">
                            <button class="story-action-btn">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <button class="close-story" onclick="this.closest('.modal').remove()">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="story-navigation">
                        <button class="nav-btn prev" onclick="previousStory()">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="nav-btn next" onclick="nextStory()">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <div class="story-content">
                        <div class="story-loading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                    <div class="story-footer">
                        <div class="story-reply">
                            <input type="text" placeholder="Reply to story...">
                            <button>
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Fetch and show story
            fetchStory(storyId);
        }

        function fetchStory(storyId) {
            fetch(`get_story.php?id=${storyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const story = data.story;
                        updateStoryView(story);
                        startStoryTimer();
                    } else {
                        throw new Error(data.message || 'Failed to load story');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelector('.story-content').innerHTML = `
                        <div class="story-error">
                            <i class="bi bi-exclamation-circle"></i>
                            <p>Error loading story</p>
                        </div>
                    `;
                });
        }

        function updateStoryView(story) {
            console.log('Story data:', story); // Debug log
            
            const modal = document.querySelector('.story-view-modal');
            const content = modal.querySelector('.story-content');
            const avatar = modal.querySelector('.story-avatar');
            const username = modal.querySelector('.story-username');
            const timeSpan = modal.querySelector('.story-time');

            // Update user info
            avatar.innerHTML = `<img src="${story.profile_pic || 'default_avatar.jpg'}" alt="${story.username}">`;
            username.textContent = story.username;
            timeSpan.textContent = formatTimeAgo(story.created_at);

            // Show media based on type
            if (story.media_type === 'image') {
                const img = new Image();
                img.onload = function() {
                    content.innerHTML = '';
                    content.appendChild(img);
                    startStoryTimer();
                };
                img.onerror = function(e) {
                    console.error('Image load error:', e);
                    content.innerHTML = `
                        <div class="story-error">
                            <i class="bi bi-exclamation-circle"></i>
                            <p>Error loading image: ${story.media_url}</p>
                        </div>
                    `;
                };
                console.log('Loading image:', story.media_url);
                img.src = story.media_url;
                img.alt = "Story";
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100vh';
                img.style.objectFit = 'contain';
            } else if (story.media_type === 'video') {
                content.innerHTML = `
                    <video controls autoplay>
                        <source src="${story.media_url}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                `;
                startStoryTimer();
            }
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return `${Math.floor(diff/60000)} minutes ago`;
            if (diff < 86400000) return `${Math.floor(diff/3600000)} hours ago`;
            return date.toLocaleDateString();
        }

        function startStoryTimer() {
            const progress = document.querySelector('.story-progress-bar .progress');
            progress.style.width = '0%';
            
            const duration = 5000; // 5 seconds for each story
            const start = Date.now();
            
            function updateProgress() {
                const elapsed = Date.now() - start;
                const percentage = (elapsed / duration) * 100;
                
                if (percentage < 100) {
                    progress.style.width = percentage + '%';
                    requestAnimationFrame(updateProgress);
                } else {
                    progress.style.width = '100%';
                    // Auto close or move to next story
                    setTimeout(() => {
                        nextStory();
                    }, 100);
                }
            }
            
            requestAnimationFrame(updateProgress);
        }

        function closeStoryViewer() {
            const viewer = document.getElementById('storyViewer');
            viewer.style.display = 'none';
            
            // Clear timeouts and intervals
            if(storyTimeout) clearTimeout(storyTimeout);
            if(progressInterval) clearInterval(progressInterval);
        }

        function pauseStory() {
            if(storyTimeout) clearTimeout(storyTimeout);
            if(progressInterval) clearInterval(progressInterval);
        }

        function resumeStory() {
            const progressBar = document.querySelector('.story-progress-bar');
            const currentProgress = parseFloat(progressBar.style.width);
            const remainingProgress = 100 - currentProgress;
            
            if(remainingProgress <= 0) {
                closeStoryViewer();
                return;
            }
            
            const duration = 5000 * (remainingProgress / 100);
            const interval = 10;
            const increment = (interval / duration) * remainingProgress;
            
            progressInterval = setInterval(() => {
                const newProgress = parseFloat(progressBar.style.width) + increment;
                progressBar.style.width = `${newProgress}%`;
                
                if(newProgress >= 100) {
                    clearInterval(progressInterval);
                    closeStoryViewer();
                }
            }, interval);
            
            storyTimeout = setTimeout(closeStoryViewer, duration);
        }

        function addTouchEvents() {
            const viewer = document.getElementById('storyViewer');
            
            viewer.addEventListener('touchstart', () => {
                pauseStory();
            });
            
            viewer.addEventListener('touchend', () => {
                resumeStory();
            });
        }

        // Handle keyboard and mouse events
        document.addEventListener('keydown', function(e) {
            if(document.getElementById('storyViewer').style.display === 'block') {
                if(e.key === 'Escape') closeStoryViewer();
            }
        });

        // Hold to pause story
        document.getElementById('storyViewer').addEventListener('mousedown', pauseStory);
        document.getElementById('storyViewer').addEventListener('mouseup', resumeStory);

        // Close story on click
        document.querySelector('.story-close').addEventListener('click', closeStoryViewer);

        // Helper function for time ago
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            
            return Math.floor(seconds) + " seconds ago";
        }

        // Post functions
        function showCreatePost(type = 'text') {
            const modal = document.getElementById('createPostModal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeCreatePost() {
            const modal = document.getElementById('createPostModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('mediaPreview').innerHTML = '';
                document.getElementById('postForm').reset();
            }, 300);
        }

        // Media preview
        document.getElementById('post_media').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(!file) return;
            
            const preview = document.getElementById('mediaPreview');
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if(file.type.startsWith('image/')) {
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 300px; border-radius: 8px;">`;
                } else if(file.type.startsWith('video/')) {
                    preview.innerHTML = `
                        <video controls style="max-width: 100%; max-height: 300px; border-radius: 8px;">
                            <source src="${e.target.result}" type="${file.type}">
                            Your browser does not support the video tag.
                        </video>`;
                }
            }
            
            reader.readAsDataURL(file);
        });

        document.getElementById('postForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('submit', 'true');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Posting...';
            submitBtn.disabled = true;
            
            fetch('save_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Create new post element
                    const newPost = createPostElement(data.post);
                    
                    // Add post to feed
                    const feedContent = document.querySelector('.feed-content');
                    feedContent.insertBefore(newPost, feedContent.firstChild);
                    
                    // Clear form
                    this.reset();
                    document.getElementById('mediaPreview').innerHTML = '';
                    
                    // Close modal
                    closeCreatePost();
                    
                    // Show success message
                    showToast('Post shared successfully!');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error sharing post', 'error');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Helper function to create post element
        function createPostElement(post) {
            const article = document.createElement('article');
            article.className = 'feed-card';
            
            let mediaContent = '';
            if (post.media_file) {
                if (post.media_type === 'image') {
                    mediaContent = `
                        <div class="content-preview">
                            <img src="${post.media_file}" alt="Post Image" style="max-width: 100%; height: auto;">
                        </div>`;
                } else if (post.media_type === 'video') {
                    mediaContent = `
                        <div class="content-preview">
                            <video controls style="max-width: 100%; height: auto;">
                                <source src="${post.media_file}" type="video/${post.media_file.split('.').pop()}">
                                Your browser does not support the video tag.
                            </video>
                        </div>`;
                }
            }
            
            article.innerHTML = `
                <div class="card-header">
                    <div class="user-info">
                        <img src="${post.profile_pic || 'default_avatar.jpg'}" alt="${post.username}" class="user-avatar">
                        <div class="user-details">
                            <div class="user-name">
                                ${post.name}
                                <span class="badge-pro">PRO</span>
                            </div>
                            <div class="post-meta">
                                <span class="post-time">Just now</span>
                            </div>
                        </div>
                    </div>
                    <button class="btn-icon">
                        <i class="bi bi-three-dots"></i>
                    </button>
                </div>

                <div class="card-content">
                    <div class="content-text">
                        <p>${post.content}</p>
                    </div>
                    ${mediaContent}
                </div>

                <div class="card-stats">
                    <div class="stat-group">
                        <button class="stat-btn like-btn ${post.liked ? 'liked' : ''}" 
                                onclick="handleLike(this)" 
                                data-post-id="${post.id}">
                            <i class="bi ${post.liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                            <span class="like-count">${post.like_count}</span>
                        </button>
                        <button class="stat-btn" onclick="showComments(${post.id})" data-post-id="${post.id}">
                            <i class="bi bi-chat"></i>
                            <span class="comment-count" data-post-id="${post.id}">${post.comment_count}</span>
                        </button>
                        <button class="stat-btn" onclick="sharePost('${post.id}')">
                            <i class="bi bi-share"></i>
                        </button>
                    </div>
                </div>
            `;
            
            return article;
        }

        // Add toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }, 100);
        }

        function showEventModal() {
            const modal = document.getElementById('createEventModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function closeEventModal() {
            const modal = document.getElementById('createEventModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('eventForm').reset();
            }, 300);
        }

        function showArticleModal() {
            const modal = document.getElementById('createArticleModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function closeArticleModal() {
            const modal = document.getElementById('createArticleModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('articleForm').reset();
            }, 300);
        }

        // Event type selector
        document.querySelectorAll('.event-type').forEach(type => {
            type.addEventListener('click', function() {
                document.querySelectorAll('.event-type').forEach(t => t.classList.remove('selected'));
                this.classList.add('selected');
                
                const locationInput = document.querySelector('.location-input');
                if(this.querySelector('input').value === 'in_person') {
                    locationInput.style.display = 'block';
                } else {
                    locationInput.style.display = 'none';
                }
            });
        });

        // Banner/Cover preview
        function setupImagePreview(inputId, previewClass) {
            document.getElementById(inputId).addEventListener('change', function(e) {
                const file = e.target.files[0];
                if(!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector(`.${previewClass}`);
                    preview.style.backgroundImage = `url(${e.target.result})`;
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                    preview.innerHTML = '';
                }
                reader.readAsDataURL(file);
            });
        }

        setupImagePreview('event_banner', 'banner-placeholder');
        setupImagePreview('article_cover', 'cover-placeholder');

        function filterPosts(filter) {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
            
            // Show loading state
            const feedContent = document.querySelector('.feed-content');
            feedContent.innerHTML = '<div class="loading">Loading posts...</div>';
            
            // Fetch filtered posts
            fetch(`get_filtered_posts.php?filter=${filter}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        feedContent.innerHTML = ''; // Clear loading state
                        
                        data.posts.forEach(post => {
                            const postElement = createPostElement(post);
                            feedContent.appendChild(postElement);
                        });
                        
                        if (data.posts.length === 0) {
                            feedContent.innerHTML = '<div class="no-posts">No posts found</div>';
                        }
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    feedContent.innerHTML = '<div class="error">Error loading posts</div>';
                });
        }

        function createPostElement(post) {
            const article = document.createElement('article');
            article.className = 'feed-card';
            
            // Create post HTML structure
            article.innerHTML = `
                <div class="card-header">
                    <div class="user-info">
                        <img src="${post.profile_pic || 'default_avatar.jpg'}" alt="${post.username}" class="user-avatar">
                        <div class="user-details">
                            <div class="user-name">
                                ${post.username}
                                <span class="badge-pro">PRO</span>
                            </div>
                            <div class="post-meta">
                                <span class="post-time">${formatDate(post.date)}</span>
                            </div>
                        </div>
                    </div>
                    <button class="btn-icon">
                        <i class="bi bi-three-dots"></i>
                    </button>
                </div>
                
                <div class="card-content">
                    <div class="content-text">
                        <p>${post.content}</p>
                    </div>
                    ${post.media_file ? createMediaElement(post.media_file, post.media_type) : ''}
                </div>
                
                <div class="card-stats">
                    <div class="stat-group">
                        <button class="stat-btn like-btn ${post.liked ? 'liked' : ''}" 
                                onclick="handleLike(this)" 
                                data-post-id="${post.id}">
                            <i class="bi ${post.liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                            <span class="like-count">${post.like_count}</span>
                        </button>
                        <button class="stat-btn" onclick="showComments(${post.id})" data-post-id="${post.id}">
                            <i class="bi bi-chat"></i>
                            <span class="comment-count" data-post-id="${post.id}">${post.comment_count}</span>
                        </button>
                        <button class="stat-btn" onclick="sharePost('${post.id}')">
                            <i class="bi bi-share"></i>
                        </button>
                    </div>
                </div>
            `;
            
            return article;
        }

        function createMediaElement(mediaFile, mediaType) {
            if (mediaType === 'image') {
                return `<div class="content-preview">
                    <img src="${mediaFile}" alt="Post Image" style="max-width: 100%; height: auto;">
                </div>`;
            } else if (mediaType === 'video') {
                return `<div class="content-preview">
                    <video controls style="max-width: 100%; height: auto;">
                        <source src="${mediaFile}" type="video/${mediaFile.split('.').pop()}">
                        Your browser does not support the video tag.
                    </video>
                </div>`;
            }
            return '';
        }
    </script>

    <style>
        /* Toast Notification Styles */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.error {
            background: #f44336;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Add these styles */
        .like-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border: none;
            background: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .like-btn:hover {
            background: rgba(0,0,0,0.05);
            border-radius: 4px;
        }

        .like-btn.liked {
            color: #e0245e;
        }

        .like-btn.liked i {
            color: #e0245e;
        }

        .like-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .like-count {
            font-size: 14px;
            color: #666;
        }

        /* Add to your existing CSS in home.php */
        .comments-modal {
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .comments-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            max-height: 400px;
        }

        .comment-item {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }

        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-content {
            flex: 1;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .comment-time {
            color: #666;
            font-size: 0.9em;
        }

        .comment-form {
            padding: 16px;
            border-top: 1px solid #eee;
        }

        .comment-form textarea {
            width: 100%;
            min-height: 80px;
            padding: 8px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .comment-form button {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .loading, .no-comments, .error {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .error {
            color: #f44336;
        }

        /* Add to your existing CSS */
        .story-upload-modal {
            max-width: 500px;
            width: 90%;
        }

        .story-upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .preview-container {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }

        .upload-controls {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .upload-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-label:hover {
            background: #e9ecef;
        }

        .upload-info {
            font-size: 0.9em;
            color: #6c757d;
        }

        .story-card {
            position: relative;
            width: 120px;
            height: 180px;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            flex-shrink: 0;
        }

        .story-card.add-story {
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .story-add-icon {
            font-size: 24px;
            color: var(--primary-color);
        }

        /* Add to your existing CSS */
        .stories-section {
            margin-bottom: 20px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stories-container {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .story-card {
            position: relative;
            width: 120px;
            height: 180px;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .story-card.add-story {
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 2px dashed #ddd;
        }

        .story-add-icon {
            font-size: 24px;
            color: var(--primary-color);
        }

        .upload-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 16px;
        }

        .upload-label:hover {
            background: #e9ecef;
        }

        .preview-container {
            margin: 16px 0;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .spin {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Add to your existing CSS */
        .story-view-modal {
            background: rgba(0, 0, 0, 0.95);
        }

        .story-viewer {
            position: relative;
            max-width: 100%;
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            color: white;
        }

        .story-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 10;
        }

        .story-progress-bar .progress {
            height: 100%;
            background: white;
            width: 0;
            transition: width linear;
        }

        .story-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);
            z-index: 5;
        }

        .story-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .story-avatar img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .story-user-details {
            display: flex;
            flex-direction: column;
        }

        .story-username {
            font-weight: 600;
            font-size: 14px;
        }

        .story-time {
            font-size: 12px;
            opacity: 0.8;
        }

        .story-actions {
            display: flex;
            gap: 16px;
        }

        .story-action-btn,
        .close-story {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
        }

        .story-navigation {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 16px;
            z-index: 5;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .story-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            position: relative;
        }

        .story-content img {
            max-width: 100%;
            max-height: 100vh;
            object-fit: contain;
        }

        .story-content video {
            max-width: 100%;
            max-height: 100vh;
            object-fit: contain;
        }

        .story-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .story-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        }

        .story-reply {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 24px;
        }

        .story-reply input {
            flex: 1;
            background: none;
            border: none;
            color: white;
            outline: none;
            font-size: 14px;
        }

        .story-reply input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .story-reply button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0 8px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
<?php $conn->close(); ?>