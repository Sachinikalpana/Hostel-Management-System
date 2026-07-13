<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['security']);
include('../includes/db.php');
include('../includes/navbar.php');

$user_id = $_SESSION['user_id'];

// Get Security Stats
$stats = [];
$stats['checkins_today'] = $conn->query("SELECT COUNT(*) as count FROM checkins_checkouts WHERE DATE(created_at) = CURDATE() AND type = 'Check-In'")->fetch_assoc()['count'] ?? 0;
$stats['checkouts_today'] = $conn->query("SELECT COUNT(*) as count FROM checkins_checkouts WHERE DATE(created_at) = CURDATE() AND type = 'Check-Out'")->fetch_assoc()['count'] ?? 0;
$stats['my_complaints'] = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$stats['total_announcements'] = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'] ?? 0;

// Recnt Announcements
$announcements = $conn->query("
    SELECT a.title, a.message, a.created_at, u.full_name 
    FROM announcements a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Security Dashboard - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="dashboard-header">
    <h1>Security Dashboard</h1>
    <p>Monitor hostel security and student movements</p>
  </div>
  
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <h3><?php echo $stats['checkins_today']; ?></h3>
      <p>Check-Ins Today</p>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🚪</div>
      <h3><?php echo $stats['checkouts_today']; ?></h3>
      <p>Check-Outs Today</p>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📋</div>
      <h3><?php echo $stats['my_complaints']; ?></h3>
      <p>My Complaints</p>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📢</div>
      <h3><?php echo $stats['total_announcements']; ?></h3>
      <p>Announcements</p>
    </div>
  </div>
  
  <div class="dashboard-section">
    <h2>📢 Latest Announcements</h2>
    <div class="announcements-container">
      <?php if($announcements && $announcements->num_rows > 0): ?>
        <?php while($row = $announcements->fetch_assoc()): ?>
          <div class="announcement-card">
            <div class="announcement-header">
              <strong><?php echo htmlspecialchars($row['title']); ?></strong>
              <span class="announcement-date">
                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
              </span>
            </div>
            <div class="announcement-content">
              <?php echo htmlspecialchars($row['message']); ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-data">
          <p>No announcements available.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-grid">
      <a href="checkins.php" class="action-card">
        <div class="action-icon">📋</div>
        <h3>View Check-Ins/Outs</h3>
        <p>Monitor student movements</p>
      </a>
      <a href="announcements.php" class="action-card">
        <div class="action-icon">📢</div>
        <h3>View Announcements</h3>
        <p>Read hostel announcements</p>
      </a>
      <!-- Added Make a Complaint quick action for security -->
      <a href="complaints.php" class="action-card">
        <div class="action-icon">📝</div>
        <h3>Make a Complaint</h3>
        <p>Report security concerns</p>
      </a>
    </div>
  </div>
</div>

<style>
body {
  background: #f8fafc;
  margin: 0;
  padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 30px;
}

.dashboard-header {
  text-align: center;
  padding: 60px 40px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  margin: -30px -30px 50px -30px;
  border-radius: 0 0 24px 24px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.dashboard-header h1 {
  margin: 0 0 10px 0;
  font-size: 2.5rem;
}

.dashboard-header p {
  margin: 0;
  font-size: 1.2rem;
  opacity: 0.9;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 15px;
  margin-bottom: 35px;
}

.stat-card {
  background: white;
  padding: 20px 15px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-left: 4px solid #2563eb;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  font-size: 2rem;
  margin-bottom: 8px;
}

.stat-card h3 {
  font-size: 2rem;
  margin: 8px 0;
  color: #1a1a1a;
  font-weight: 700;
}

.stat-card p {
  margin: 0;
  color: #64748b;
  font-weight: 600;
  font-size: 0.875rem;
}

.dashboard-section {
  background: white;
  padding: 40px;
  margin-bottom: 35px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.dashboard-section h2 {
  margin-top: 0;
  color: #1a1a1a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 15px;
  margin-bottom: 30px;
}

.announcements-container {
  max-height: 500px;
  overflow-y: auto;
}

.announcement-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 25px;
  margin-bottom: 20px;
  border-radius: 12px;
  border-left: 5px solid #2563eb;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.announcement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.announcement-date {
  color: #64748b;
  font-size: 0.875rem;
}

.announcement-content {
  color: #475569;
  line-height: 1.7;
}

.no-data {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
}

.quick-actions {
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.quick-actions h2 {
  margin-top: 0;
  color: #1a1a1a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 15px;
  margin-bottom: 30px;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.action-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 25px;
  border-radius: 10px;
  text-decoration: none;
  color: inherit;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
  text-align: center;
}

.action-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  border-color: #2563eb;
}

.action-icon {
  font-size: 2.5rem;
  margin-bottom: 12px;
}

.action-card h3 {
  margin: 0 0 8px 0;
  color: #1a1a1a;
  font-size: 1.125rem;
}

.action-card p {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .container {
    padding: 15px;
  }
  
  .dashboard-header {
    padding: 30px 15px;
    margin: -15px -15px 25px -15px;
  }
  
  .dashboard-header h1 {
    font-size: 1.5rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
  }
  
  .stat-icon {
    font-size: 1.5rem;
  }
  
  .stat-card h3 {
    font-size: 1.5rem;
  }
  
  .dashboard-section,
  .quick-actions {
    padding: 20px;
  }
  
  .action-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
</body>
</html>
