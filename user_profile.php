<?php
session_start();
require_once 'db_connect.php';

// Get user ID from URL or use logged in user's ID
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? 0);

if (!$profile_user_id) {
    header('Location: login.php');
    exit;
}

// Get user details with counts
$user_query = "SELECT 
    u.id,
    u.username,
    u.email,
    u.full_name,
    u.profile_pic,
    u.bio,
    (SELECT COUNT(*) FROM post WHERE user_id = u.id) as post_count,
    (SELECT COUNT(*) FROM followers WHERE following_id = u.id) as followers_count,
    (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count
FROM users u 
WHERE u.id = ?";

$stmt = $conn->prepare($user_query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    die("User not found");
}

// Set default values if any field is null
$user_data = array_merge([
    'username' => 'Unknown',
    'full_name' => 'Unknown User',
    'profile_pic' => 'default_avatar.jpg',
    'bio' => '',
    'post_count' => 0,
    'followers_count' => 0,
    'following_count' => 0
], $user_data ?? []);

// Get user's posts
$posts_query = "SELECT p.*, 
                u.username,
                u.profile_pic,
                COALESCE(COUNT(DISTINCT l.id), 0) as like_count,
                COALESCE(COUNT(DISTINCT c.id), 0) as comment_count
                FROM post p 
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN likes l ON p.id = l.post_id
                LEFT JOIN comments c ON p.id = c.post_id
                WHERE p.user_id = ?
                GROUP BY p.id
                ORDER BY p.date DESC";

$stmt = $conn->prepare($posts_query);
if ($stmt === false) {
    die("Error preparing posts query: " . $conn->error . " - Query: " . $posts_query);
}

try {
    $stmt->bind_param("i", $profile_user_id);
    $stmt->execute();
    $posts = $stmt->get_result();
} catch (Exception $e) {
    die("Error executing posts query: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_data['username']); ?>'s Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .dashboard-container {
            padding: 20px;
            margin-left: 300px;
            max-width: 1200px;
            margin-right: auto;
        }

        .profile-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-details h1 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 20px;
            max-width: 100%;
        }

        .posts-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .post-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }

        .post-card:last-child {
            border-bottom: none;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .post-content {
            word-wrap: break-word;
            overflow-wrap: break-word;
            margin-bottom: 10px;
        }

        .post-actions {
            display: flex;
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            cursor: pointer;
        }

        .action-btn:hover {
            color: var(--primary-color);
        }

        .edit-profile-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
        }

        .edit-profile-btn:hover {
            opacity: 0.9;
        }

        .post-media {
            margin: 10px 0;
        }

        .post-media img,
        .post-media video {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-container {
                padding: 10px;
            }
        }

        .modal {
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

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="profile-header">
            <div class="profile-info">
                <img src="<?php echo htmlspecialchars($user_data['profile_pic'] ?? 'default_avatar.jpg'); ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user_data['full_name'] ?? 'Unknown User'); ?></h1>
                    <p>@<?php echo htmlspecialchars($user_data['username'] ?? 'unknown'); ?></p>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo (int)($user_data['post_count'] ?? 0); ?></div>
                            <div class="stat-label">Posts</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo (int)($user_data['followers_count'] ?? 0); ?></div>
                            <div class="stat-label">Followers</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo (int)($user_data['following_count'] ?? 0); ?></div>
                            <div class="stat-label">Following</div>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id): ?>
                        <button class="edit-profile-btn" onclick="showEditProfileModal()">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="posts-section">
                <h2>Posts</h2>
                <?php if($posts->num_rows > 0): ?>
                    <?php while($post = $posts->fetch_assoc()): ?>
                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <div class="post-time"><?php echo date('M d, Y', strtotime($post['date'])); ?></div>
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                                    <div class="post-actions">
                                        <span class="action-btn edit-post" onclick="editPost(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </span>
                                        <span class="action-btn delete-post" onclick="deletePost(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-trash"></i> Delete
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <?php echo htmlspecialchars($post['content']); ?>
                                <?php if($post['media_file']): ?>
                                    <div class="post-media">
                                        <?php if($post['media_type'] == 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($post['media_file']); ?>" alt="Post image">
                                        <?php else: ?>
                                            <video controls>
                                                <source src="<?php echo htmlspecialchars($post['media_file']); ?>" type="video/mp4">
                                            </video>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-footer">
                                <div class="post-stats">
                                    <span><i class="bi bi-heart"></i> <?php echo $post['like_count']; ?></span>
                                    <span><i class="bi bi-chat"></i> <?php echo $post['comment_count']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No posts yet.</p>
                <?php endif; ?>
            </div>

            <div class="sidebar-section">
                <h3>About</h3>
                <p><?php echo htmlspecialchars($user_data['bio'] ?? 'No bio available'); ?></p>
                
                <!-- Add more sidebar sections as needed -->
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditProfileModal()">&times;</span>
            <h2>Edit Profile</h2>
            <form id="editProfileForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/*">
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function showEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'flex';
        }

        function closeEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Handle profile edit form submission
        document.getElementById('editProfileForm').onsubmit = async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if(data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating profile');
                }
            } catch(err) {
                console.error('Error:', err);
                alert('Error updating profile');
            }
        };

        // Delete post function
        function deletePost(postId) {
            if(confirm('Are you sure you want to delete this post?')) {
                fetch('delete_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ post_id: postId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        document.querySelector(`[data-post-id="${postId}"]`).remove();
                    } else {
                        alert(data.message || 'Error deleting post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting post');
                });
            }
        }
    </script>
</body>
</html> 