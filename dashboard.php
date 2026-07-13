<?php 
session_start();
include('includes/auth_check.php');
include('includes/db.php');

$role = $_SESSION['role'];

switch($role){
    case 'admin':
        header('Location: admin/dashboard.php');
        exit();
    case 'warden':
        header('Location: warden/dashboard.php');
        exit();
    case 'security':
        header('Location: security/dashboard.php');
        exit();
    case 'student':
        header('Location: student/dashboard.php');
        exit();
    default:
        session_destroy();
        header('Location: index.php');
        exit();
}

$error = $success = "";


if(isset($_POST['complaint_submit'])){
    $room_number = intval($_POST['room_number']);
    $designation = $conn->real_escape_string($_POST['designation']);
    $complaint = trim($conn->real_escape_string($_POST['complaint']));
    $user_id = $_SESSION['user_id']; // Use the user_id from session
    
    if($room_number <= 0 || empty($designation) || empty($complaint)){
        $error = "All fields are required. Please fill in all information.";
    } else if(strlen($complaint) < 10) {
        $error = "Please provide a more detailed complaint (at least 10 characters).";
    } else {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, room_number, designation, complaint, status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("iiss", $user_id, $room_number, $designation, $complaint);
        
        if($stmt->execute()) {
            $success = "Complaint submitted successfully. We will address it soon.";
            
            $_POST = array();
        } else {
            $error = "Error submitting complaint. Please try again.";
        }
        $stmt->close();
    }
}


$announcements = $conn->query("
    SELECT a.message, a.created_at, u.username 
    FROM announcements a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");


$recent_complaints = $conn->query("
    SELECT room_number, designation, complaint, status, created_at 
    FROM complaints 
    WHERE user_id = $_SESSION[user_id] 
    ORDER BY created_at DESC 
    LIMIT 5
");


$stats = [];
$stats['total_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'] ?? 0;
$stats['occupied_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'Occupied'")->fetch_assoc()['count'] ?? 0;
$stats['pending_complaints'] = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - StaySmart Hostel</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <div class="dashboard-header">
    <h1>Dashboard</h1>
    <p>Welcome to your hostel management dashboard</p>
  </div>
  
  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🏢</div>
      <h3><?php echo $stats['total_rooms']; ?></h3>
      <p>Total Rooms</p>
    </div>
    <div class="stat-card occupied">
      <div class="stat-icon">🔒</div>
      <h3><?php echo $stats['occupied_rooms']; ?></h3>
      <p>Occupied Rooms</p>
    </div>
    <div class="stat-card available">
      <div class="stat-icon">✅</div>
      <h3><?php echo $stats['total_rooms'] - $stats['occupied_rooms']; ?></h3>
      <p>Available Rooms</p>
    </div>
    <div class="stat-card pending">
      <div class="stat-icon">📋</div>
      <h3><?php echo $stats['pending_complaints']; ?></h3>
      <p>Pending Complaints</p>
    </div>
  </div>
  
  <!-- Announcements -->
  <div class="dashboard-section">
    <h2>📢 Latest Announcements</h2>
    <div class="announcements-container">
      <?php if($announcements && $announcements->num_rows > 0): ?>
        <?php while($row = $announcements->fetch_assoc()): ?>
          <div class="announcement-card">
            <div class="announcement-header">
              <strong><?php echo htmlspecialchars($row['username'] ?? 'Admin'); ?></strong>
              <span class="announcement-date">
                <?php echo date('M d, Y - H:i', strtotime($row['created_at'])); ?>
              </span>
            </div>
            <div class="announcement-content">
              <?php echo htmlspecialchars($row['message']); ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-data">
          <p>No announcements available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Complaint Section -->
  <div class="dashboard-section">
    <h2>📝 Submit a Complaint</h2>
    
    <?php if($error): ?>
      <div class="alert alert-error">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <?php if($success): ?>
      <div class="alert alert-success">
        <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <form method="post" class="complaint-form">
      <div class="form-row">
        <div class="form-group">
          <label for="room_number">Room Number *</label>
          <input type="number" id="room_number" name="room_number" min="1" required
                 value="<?php echo isset($_POST['room_number']) ? $_POST['room_number'] : ''; ?>"
                 placeholder="Enter room number">
        </div>
        
        <div class="form-group">
          <label for="designation">Your Role *</label>
          <select id="designation" name="designation" required>
            <option value="">-- Select Your Role --</option>
            <option value="Student" <?php echo (isset($_POST['designation']) && $_POST['designation'] == 'Student') ? 'selected' : ''; ?>>Student</option>
            <option value="Security" <?php echo (isset($_POST['designation']) && $_POST['designation'] == 'Security') ? 'selected' : ''; ?>>Security</option>
          </select>
        </div>
      </div>
      
      <div class="form-group">
        <label for="complaint">Complaint Details *</label>
        <textarea id="complaint" name="complaint" required rows="4" 
                  placeholder="Please describe your complaint in detail..."><?php echo isset($_POST['complaint']) ? htmlspecialchars($_POST['complaint']) : ''; ?></textarea>
        <small>Minimum 10 characters required</small>
      </div>
      
      <div class="form-actions">
        <button type="submit" name="complaint_submit" class="btn btn-primary">Submit Complaint</button>
        <button type="reset" class="btn btn-secondary">Clear Form</button>
      </div>
    </form>
  </div>
  
  <!-- Recent Complaints -->
  <?php if($recent_complaints && $recent_complaints->num_rows > 0): ?>
  <div class="dashboard-section">
    <h2>📋 Your Recent Complaints</h2>
    <div class="complaints-list">
      <?php while($complaint = $recent_complaints->fetch_assoc()): ?>
        <div class="complaint-card">
          <div class="complaint-header">
            <span class="room-badge">Room <?php echo $complaint['room_number']; ?></span>
            <span class="status-badge status-<?php echo strtolower($complaint['status']); ?>">
              <?php echo $complaint['status']; ?>
            </span>
            <span class="complaint-date">
              <?php echo date('M d, Y', strtotime($complaint['created_at'])); ?>
            </span>
          </div>
          <div class="complaint-content">
            <?php echo htmlspecialchars($complaint['complaint']); ?>
          </div>
          <div class="complaint-meta">
            <small>Filed as: <?php echo $complaint['designation']; ?></small>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<style>

.dashboard-header {
  text-align: center;
  padding: 60px 40px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  margin: -30px -30px 50px -30px;
  border-radius: 0 0 24px 24px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
  position: relative;
  overflow: hidden;
}

.dashboard-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, transparent 0%, rgba(37, 99, 235, 0.1) 100%);
  pointer-events: none;
}

.dashboard-header h1,
.dashboard-header p {
  position: relative;
  z-index: 1;
  color: white;
}

.dashboard-header h1 {
  margin-bottom: 10px;
}

.dashboard-header p {
  font-size: 1.2rem;
  opacity: 0.95;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 25px;
  margin-bottom: 50px;
}

.stat-card {
  background: white;
  padding: 35px 25px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border-left: 5px solid #2563eb;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-left: 5px solid #2563eb;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, transparent 100%);
  border-radius: 0 0 0 100%;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-card.occupied {
  border-left-color: #ef4444;
}

.stat-card.available {
  border-left-color: #10b981;
}

.stat-card.pending {
  border-left-color: #f59e0b;
}

.stat-icon {
  font-size: 2.5rem;
  margin-bottom: 15px;
  filter: grayscale(20%);
}

.stat-card h3 {
  font-size: 3rem;
  margin: 10px 0;
  color: #1a1a1a;
  font-weight: 700;
}

.stat-card p {
  margin: 0;
  color: #64748b;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.025em;
}

.dashboard-section {
  background: white;
  padding: 40px;
  margin-bottom: 35px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.dashboard-section h2 {
  margin-top: 0;
  color: #1a1a1a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 15px;
  font-size: 1.875rem;
  margin-bottom: 30px;
}

.announcements-container {
  max-height: 500px;
  overflow-y: auto;
  padding-right: 10px;
}

.announcements-container::-webkit-scrollbar {
  width: 8px;
}

.announcements-container::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}

.announcements-container::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.announcements-container::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.announcement-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 25px;
  margin-bottom: 20px;
  border-radius: 12px;
  border-left: 5px solid #2563eb;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.announcement-card:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.announcement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 10px;
}

.announcement-header strong {
  color: #1a1a1a;
  font-size: 1.05rem;
}

.announcement-date {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
}

.announcement-content {
  color: #475569;
  line-height: 1.7;
  font-size: 1rem;
}

.no-data {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
}

.no-data p {
  font-size: 1.125rem;
  color: #94a3b8;
}

.complaint-form {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 35px;
  border-radius: 12px;
  margin-top: 25px;
  border: 1px solid #e2e8f0;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 25px;
}

.form-group {
  margin-bottom: 25px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #1a1a1a;
  font-size: 0.9375rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s ease;
  background-color: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group small {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 6px;
  display: block;
  font-weight: 500;
}

.form-group textarea {
  resize: vertical;
  min-height: 120px;
  font-family: inherit;
  line-height: 1.6;
}

.form-actions {
  display: flex;
  gap: 15px;
  margin-top: 30px;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.btn-secondary {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #475569 0%, #334155 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
}

.alert {
  padding: 18px 20px;
  margin-bottom: 25px;
  border-radius: 12px;
  font-weight: 500;
}

.alert-error {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  border-left: 4px solid #dc2626;
  box-shadow: 0 2px 10px rgba(220, 38, 38, 0.1);
}

.alert-success {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
  border-left: 4px solid #10b981;
  box-shadow: 0 2px 10px rgba(16, 185, 129, 0.1);
}

.complaints-list {
  margin-top: 25px;
}

.complaint-card {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  padding: 25px;
  margin-bottom: 20px;
  border-radius: 12px;
  border-left: 5px solid #f59e0b;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.complaint-card:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.complaint-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
  flex-wrap: wrap;
  gap: 12px;
}

.room-badge {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 700;
  letter-spacing: 0.025em;
}

.status-badge {
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-pending {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
}

.status-resolved {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
}

.complaint-date {
  color: #78716c;
  font-size: 0.875rem;
  font-weight: 600;
}

.complaint-content {
  color: #57534e;
  line-height: 1.7;
  margin-bottom: 12px;
  font-size: 1rem;
}

.complaint-meta {
  border-top: 2px solid rgba(0, 0, 0, 0.05);
  padding-top: 12px;
  color: #78716c;
}

.complaint-meta small {
  font-weight: 600;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
  }
  
  .stat-card {
    padding: 25px 20px;
  }
  
  .stat-icon {
    font-size: 2rem;
  }
  
  .stat-card h3 {
    font-size: 2.5rem;
  }
  
  .dashboard-header {
    padding: 40px 20px;
    margin: -15px -15px 30px -15px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .complaint-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .dashboard-section {
    padding: 25px;
  }
}
</style>
</body>
</html>
