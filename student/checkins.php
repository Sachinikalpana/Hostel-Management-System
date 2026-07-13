<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['student']);
include('../includes/db.php');
include('../includes/time_helper.php');
include('../includes/navbar.php');

$error = $success = "";
$user_id = $_SESSION['user_id'];

$user_result = $conn->query("SELECT full_name, student_number, room_number FROM users WHERE id = $user_id");
$user_data = $user_result->fetch_assoc();
$student_name = $user_data['full_name'];
$student_number = $user_data['student_number'];
$room_number = $user_data['room_number'] ?? 0;

$status_result = $conn->query("SELECT current_status FROM user_checkin_status WHERE user_id = $user_id");
if($status_result && $status_result->num_rows > 0){
    $current_status = $status_result->fetch_assoc()['current_status'];
} else {
    // Get Currnt Status
    $conn->query("INSERT INTO user_checkin_status (user_id, current_status) VALUES ($user_id, 'Checked-Out')");
    $current_status = 'Checked-Out';
}

if(isset($_POST['checkin_submit'])){
    if($current_status === 'Checked-In'){
        $error = "You are already checked in! Please check out first before checking in again.";
    } else {
        $currentHour = getSriLankaHour();
        $currentTime = formatSriLankaDateTime();
        
        if($currentHour >= 21){
            $error = "Check-in is not allowed after 9:00 PM (Current time: " . date('H:i', strtotime($currentTime)) . ").";
        } else {
            $stmt = $conn->prepare("INSERT INTO checkins_checkouts (user_id, student_name, student_number, room_number, type, place, time) VALUES (?, ?, ?, ?, 'Check-In', '', ?)");
            $stmt->bind_param('issis', $user_id, $student_name, $student_number, $room_number, $currentTime);
            
            if($stmt->execute()){
                $conn->query("UPDATE user_checkin_status SET current_status = 'Checked-In', last_checkin_time = '$currentTime' WHERE user_id = $user_id");
                $current_status = 'Checked-In';
                $success = "Check-in recorded successfully at " . date('h:i A', strtotime($currentTime)) . ".";
            } else {
                $error = "Failed to record check-in.";
            }
            $stmt->close();
        }
    }
}

if(isset($_POST['checkout_submit'])){
    if($current_status === 'Checked-Out'){
        $error = "You are already checked out! Please check in first before checking out again.";
    } else {
        $place = trim($_POST['place'] ?? '');
        $currentHour = getSriLankaHour();
        $currentTime = formatSriLankaDateTime();
        
        if($currentHour < 6){
            $error = "Check-out is not allowed before 6:00 AM (Current time: " . date('H:i', strtotime($currentTime)) . ").";
        } else {
            $stmt = $conn->prepare("INSERT INTO checkins_checkouts (user_id, student_name, student_number, room_number, type, place, time) VALUES (?, ?, ?, ?, 'Check-Out', ?, ?)");
            $stmt->bind_param('ississ', $user_id, $student_name, $student_number, $room_number, $place, $currentTime);
            
            if($stmt->execute()){
                $conn->query("UPDATE user_checkin_status SET current_status = 'Checked-Out', last_checkout_time = '$currentTime' WHERE user_id = $user_id");
                $current_status = 'Checked-Out';
                $success = "Check-out recorded successfully at " . date('h:i A', strtotime($currentTime)) . ".";
            } else {
                $error = "Failed to record check-out.";
            }
            $stmt->close();
        }
    }
}

// Get Histry
$history = $conn->query("
    SELECT * FROM checkins_checkouts 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 20
");

$currentSLTime = formatSriLankaDateTime('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-In/Out - Student Portal</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>🚪 Check-In / Check-Out</h1>
    <p>Quick one-tap entry and exit recording</p>
    
    <!-- Clock -->
    
    <div class="digital-clock" id="digitalClock">
      Loading time...
    </div>
    <p style="font-size: 0.95rem; opacity: 0.85; margin-top: 5px;">
      Sri Lanka Time - Server Synchronized
    </p>
  </div>
  
  <!-- Show Current Status -->
  <div class="status-display">
    <div class="status-badge status-<?php echo strtolower(str_replace('-', '', $current_status)); ?>">
      <?php if($current_status === 'Checked-In'): ?>
        <span class="status-icon">🟢</span> Currently Checked In
      <?php else: ?>
        <span class="status-icon">🔴</span> Currently Checked Out
      <?php endif; ?>
    </div>
  </div>
  
  <div class="checkin-actions">
    <?php if($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="action-cards">
      <div class="action-card-large <?php echo ($current_status === 'Checked-In') ? 'disabled' : ''; ?>">
        <div class="action-icon-large">✅</div>
        <h2>Check-In</h2>
        <p class="info-text">Record your hostel entry (Before 9:00 PM)</p>
        <?php if($current_status === 'Checked-In'): ?>
          <button class="btn btn-disabled btn-large" disabled>
            Already Checked In
          </button>
        <?php else: ?>
          <form method="post" style="margin: 0;">
            <button type="submit" name="checkin_submit" class="btn btn-primary btn-large">
              <span style="font-size: 1.5rem; margin-right: 10px;">✅</span>
              Tap to Check-In
            </button>
          </form>
        <?php endif; ?>
      </div>
      
      <div class="action-card-large <?php echo ($current_status === 'Checked-Out') ? 'disabled' : ''; ?>">
        <div class="action-icon-large">🚪</div>
        <h2>Check-Out</h2>
        <p class="info-text">Record your hostel exit (After 6:00 AM)</p>
        <?php if($current_status === 'Checked-Out'): ?>
          <button class="btn btn-disabled btn-large" disabled>
            Already Checked Out
          </button>
        <?php else: ?>
          <form method="post" style="margin: 0;">
            <div class="form-group" style="margin-bottom: 15px;">
              <input type="text" name="place" placeholder="Where are you going? (Optional)" 
                     style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            </div>
            <button type="submit" name="checkout_submit" class="btn btn-secondary btn-large">
              <span style="font-size: 1.5rem; margin-right: 10px;">🚪</span>
              Tap to Check-Out
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="info-box-center">
      <strong>ℹ️ Auto-filled Information:</strong>
      <ul style="margin: 10px 0 0 0; padding-left: 20px;">
        <li>Name: <?php echo htmlspecialchars($student_name); ?></li>
        <li>Student Number: <?php echo htmlspecialchars($student_number ?? 'Not Set'); ?></li>
        <li>Room: <?php echo $room_number ? $room_number : 'Not Assigned'; ?></li>
        <li>Time: Current server time (Sri Lanka timezone)</li>
      </ul>
    </div>
  </div>
  
  <?php if($history && $history->num_rows > 0): ?>
  <div class="dashboard-section">
    <h2>📊 Your Check-In/Out History</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Date & Time</th>
            <th>Destination</th>
            <th>Recorded At</th>
          </tr>
        </thead>
        <tbody>
          <?php while($record = $history->fetch_assoc()): ?>
          <tr>
            <td>
              <span class="type-badge type-<?php echo strtolower(str_replace('-', '', $record['type'])); ?>">
                <?php echo $record['type']; ?>
              </span>
            </td>
            <td><?php echo date('M d, Y h:i A', strtotime($record['time'])); ?></td>
            <td><?php echo $record['place'] ? htmlspecialchars($record['place']) : 'N/A'; ?></td>
            <td><?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Clock -->
<script>
function updateClock() {
  // SL Time
  const now = new Date();
  const options = {
    timeZone: 'Asia/Colombo',
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  };
  
  const timeString = now.toLocaleString('en-US', options);
  document.getElementById('digitalClock').textContent = timeString;
}

// Update Clock
updateClock();
setInterval(updateClock, 1000);
</script>

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

.page-header {
  text-align: center;
  padding: 60px 40px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  margin: -30px -30px 50px -30px;
  border-radius: 0 0 24px 24px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.page-header h1 {
  margin: 0 0 10px 0;
  font-size: 2.5rem;
}

.page-header p {
  margin: 0;
  font-size: 1.2rem;
  opacity: 0.9;
}

.checkin-actions {
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 40px;
}

.action-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 30px;
}

.action-card-large {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 40px;
  border-radius: 16px;
  text-align: center;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
}

.action-card-large:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  border-color: #2563eb;
}

.action-icon-large {
  font-size: 4rem;
  margin-bottom: 20px;
}

.action-card-large h2 {
  margin: 0 0 10px 0;
  color: #1a1a1a;
  font-size: 1.75rem;
}

.info-text {
  color: #64748b;
  margin-bottom: 25px;
  font-size: 0.95rem;
}

.btn-large {
  padding: 18px 40px;
  font-size: 1.25rem;
  font-weight: 700;
  width: 100%;
}

.info-box-center {
  background: #eff6ff;
  border: 2px solid #3b82f6;
  border-radius: 12px;
  padding: 20px;
  color: #1e40af;
}

.info-box-center ul {
  list-style: none;
  padding-left: 0;
}

.info-box-center li {
  padding: 5px 0;
  font-weight: 500;
}

.dashboard-section {
  background: white;
  padding: 40px;
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
}

.alert-success {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
  border-left: 4px solid #10b981;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  transform: translateY(-2px);
}

.btn-secondary {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  transform: translateY(-2px);
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 15px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  background: #f8fafc;
  color: #1a1a1a;
  font-weight: 600;
}

.data-table tr:hover {
  background: #f8fafc;
}

.type-badge {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
}

.type-checkin {
  background: #d1fae5;
  color: #065f46;
}

.type-checkout {
  background: #fef3c7;
  color: #92400e;
}

.type-icon {
  font-size: 1.5rem;
  margin-right: 10px;
}

.digital-clock {
  font-size: 1.8rem;
  font-weight: 700;
  color: #fbbf24;
  margin-top: 20px;
  padding: 20px 30px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 12px;
  display: inline-block;
  font-family: 'Courier New', monospace;
  letter-spacing: 2px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
  border: 2px solid rgba(251, 191, 36, 0.3);
}

.status-display {
  text-align: center;
  margin: -20px auto 30px auto;
  max-width: 1400px;
}

.status-badge {
  display: inline-block;
  padding: 15px 30px;
  border-radius: 50px;
  font-size: 1.25rem;
  font-weight: 700;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.status-checkedin {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
  border: 3px solid #10b981;
}

.status-checkedout {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  border: 3px solid #ef4444;
}

.status-icon {
  font-size: 1.5rem;
  margin-right: 10px;
}

.action-card-large.disabled {
  opacity: 0.6;
  pointer-events: none;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.btn-disabled {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
  color: white;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-disabled:hover {
  transform: none;
}

@media (max-width: 1024px) {
  .action-cards {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 15px;
  }
  
  .page-header {
    padding: 40px 20px;
    margin: -15px -15px 30px -15px;
  }
  
  .checkin-actions,
  .dashboard-section {
    padding: 25px;
  }
}
</style>
</body>
</html>
