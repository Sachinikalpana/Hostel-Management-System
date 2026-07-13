<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['warden', 'admin']);
include('../includes/db.php');
include('../includes/navbar.php');

$error = $success = "";

// Status Update
if(isset($_POST['update_status'])){
    $complaint_id = intval($_POST['complaint_id']);
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE complaints SET status = ?, processed_by = ?, resolved_at = NOW() WHERE id = ?");
    $stmt->bind_param('sii', $new_status, $_SESSION['user_id'], $complaint_id);
    
    if($stmt->execute()){
        $success = "Complaint status updated successfully.";
    } else {
        $error = "Failed to update complaint status.";
    }
    $stmt->close();
}

// Get Complaints
$complaints = $conn->query("
    SELECT c.*, u.full_name, u.email 
    FROM complaints c 
    LEFT JOIN users u ON c.user_id = u.id 
    ORDER BY 
        CASE c.status 
            WHEN 'Pending' THEN 1 
            WHEN 'Notified' THEN 2 
            WHEN 'Action Taken' THEN 3 
            WHEN 'Resolved' THEN 4 
        END,
        c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Complaints - Warden Portal</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>Manage Complaints</h1>
    <p>Review and update complaint statuses</p>
  </div>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Room</th>
            <th>Designation</th>
            <th>Complaint</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if($complaints && $complaints->num_rows > 0): ?>
            <?php while($complaint = $complaints->fetch_assoc()): ?>
              <tr>
                <td><?php echo $complaint['id']; ?></td>
                <td>
                  <strong><?php echo htmlspecialchars($complaint['full_name']); ?></strong><br>
                  <small><?php echo htmlspecialchars($complaint['email']); ?></small>
                </td>
                <td>Room <?php echo $complaint['room_number']; ?></td>
                <td><?php echo $complaint['designation']; ?></td>
                <td style="max-width: 300px;"><?php echo htmlspecialchars($complaint['complaint']); ?></td>
                <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $complaint['status'])); ?>"><?php echo $complaint['status']; ?></span></td>
                <td><?php echo date('M d, Y H:i', strtotime($complaint['created_at'])); ?></td>
                <td>
                  <form method="post" style="display: inline-block;">
                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                    <select name="status" class="status-select" onchange="this.form.submit()">
                      <option value="">Change Status...</option>
                      <option value="Pending" <?php echo ($complaint['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                      <option value="Notified" <?php echo ($complaint['status'] == 'Notified') ? 'selected' : ''; ?>>Notified</option>
                      <option value="Action Taken" <?php echo ($complaint['status'] == 'Action Taken') ? 'selected' : ''; ?>>Action Taken</option>
                      <option value="Resolved" <?php echo ($complaint['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                    <button type="submit" name="update_status" class="btn-small btn-primary" style="display: none;">Update</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align: center;">No complaints found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
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

.dashboard-section {
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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

.status-badge {
  padding: 5px 12px;
  border-radius: 15px;
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
  color: #4338ca;
}

.status-resolved {
  background: #d1fae5;
  color: #065f46;
}

.status-select {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.9rem;
  cursor: pointer;
}

.btn-small {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  cursor: pointer;
  font-weight: 600;
}

.btn-primary {
  background: #2563eb;
  color: white;
}

.btn-primary:hover {
  background: #1d4ed8;
}

@media (max-width: 768px) {
  .container {
    padding: 15px;
  }
  
  .page-header {
    padding: 40px 20px;
    margin: -15px -15px 30px -15px;
  }
  
  .dashboard-section {
    padding: 25px;
  }
}
</style>
</body>
</html>
