<?php
?>

<aside class="sidebar">
    <div class="logo">
    <a href="home.php" class="nav-item">
        <i class="bi bi-box"></i>
        <span>CONNECTIONZIM</span>
    </div>
    
    <div class="search-container">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search">
    </div>

    <nav class="nav-menu">
        <div class="menu-scroll">
            <a href="home.php" class="nav-item active">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="index.html" class="nav-item">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-folder"></i>
                <span>Projects</span>
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-check2-square"></i>
                <span>Tasks</span>
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-graph-up"></i>
                <span>Reporting</span>
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-people"></i>
                <span>Designers</span>
            </a>
        </div>
    </nav>

    

    <div class="bottom-menu">
        <?php if(isset($_SESSION['user_id'])): 
            // Get user's profile pic
            $user_id = $_SESSION['user_id'];
            $profile_query = "SELECT profile_pic FROM users WHERE id = ?";
            $stmt = $conn->prepare($profile_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $profile_result = $stmt->get_result();
            $user_data = $profile_result->fetch_assoc();
            $profile_pic = $user_data['profile_pic'] ?? 'default_avatar.jpg';
        ?>
            <a href="#" class="nav-item">
                <i class="bi bi-question-circle"></i>
                <span>Support</span>
            </a>
            <a href="#" class="nav-item">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="avatar">
                <div class="user-info">
                    <span class="name"><?php echo $_SESSION['username'] ?? 'Guest'; ?></span>
                    <span class="handle">@<?php echo $_SESSION['username'] ?? 'guest'; ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="auth-buttons">
                <button onclick="showLoginModal()" class="btn-login">Login</button>
                <button onclick="showSignupModal()" class="btn-signup">Sign Up</button>
            </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Login</h2>
            <span class="close">&times;</span>
        </div>
        <form id="loginForm">
            <div class="form-group">
                <label for="login_username">Username or Email</label>
                <input type="text" id="login_username" name="username" required>
            </div>
            <div class="form-group">
                <label for="login_password">Password</label>
                <input type="password" id="login_password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Login</button>
            </div>
        </form>
        <div id="loginMessage" style="margin-top: 10px; color: red;"></div>
    </div>
</div>

<!-- Signup Modal -->
<div id="signupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create Account</h2>
            <span class="close">&times;</span>
        </div>
        <form id="signupForm" method="POST" action="signup.php">
            <div class="form-group">
                <label for="signup_fullname">Full Name</label>
                <input type="text" id="signup_fullname" name="fullname" required>
            </div>
            <div class="form-group">
                <label for="signup_email">Email</label>
                <input type="email" id="signup_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="signup_username">Username</label>
                <input type="text" id="signup_username" name="username" required>
            </div>
            <div class="form-group">
                <label for="signup_password">Password</label>
                <input type="password" id="signup_password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functionality
function showLoginModal() {
    document.getElementById('loginModal').style.display = 'flex';
}

function showSignupModal() {
    document.getElementById('signupModal').style.display = 'flex';
}

function showForgotPasswordModal() {
    // Implement forgot password functionality
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Close button functionality
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

// Form submissions
document.getElementById('loginForm').onsubmit = async function(e) {
    e.preventDefault();
    
    const messageDiv = document.getElementById('loginMessage');
    const formData = new FormData(this);
    
    try {
        const response = await fetch('login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Login response:', data); // For debugging
        
        if (data.success) {
            messageDiv.style.color = 'green';
            messageDiv.textContent = 'Login successful!';
            window.location.href = 'home.php';
        } else {
            messageDiv.style.color = 'red';
            messageDiv.textContent = data.message || 'Login failed';
        }
    } catch (err) {
        console.error('Login error:', err);
        messageDiv.style.color = 'red';
        messageDiv.textContent = 'Error during login. Please try again.';
    }
};

document.getElementById('signupForm').onsubmit = async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    try {
        const response = await fetch('signup.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Account created successfully! Please login.');
            this.closest('.modal').style.display = 'none';
            showLoginModal();
        } else {
            alert(data.message || 'Signup failed');
        }
    } catch (err) {
        alert('Error during signup');
    }
};
</script>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.close {
    font-size: 24px;
    cursor: pointer;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
}

.auth-buttons {
    display: flex;
    gap: 8px;
    padding: 16px;
}

.btn-login,
.btn-signup {
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
}

.btn-login {
    background: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-signup {
    background: var(--primary-color);
    border: none;
    color: white;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: #dc3545;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background: rgba(220, 53, 69, 0.1);
}

.logout-btn i {
    font-size: 1.2em;
}
</style> 