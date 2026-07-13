<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['student']);
include('../includes/db.php');
include('../includes/navbar.php');

$user_id = $_SESSION['user_id'];
$current_room = $_SESSION['room_number'] ?? null;

$message = '';
$message_type = '';

// Room Request
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $desired_room = intval($_POST['desired_room']);
    $reason = trim($_POST['reason']);
    
    if($desired_room && $reason) {
        // Check Room Available
        $room_check = $conn->query("SELECT status FROM rooms WHERE room_number = $desired_room");
        if($room_check && $room_check->num_rows > 0) {
            $room = $room_check->fetch_assoc();
            if($room['status'] == 'Available') {
                $reason = $conn->real_escape_string($reason);
                $insert = $conn->query("
                    INSERT INTO room_change_requests (user_id, current_room, desired_room, reason) 
                    VALUES ($user_id, $current_room, $desired_room, '$reason')
                ");
                if($insert) {
                    $message = 'Room change request submitted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error submitting request. Please try again.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Selected room is not available!';
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid room number!';
            $message_type = 'error';
        }
    }
}

// Get Student Room Requests
$requests = $conn->query("
    SELECT rcr.*, r.status as room_status 
    FROM room_change_requests rcr
    LEFT JOIN rooms r ON rcr.desired_room = r.room_number
    WHERE rcr.user_id = $user_id 
    ORDER BY rcr.created_at DESC
");

// Get Available Rooms
$available_rooms = $conn->query("SELECT room_number FROM rooms WHERE status = 'Available' ORDER BY room_number ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Room Change Requests - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>🔄 Room Change Requests</h1>
    <p>Request to change your assigned room</p>
  </div>
  
  <?php if($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>
  
  <div class="content-section">
    <h2>Submit New Request</h2>
    <?php if($current_room): ?>
      <form method="POST" class="form-container">
        <div class="form-group">
          <label>Current Room</label>
          <input type="text" value="<?php echo $current_room; ?>" disabled class="form-control">
        </div>
        
        <div class="form-group">
          <label for="desired_room">Desired Room *</label>
          <select name="desired_room" id="desired_room" required class="form-control">
            <option value="">Select a room</option>
            <?php while($room = $available_rooms->fetch_assoc()): ?>
              <option value="<?php echo $room['room_number']; ?>">Room <?php echo $room['room_number']; ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="reason">Reason for Request *</label>
          <textarea name="reason" id="reason" rows="4" required class="form-control" placeholder="Explain why you want to change your room..."></textarea>
        </div>
        
        <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
      </form>
    <?php else: ?>
      <div class="alert alert-error">
        You are not assigned to any room yet. Please contact the admin.
      </div>
    <?php endif; ?>
  </div>
  
  <div class="content-section">
    <h2>My Requests</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Request Date</th>
            <th>Current Room</th>
            <th>Desired Room</th>
            <th>Reason</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if($requests && $requests->num_rows > 0): ?>
            <?php while($req = $requests->fetch_assoc()): ?>
              <tr>
                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                <td><?php echo $req['current_room']; ?></td>
                <td><?php echo $req['desired_room']; ?></td>
                <td><?php echo htmlspecialchars($req['reason']); ?></td>
                <td>
                  <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $req['status'])); ?>">
                    <?php echo $req['status']; ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center;">No room change requests found</td>
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
  max-width: 1000px;
  margin: 0 auto;
  padding: 30px;
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
  margin-bottom: 25px;
  border-radius: 8px;
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

.content-section {
  background: white;
  padding: 30px;
  margin-bottom: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.content-section h2 {
  margin-top: 0;
  color: #1a1a1a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 12px;
  margin-bottom: 20px;
}

.form-container {
  max-width: 600px;
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
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control:disabled {
  background: #f1f5f9;
  cursor: not-allowed;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f8fafc;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #1a1a1a;
  border-bottom: 2px solid #e2e8f0;
}

.data-table td {
  padding: 15px;
  border-bottom: 1px solid #e2e8f0;
}

.status-badge {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-check {
  background: #dbeafe;
  color: #1e40af;
}

.status-under-review {
  background: #e0e7ff;
  color: #3730a3;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 768px) {
  .container {
    padding: 15px;
  }
  
  .page-header {
    padding: 30px 15px;
    margin: -15px -15px 25px -15px;
  }
  
  .page-header h1 {
    font-size: 1.5rem;
  }
  
  .content-section {
    padding: 20px;
  }
  
  .data-table {
    font-size: 0.875rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 10px;
  }
}
</style>
</body>
</html>
