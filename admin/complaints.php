<?php
session_start();
include('../includes/auth_check.php');
include('../includes/db.php');

if($_SESSION['role'] != 'admin'){
    header('Location: ../dashboard.php');
    exit();
}

$success = "";

// Updt complaint status
if(isset($_POST['update_status'])){
    $id = (int)$_POST['complaint_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE complaints SET status = ?, processed_by = ? WHERE id = ?");
    $stmt->bind_param('sii', $status, $_SESSION['user_id'], $id);
    if($stmt->execute()){
        $success = "Complaint status updated!";
    }
    $stmt->close();
}

$complaints = $conn->query("SELECT c.*, u.full_name, u.email FROM complaints c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complaints Management - Admin</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include('../includes/navbar.php'); ?>

<div class="container" style="padding: 30px;">
  <!-- Updtd hder to match othr pgs -->
  <div class="page-header">
    <h1>Complaints Management</h1>
    <p>View and resolve student complaints</p>
  </div>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <h2>All Complaints</h2>
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
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($complaint = $complaints->fetch_assoc()): ?>
          <tr>
            <td><?php echo $complaint['id']; ?></td>
            <td>
              <?php echo htmlspecialchars($complaint['full_name']); ?><br>
              <small><?php echo htmlspecialchars($complaint['email']); ?></small>
            </td>
            <td><?php echo $complaint['room_number']; ?></td>
            <td><?php echo $complaint['designation']; ?></td>
            <td><?php echo htmlspecialchars($complaint['complaint']); ?></td>
            <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $complaint['status'])); ?>"><?php echo $complaint['status']; ?></span></td>
            <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
            <td>
              <form method="post" style="display: inline-block;">
                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                <input type="hidden" name="update_status" value="1">
                <select name="status" class="status-select" onchange="if(this.value) this.form.submit()">
                  <option value="">Change Status</option>
                  <option value="Pending" <?php echo $complaint['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="Notified" <?php echo $complaint['status'] == 'Notified' ? 'selected' : ''; ?>>Notified</option>
                  <option value="Action Taken" <?php echo $complaint['status'] == 'Action Taken' ? 'selected' : ''; ?>>Action Taken</option>
                  <option value="Resolved" <?php echo $complaint['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
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

.alert-success {
  background: #d1fae5;
  color: #065f46;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
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

.status-pending { background: #fef3c7; color: #92400e; }
.status-notified { background: #dbeafe; color: #1e40af; }
.status-actiontaken { background: #e0e7ff; color: #3730a3; }
.status-resolved { background: #d1fae5; color: #065f46; }

.status-select {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  background: white;
  transition: all 0.2s ease;
}

.status-select:hover {
  border-color: #2563eb;
}

.status-select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

@media (max-width: 768px) {
  .container {
    padding: 15px !important;
  }
  
  .page-header {
    padding: 30px 15px;
    margin: -15px -15px 25px -15px;
  }
}
</style>
</body>
</html>
