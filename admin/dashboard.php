<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['admin']);
include('../includes/db.php');
include('../includes/navbar.php');

$stats = [];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'] ?? 0;
$stats['total_wardens'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'warden'")->fetch_assoc()['count'] ?? 0;
$stats['total_security'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'security'")->fetch_assoc()['count'] ?? 0;
$stats['total_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'] ?? 0;
$stats['available_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'Available'")->fetch_assoc()['count'] ?? 0;
$stats['occupied_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'Occupied'")->fetch_assoc()['count'] ?? 0;
$stats['maintenance_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'Maintenance'")->fetch_assoc()['count'] ?? 0;
$stats['pending_complaints'] = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$stats['pending_requests'] = $conn->query("SELECT COUNT(*) as count FROM room_change_requests WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;

$rooms_result = $conn->query("SELECT room_number, room_type, status FROM rooms ORDER BY room_number ASC");
$rooms = [];
while($room = $rooms_result->fetch_assoc()){
    $rooms[] = $room;
}

// Recnt actvity
$recent_registrations = $conn->query("
    SELECT full_name, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="dashboard-header">
    <h1>Admin Dashboard</h1>
    <p>Complete system oversight and mangement</p>
  </div>
  
  <div class="stats-grid">
    <a href="users.php" class="stat-card stat-link">
      <div class="stat-icon">👥</div>
      <h3><?php echo $stats['total_users']; ?></h3>
      <p>Total Users</p>
    </a>
    <a href="students.php" class="stat-card stat-link">
      <div class="stat-icon">🎓</div>
      <h3><?php echo $stats['total_students']; ?></h3>
      <p>Students</p>
    </a>
    <a href="users.php" class="stat-card stat-link">
      <div class="stat-icon">👮</div>
      <h3><?php echo $stats['total_wardens']; ?></h3>
      <p>Wardens</p>
    </a>
    <a href="users.php" class="stat-card stat-link">
      <div class="stat-icon">🔐</div>
      <h3><?php echo $stats['total_security']; ?></h3>
      <p>Security</p>
    </a>
    <a href="rooms.php" class="stat-card stat-link">
      <div class="stat-icon">🏢</div>
      <h3><?php echo $stats['total_rooms']; ?></h3>
      <p>Total Rooms</p>
    </a>
    <div class="stat-card available">
      <div class="stat-icon">✅</div>
      <h3><?php echo $stats['available_rooms']; ?></h3>
      <p>Available</p>
    </div>
    <div class="stat-card occupied">
      <div class="stat-icon">🔒</div>
      <h3><?php echo $stats['occupied_rooms']; ?></h3>
      <p>Occupied</p>
    </div>
    <div class="stat-card maintenance">
      <div class="stat-icon">🔧</div>
      <h3><?php echo $stats['maintenance_rooms']; ?></h3>
      <p>Maintenance</p>
    </div>
    <a href="complaints.php" class="stat-card stat-link pending">
      <div class="stat-icon">📋</div>
      <h3><?php echo $stats['pending_complaints']; ?></h3>
      <p>Pending Complaints</p>
    </a>
    <a href="room_requests.php" class="stat-card stat-link pending">
      <div class="stat-icon">🔄</div>
      <h3><?php echo $stats['pending_requests']; ?></h3>
      <p>Room Requests</p>
    </a>
  </div>
  
  <!-- Room Map -->
  <div class="dashboard-section">
    <h2>Room Map Overview</h2>
    <div class="room-legend">
      <span class="legend-item"><span class="legend-color" style="background: #10b981;"></span> Available</span>
      <span class="legend-item"><span class="legend-color" style="background: #ef4444;"></span> Occupied</span>
      <span class="legend-item"><span class="legend-color" style="background: #f59e0b;"></span> Maintenance</span>
      <span class="legend-item"><span class="legend-color" style="background: #8b5cf6;"></span> Sick Room</span>
    </div>
    <div class="room-map-dashboard">
      <?php foreach($rooms as $room): ?>
        <?php
          $color_class = 'room-available';
          if($room['status'] == 'Occupied') $color_class = 'room-occupied';
          if($room['status'] == 'Maintenance') $color_class = 'room-maintenance';
          if($room['room_type'] == 'Sick Room') $color_class = 'room-sick';
        ?>
        <div class="room-box-dashboard <?php echo $color_class; ?>" title="Room <?php echo $room['room_number']; ?> - <?php echo $room['status']; ?>">
          <?php echo $room['room_number']; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align: center; margin-top: 25px;">
      <a href="rooms.php" class="btn-manage-rooms">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="3" y1="9" x2="21" y2="9"></line>
          <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        Click here to manage rooms
      </a>
    </div>
  </div>
  
  <div class="dashboard-section">
    <h2>Recent User Registrations</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php if($recent_registrations && $recent_registrations->num_rows > 0): ?>
            <?php while($user = $recent_registrations->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" style="text-align: center;">No recent registrations</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-grid">
      <a href="users.php" class="action-card">
        <div class="action-icon">👥</div>
        <h3>Manage Users</h3>
        <p>Create and manage user accounts</p>
      </a>
      <a href="students.php" class="action-card">
        <div class="action-icon">🎓</div>
        <h3>Manage Students</h3>
        <p>Assign rooms and update profiles</p>
      </a>
      <a href="rooms.php" class="action-card">
        <div class="action-icon">🏢</div>
        <h3>Manage Rooms</h3>
        <p>Update room status and assignments</p>
      </a>
      <a href="announcements.php" class="action-card">
        <div class="action-icon">📢</div>
        <h3>Announcements</h3>
        <p>Post and manage announcements</p>
      </a>
      <a href="complaints.php" class="action-card">
        <div class="action-icon">📝</div>
        <h3>Complaints</h3>
        <p>View and resolve complaints</p>
      </a>
      <a href="payments.php" class="action-card">
        <div class="action-icon">💳</div>
        <h3>Payments</h3>
        <p>Monitor payment records</p>
      </a>
      <a href="room_requests.php" class="action-card">
        <div class="action-icon">🔄</div>
        <h3>Room Requests</h3>
        <p>Process room change requests</p>
      </a>
      <a href="checkins.php" class="action-card">
        <div class="action-icon">✅</div>
        <h3>Check-Ins/Outs</h3>
        <p>Monitor student movements</p>
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

/* Mobile */
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

.stat-card.stat-link {
  cursor: pointer;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-card.available {
  border-left-color: #10b981;
}

.stat-card.occupied {
  border-left-color: #ef4444;
}

.stat-card.maintenance {
  border-left-color: #f59e0b;
}

.stat-card.pending {
  border-left-color: #f59e0b;
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

/* Room map styles */
.room-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f8fafc;
  border-radius: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
}

.legend-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  display: inline-block;
}

.room-map-dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
  gap: 12px;
  padding: 10px;
}

.room-box-dashboard {
  width: 70px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1rem;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.room-box-dashboard:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.room-available {
  background: #d1fae5;
  color: #065f46;
  border: 2px solid #10b981;
}

.room-occupied {
  background: #fee2e2;
  color: #991b1b;
  border: 2px solid #dc2626;
}

.room-maintenance {
  background: #fef3c7;
  color: #92400e;
  border: 2px solid #f59e0b;
}

.room-sick {
  background: #e9d5ff;
  color: #6b21a8;
  border: 2px solid #a855f7;
}

.dashboard-section {
  background: white;
  padding: 30px;
  margin-bottom: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.dashboard-section h2 {
  margin-top: 0;
  color: #1a1a1a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 12px;
  margin-bottom: 20px;
  font-size: 1.5rem;
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
  
  /* Mobile Room Map */
  .room-map-dashboard {
    grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
    gap: 8px;
  }
  
  .room-box-dashboard {
    width: 55px;
    height: 55px;
    font-size: 0.85rem;
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
  
  .room-map-dashboard {
    grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
    gap: 6px;
  }
  
  .room-box-dashboard {
    width: 45px;
    height: 45px;
    font-size: 0.75rem;
  }
}

.btn-manage-rooms {
  display: inline-flex;
  align-items: center;
  padding: 12px 24px;
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.btn-manage-rooms:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
  background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
}
</style>
</body>
</html>
