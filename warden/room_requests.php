<?php
session_start();
include('../includes/auth_check.php');
checkRole(['warden']);
include('../includes/db.php');
include('../includes/navbar.php');

$success = $error = "";

// Request Status Update
if(isset($_POST['update_request'])){
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE room_change_requests SET status = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
    $stmt->bind_param('sii', $status, $_SESSION['user_id'], $request_id);
    
    if($stmt->execute()){
        // If Approved, Updte The Room Assignments
        if($status == 'Approved'){
            $req = $conn->query("SELECT user_id, current_room, desired_room FROM room_change_requests WHERE id = $request_id")->fetch_assoc();
            
            // Release Room
            $conn->query("UPDATE rooms SET status = 'Available', assigned_to = NULL WHERE room_number = {$req['current_room']}");
            
            // Occupy New Room
            $conn->query("UPDATE rooms SET status = 'Occupied', assigned_to = {$req['user_id']} WHERE room_number = {$req['desired_room']}");
            
            // Update User Room
            $conn->query("UPDATE users SET room_number = {$req['desired_room']} WHERE id = {$req['user_id']}");
        }
        
        $success = "Request status updated successfully!";
    } else {
        $error = "Failed to update request status.";
    }
    $stmt->close();
}

// Get Room Change Requests
$requests = $conn->query("SELECT rcr.*, u.full_name, u.student_number, u.email 
                          FROM room_change_requests rcr 
                          LEFT JOIN users u ON rcr.user_id = u.id 
                          ORDER BY rcr.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Room Change Requests - Warden</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>Room Change Requests</h1>
    <p>Manage student room change requests</p>
  </div>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <h2>All Requests</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Student Name</th>
            <th>Student Number</th>
            <th>Current Room</th>
            <th>Desired Room</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if($requests && $requests->num_rows > 0): ?>
            <?php while($request = $requests->fetch_assoc()): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
              <td><?php echo htmlspecialchars($request['full_name']); ?></td>
              <td><?php echo htmlspecialchars($request['student_number']); ?></td>
              <td><?php echo $request['current_room']; ?></td>
              <td><?php echo $request['desired_room']; ?></td>
              <td><?php echo htmlspecialchars(substr($request['reason'], 0, 50)) . '...'; ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                  <?php echo $request['status']; ?>
                </span>
              </td>
              <td>
                <?php if($request['status'] == 'Pending' || $request['status'] == 'Check' || $request['status'] == 'Under Review'): ?>
                <button onclick="updateRequest(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>')" class="btn btn-small">Update</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="8" style="text-align: center;">No requests found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Update Request Box -->
<div id="updateModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Update Request Status</h2>
    <form method="post">
      <input type="hidden" id="request_id" name="request_id">
      <div class="form-group">
        <label>Status</label>
        <select name="status" id="request_status" required>
          <option value="Pending">Pending</option>
          <option value="Check">Check</option>
          <option value="Under Review">Under Review</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
      <button type="submit" name="update_request" class="btn btn-primary">Update Status</button>
    </form>
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

.alert {
  padding: 15px 20px;
  margin-bottom: 20px;
  border-radius: 10px;
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
  border-left: 4px solid #dc2626;
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

.status-check, .status-under-review {
  background: #dbeafe;
  color: #1e40af;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background: #2563eb;
  color: white;
}

.btn:hover {
  background: #1d4ed8;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.8rem;
}

.btn-primary {
  background: #2563eb;
  color: white;
}

.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  background-color: white;
  margin: 10% auto;
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
  color: #000;
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

.form-group select {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
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

<script>
function updateRequest(requestId, currentStatus) {
    document.getElementById('request_id').value = requestId;
    document.getElementById('request_status').value = currentStatus;
    document.getElementById('updateModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('updateModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>
