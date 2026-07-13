<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['student']);
include('../includes/db.php');
include('../includes/navbar.php');

$user_id = $_SESSION['user_id'];

// Complaint Submit
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_complaint'])){
    $room_number = $_SESSION['room_number'];
    $complaint = $_POST['complaint'];
    
    $stmt = $conn->prepare("INSERT INTO complaints (user_id, room_number, complaint, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iis", $user_id, $room_number, $complaint);
    
    if($stmt->execute()){
        $success = "Complaint submitted successfully!";
    } else {
        $error = "Failed to submit complaint. Please try again.";
    }
}

// Get Student Complaints
$complaints_result = $conn->query("SELECT * FROM complaints WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Complaints - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>My Complaints</h1>
    <p>Submit and track your complaints</p>
  </div>
  
  <?php if(isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>
  <?php if(isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <h2>Submit New Complaint</h2>
    <form method="post">
      <div class="form-group">
        <label>Your Room Number</label>
        <input type="text" value="<?php echo $_SESSION['room_number']; ?>" readonly class="form-control">
      </div>
      <div class="form-group">
        <label>Complaint Details</label>
        <textarea name="complaint" rows="5" required class="form-control" placeholder="Describe your issue..."></textarea>
      </div>
      <button type="submit" name="submit_complaint" class="btn btn-primary">Submit Complaint</button>
    </form>
  </div>
  
  <div class="dashboard-section">
    <h2>My Complaints History</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Room</th>
            <th>Complaint</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if($complaints_result && $complaints_result->num_rows > 0): ?>
            <?php while($complaint = $complaints_result->fetch_assoc()): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
              <td>Room <?php echo $complaint['room_number']; ?></td>
              <td><?php echo htmlspecialchars($complaint['complaint']); ?></td>
              <td><span class="status-badge status-<?php echo strtolower($complaint['status']); ?>"><?php echo $complaint['status']; ?></span></td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="4" style="text-align: center;">No complaints found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
body {
  background: #f8fafc;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  text-align: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  margin: -30px -30px 40px -30px;
  border-radius: 0 0 20px 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.page-header h1 {
  margin: 0 0 8px 0;
  font-size: 2rem;
}

.page-header p {
  margin: 0;
  font-size: 1rem;
  opacity: 0.9;
}

.alert {
  padding: 15px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-success {
  background: #d1fae5;
  color: #065f46;
  border-left: 4px solid #10b981;
}

.alert-error {
  background: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #ef4444;
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
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #1a1a1a;
}

.form-control {
  width: 100%;
  padding: 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
}

.form-control:focus {
  outline: none;
  border-color: #2563eb;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.2s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
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
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #1a1a1a;
}

.data-table tr:hover {
  background: #f8fafc;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-notified {
  background: #dbeafe;
  color: #1e40af;
}

.status-actiontaken {
  background: #e0e7ff;
  color: #3730a3;
}

.status-resolved {
  background: #d1fae5;
  color: #065f46;
}

@media (max-width: 768px) {
  .container {
    padding: 15px !important;
  }
  
  .page-header {
    padding: 30px 15px;
    margin: -15px -15px 25px -15px;
  }
  
  .dashboard-section {
    padding: 20px;
  }
}
</style>
</body>
</html>
