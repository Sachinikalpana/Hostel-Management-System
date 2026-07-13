<?php
if(!isset($_SESSION)){
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$full_name = $_SESSION['full_name'] ?? 'User';
$name_parts = explode(' ', $full_name);
$initials = '';
if(count($name_parts) >= 2){
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
} else {
    $initials = strtoupper(substr($full_name, 0, 2));
}

$current_dir = basename(getcwd());
$base_path = ($current_dir === 'admin' || $current_dir === 'warden' || $current_dir === 'security' || $current_dir === 'student') ? '../' : '';
?>
<nav class="main-nav">
  <div class="nav-container">
    <!-- Mobile Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
      <span></span>
      <span></span>
      <span></span>
    </button>
    
    <div class="nav-brand">
      <h3>StaySmart Hostel</h3>
    </div>
    
    <div class="mobile-menu" id="mobileMenu">
      <ul class="nav-links">
        <?php if($is_logged_in): ?>
          <?php if($role == 'admin'): ?>
            <li><a href="<?php echo $base_path; ?>admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>admin/users.php">Users</a></li>
            <li><a href="<?php echo $base_path; ?>admin/students.php">Students</a></li>
            <li><a href="<?php echo $base_path; ?>admin/rooms.php">Rooms</a></li>
            <li><a href="<?php echo $base_path; ?>admin/announcements.php">Announcements</a></li>
            <li><a href="<?php echo $base_path; ?>admin/complaints.php">Complaints</a></li>
          <?php elseif($role == 'warden'): ?>
            <li><a href="<?php echo $base_path; ?>warden/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>warden/students.php">Students</a></li>
            <li><a href="<?php echo $base_path; ?>warden/rooms.php">Rooms</a></li>
            <li><a href="<?php echo $base_path; ?>warden/announcements.php">Announcements</a></li>
            <li><a href="<?php echo $base_path; ?>warden/complaints.php">Complaints</a></li>
          <?php elseif($role == 'security'): ?>
            <li><a href="<?php echo $base_path; ?>security/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>security/announcements.php">Announcements</a></li>
            <li><a href="<?php echo $base_path; ?>security/checkins.php">Check-Ins/Outs</a></li>
          <?php elseif($role == 'student'): ?>
            <li><a href="<?php echo $base_path; ?>student/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>student/checkins.php">Check-In/Out</a></li>
            <li><a href="<?php echo $base_path; ?>student/payments.php">Payments</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
    
    <?php if($is_logged_in): ?>
      <div class="nav-user-section">
        <div class="user-profile">
          <div class="profile-avatar"><?php echo $initials; ?></div>
          <div class="profile-info">
            <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
            <span class="user-role"><?php echo ucfirst($role); ?></span>
          </div>
        </div>
        <a href="<?php echo $base_path; ?>logout.php" class="logout-btn" title="Logout">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<style>
.main-nav {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  padding: 12px 0;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.nav-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 30px;
}

.nav-brand h3 {
  color: white;
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.mobile-menu-toggle {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  z-index: 1001;
}

.mobile-menu-toggle span {
  display: block;
  width: 25px;
  height: 3px;
  background: white;
  border-radius: 3px;
  transition: all 0.3s ease;
}

.mobile-menu-toggle.active span:nth-child(1) {
  transform: rotate(45deg) translate(8px, 8px);
}

.mobile-menu-toggle.active span:nth-child(2) {
  opacity: 0;
}

.mobile-menu-toggle.active span:nth-child(3) {
  transform: rotate(-45deg) translate(7px, -7px);
}

.mobile-menu {
  flex: 1;
  display: flex;
  justify-content: center;
}

.nav-links {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 5px;
}

.nav-links li {
  margin: 0;
}

.nav-links a {
  color: white;
  text-decoration: none;
  padding: 10px 16px;
  border-radius: 6px;
  transition: all 0.3s ease;
  font-weight: 500;
  display: block;
  font-size: 0.95rem;
}

.nav-links a:hover {
  background: rgba(255, 255, 255, 0.15);
}

.nav-user-section {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px 8px 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50px;
  backdrop-filter: blur(10px);
}

.profile-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.profile-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  color: white;
  font-weight: 600;
  font-size: 0.95rem;
  line-height: 1.2;
}

.user-role {
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  transition: all 0.3s ease;
  cursor: pointer;
  border: none;
}

.logout-btn:hover {
  background: rgba(239, 68, 68, 0.3);
  color: #fecaca;
  transform: scale(1.05);
}

/* Mobile Menu */
@media (max-width: 768px) {
  .nav-container {
    padding: 0 15px;
  }
  
  /* Reorder */
  .nav-container {
    display: grid;
    grid-template-columns: auto 1fr auto;
    grid-template-areas: "toggle brand user";
  }
  
  .mobile-menu-toggle {
    grid-area: toggle;
  }
  
  .nav-brand {
    grid-area: brand;
  }
  
  .nav-user-section {
    grid-area: user;
  }
  
  .nav-brand h3 {
    font-size: 1.2rem;
  }
  
  .mobile-menu-toggle {
    display: flex;
  }
  
  .mobile-menu {
    position: fixed;
    top: 60px;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    grid-column: 1 / -1;
  }
  
  .mobile-menu.active {
    max-height: 400px;
  }
  
  .nav-links {
    flex-direction: column;
    padding: 20px;
    gap: 0;
  }
  
  .nav-links li {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .nav-links li:last-child {
    border-bottom: none;
  }
  
  .nav-links a {
    padding: 15px;
    display: block;
    font-size: 1rem;
  }
  
  .profile-info {
    display: none;
  }
  
  .user-profile {
    padding: 8px;
  }
  
  .logout-btn {
    width: 36px;
    height: 36px;
  }
}

@media (max-width: 480px) {
  .main-nav {
    padding: 8px 0;
  }
  
  .nav-container {
    padding: 0 10px;
    gap: 10px;
  }
  
  .nav-brand h3 {
    font-size: 1rem;
  }
  
  .profile-avatar {
    width: 36px;
    height: 36px;
    font-size: 0.8rem;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', function() {
      menuToggle.classList.toggle('active');
      mobileMenu.classList.toggle('active');
    });
    
    document.addEventListener('click', function(event) {
      if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
        menuToggle.classList.remove('active');
        mobileMenu.classList.remove('active');
      }
    });
    
    const menuLinks = mobileMenu.querySelectorAll('a');
    menuLinks.forEach(link => {
      link.addEventListener('click', function() {
        menuToggle.classList.remove('active');
        mobileMenu.classList.remove('active');
      });
    });
  }
});
</script>
