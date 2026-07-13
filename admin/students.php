<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['admin']);
include('../includes/db.php');
include('../includes/navbar.php');

$error = $success = "";

if(isset($_POST['update_student'])){
    $student_id = intval($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $home_address = trim($_POST['home_address']);
    $phone_digits = trim($_POST['phone_number']);
    $phone_number = '+94' . $phone_digits;
    $room_number = !empty($_POST['room_number']) ? intval($_POST['room_number']) : NULL;
    
    if(!preg_match('/^\d{9}$/', $phone_digits)){
        $error = "Phone number must be exactly 9 digits.";
    } else {
        $current_room_query = $conn->query("SELECT room_number FROM users WHERE id = $student_id AND role = 'student'");
        $current_room_data = $current_room_query->fetch_assoc();
        $old_room = $current_room_data['room_number'];
        
        $conn->begin_transaction();
        
        try {
            // Updt user info
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, home_address = ?, phone_number = ?, room_number = ? WHERE id = ? AND role = 'student'");
            $stmt->bind_param('ssssii', $full_name, $email, $home_address, $phone_number, $room_number, $student_id);
            $stmt->execute();
            $stmt->close();
            
            if($old_room !== null && $old_room != $room_number) {
                $conn->query("UPDATE rooms SET status = 'Available', assigned_to = NULL WHERE room_number = $old_room");
            }
            
            if($room_number !== null) {
                $conn->query("UPDATE rooms SET status = 'Occupied', assigned_to = $student_id WHERE room_number = $room_number");
            }
            
            $conn->commit();
            $success = "Student profile updated successfully and room status synchronized.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Get Students
$students = $conn->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students - Admin Portal</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>Manage Students</h1>
    <p>View and update student profiles</p>
  </div>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <h2>All Students</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Room</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if($students && $students->num_rows > 0): ?>
            <?php while($student = $students->fetch_assoc()): ?>
            <tr>
              <td><?php echo $student['id']; ?></td>
              <td><?php echo htmlspecialchars($student['full_name']); ?></td>
              <td><?php echo htmlspecialchars($student['email']); ?></td>
              <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
              <td><?php echo $student['room_number'] ?? 'Not Assigned'; ?></td>
              <td>
                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-sm btn-primary">Edit</button>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center;">No students found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit -->
<div id="editModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Student Profile</h2>
    <form method="post" id="editForm">
      <input type="hidden" name="student_id" id="edit_student_id">
      
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" id="edit_full_name" required>
      </div>
      
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" id="edit_email" required>
      </div>
      
      <div class="form-group">
        <label>Home Address *</label>
        <textarea name="home_address" id="edit_home_address" required rows="3"></textarea>
      </div>
      
      <div class="form-group">
        <label>Phone Number *</label>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="padding: 12px 16px; background: #e2e8f0; border: 2px solid #e2e8f0; border-radius: 8px; font-weight: 600;">+94</span>
          <input type="text" name="phone_number" id="edit_phone_number" required pattern="\d{9}" maxlength="9" style="flex: 1;">
        </div>
        <small>Enter 9 digits only</small>
      </div>
      
      <div class="form-group">
        <label>Room Number</label>
        <input type="number" name="room_number" id="edit_room_number" min="1" max="100">
      </div>
      
      <button type="submit" name="update_student" class="btn btn-primary">Update Profile</button>
    </form>
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

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.875rem;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}

.modal {
  position: fixed;
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  overflow: auto;
}

.modal-content {
  background: white;
  margin: 50px auto;
  padding: 40px;
  border-radius: 16px;
  max-width: 600px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.3);
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

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
}

.form-group small {
  display: block;
  margin-top: 5px;
  color: #64748b;
  font-size: 0.875rem;
}
</style>

<script>
function openEditModal(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_full_name').value = student.full_name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_home_address').value = student.home_address || '';
    
    // Get 9 Digits from 4n No.
    let phone = student.phone_number || '';
    if(phone.startsWith('+94')) {
        phone = phone.substring(3);
    }
    document.getElementById('edit_phone_number').value = phone;
    
    document.getElementById('edit_room_number').value = student.room_number || '';
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
}
</script>
</body>
</html>
