<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['student']);
include('../includes/db.php');
include('../includes/navbar.php');

$user_id = $_SESSION['user_id'];

// Get Student Stats
$stats = [];
$stats['room_number'] = $_SESSION['room_number'] ?? 'Not Assigned';
$stats['total_payments'] = $conn->query("SELECT COUNT(*) as count FROM payments WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$stats['my_complaints'] = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$stats['checkins_today'] = $conn->query("SELECT COUNT(*) as count FROM checkins_checkouts WHERE user_id = $user_id AND DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;

$unread_count_result = $conn->query("
    SELECT COUNT(*) as count 
    FROM announcements a 
    WHERE a.id NOT IN (
        SELECT announcement_id 
        FROM announcement_reads 
        WHERE user_id = $user_id
    )
");
$unread_count = $unread_count_result->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="dashboard-header">
    <h1>Student Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
  </div>
  
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🏠</div>
      <h3><?php echo $stats['room_number']; ?></h3>
      <p>Room Number</p>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💳</div>
      <h3><?php echo $stats['total_payments']; ?></h3>
      <p>Total Payments</p>
    </div>
    <a href="complaints.php" class="stat-card complaints-link">
      <div class="stat-icon">📋</div>
      <h3><?php echo $stats['my_complaints']; ?></h3>
      <p>My Complaints</p>
    </a>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <h3><?php echo $stats['checkins_today']; ?></h3>
      <p>Check-ins Today</p>
    </div>
  </div>
    
  <div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-grid">
      <a href="checkins.php" class="action-card">
        <div class="action-icon">✅</div>
        <h3>Check-In/Out</h3>
        <p>Record your entry and exit</p>
      </a>
      <a href="payments.php" class="action-card">
        <div class="action-icon">💳</div>
        <h3>Make Payment</h3>
        <p>Pay hostel fees</p>
      </a>
      <a href="complaints.php" class="action-card">
        <div class="action-icon">📝</div>
        <h3>Make a Complaint</h3>
        <p>Submit your concerns</p>
      </a>

      <a href="room_requests.php" class="action-card">
        <div class="action-icon">🔄</div>
        <h3>Room Change Request</h3>
        <p>Request to change your room</p>
      </a>
    </div>
  </div>
</div>

<!-- Bell Icon -->
<a href="announcements.php" class="announcement-bell">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
  </svg>
  <?php if($unread_count > 0): ?>
    <span class="notification-badge"><?php echo $unread_count; ?></span>
  <?php endif; ?>
</a>

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
  padding-bottom: 100px; 
}

.dashboard-header {
  text-align: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  margin: -30px -30px 40px -30px;
  border-radius: 0 0 20px 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.dashboard-header h1 {
  margin: 0 0 8px 0;
  font-size: 2rem;
}

.dashboard-header p {
  margin: 0;
  font-size: 1rem;
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
  text-decoration: none;
  color: inherit;
  display: block;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-card.complaints-link {
  cursor: pointer;
  border-left-color: #f59e0b;
}

.stat-card.complaints-link:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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

.quick-actions {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.quick-actions h2 {
  margin-top: 0;
  color: #1a1a1a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 12px;
  margin-bottom: 20px;
  font-size: 1.5rem;
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

.announcement-bell {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
  transition: all 0.3s ease;
  z-index: 1000;
  text-decoration: none;
  cursor: pointer;
}

.announcement-bell:hover {
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 8px 30px rgba(37, 99, 235, 0.5);
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  border: 3px solid white;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

@media (max-width: 768px) {
  .container {
    padding: 15px;
    padding-bottom: 100px;
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
  
  .quick-actions {
    padding: 20px;
  }
  
  .action-grid {
    grid-template-columns: 1fr;
  }
  
  .announcement-bell {
    bottom: 20px;
    right: 20px;
    width: 55px;
    height: 55px;
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
