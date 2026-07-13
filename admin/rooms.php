<?php
session_start();
include('../includes/auth_check.php');
checkRole(['admin']);
include('../includes/db.php');
include('../includes/navbar.php');

$success = $error = "";

if(isset($_POST['update_room'])){
    $room_number = (int)$_POST['room_number'];
    $status = $_POST['status'];
    
    if($status == 'Occupied' && empty($_POST['student_id'])){
        $error = "Please select a student to assign to this room.";
    } else {
        // Updte Room Status
        $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE room_number = ?");
        $stmt->bind_param('si', $status, $room_number);
        
        if($stmt->execute()){
            // If change to Avlbl or Maintnance, Release Students
            if($status == 'Available' || $status == 'Maintenance'){
                $conn->query("UPDATE users SET room_number = NULL WHERE room_number = $room_number");
                $success = "Room status updated and students released successfully!";
            } elseif($status == 'Occupied' && !empty($_POST['student_id'])){
                $student_id = (int)$_POST['student_id'];
                
                // Check Student is Already Assigned Anothr Room
                $check_student = $conn->query("SELECT room_number FROM users WHERE id = $student_id AND room_number IS NOT NULL");
                if($check_student && $check_student->num_rows > 0){
                    $error = "This student is already assigned to another room. Please release them first.";
                    // Revert Stats Change
                    $conn->query("UPDATE rooms SET status = 'Available' WHERE room_number = $room_number");
                } else {
                    // Assign Student To Room
                    $stmt2 = $conn->prepare("UPDATE users SET room_number = ? WHERE id = ?");
                    $stmt2->bind_param('ii', $room_number, $student_id);
                    $stmt2->execute();
                    $stmt2->close();
                    $success = "Room status updated and student assigned successfully!";
                }
            } else {
                $success = "Room status updated successfully!";
            }
        } else {
            $error = "Failed to update room status: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get All Students
$all_students = $conn->query("SELECT id, full_name, student_number, email, room_number FROM users WHERE role = 'student' ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Rooms - Admin</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>Manage Rooms</h1>
    <p>Click on any room to view details and manage status</p>
  </div>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <!-- Room Map - Clickabl -->
  <div class="dashboard-section">
    <h2>Interactive Room Map</h2>
    <p style="color: #64748b; margin-bottom: 20px;">Click on any room to change status or view student details</p>
    <div class="room-map">
      <?php 
      $rooms_map = $conn->query("SELECT r.room_number, r.room_type, r.status FROM rooms r ORDER BY r.room_number ASC");
      while($rm = $rooms_map->fetch_assoc()): 
        $color_class = '';
        if($rm['status'] == 'Available') $color_class = 'room-available';
        elseif($rm['status'] == 'Occupied') $color_class = 'room-occupied';
        elseif($rm['status'] == 'Maintenance') $color_class = 'room-maintenance';
        if($rm['room_type'] == 'Sick Room') $color_class = 'room-sick';
      ?>
        <div class="room-box <?php echo $color_class; ?>" 
             onclick="openRoomModal(<?php echo $rm['room_number']; ?>)"
             title="Click to manage Room <?php echo $rm['room_number']; ?>">
          <?php echo $rm['room_number']; ?>
        </div>
      <?php endwhile; ?>
    </div>
    <div class="room-legend">
      <div><span class="legend-box room-available"></span> Available</div>
      <div><span class="legend-box room-occupied"></span> Occupied</div>
      <div><span class="legend-box room-maintenance"></span> Maintenance</div>
      <div><span class="legend-box room-sick"></span> Sick Room</div>
    </div>
  </div>
</div>

<!-- Combined Room Management -->
<div id="roomModal" class="modal">
  <div class="modal-content modal-large">
    <span class="close" onclick="closeRoomModal()">&times;</span>
    <h2 id="roomModalTitle">Room Details</h2>
    
    <div id="roomDetailsSection">
      <div class="room-info-card">
        <h3>Room Information</h3>
        <div id="roomInfo"></div>
      </div>
      
      <div class="room-info-card" id="studentsSection" style="display: none;">
        <h3>Assigned Students</h3>
        <div id="studentsList"></div>
      </div>
    </div>
    
    <div class="room-info-card">
      <h3>Change Room Status</h3>
      <form method="post">
        <input type="hidden" id="modal_room_number" name="room_number">
        <div class="form-group">
          <label>Room Status</label>
          <select name="status" id="modal_room_status" onchange="toggleStudentSelect()" required>
            <option value="Available">Available</option>
            <option value="Occupied">Occupied</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </div>
        <div class="form-group" id="modal_student_select_group" style="display: none;">
          <label>Assign Student <span class="required">*</span></label>
          <select name="student_id" id="modal_student_id_select">
            <option value="">-- Select Student --</option>
            <?php 
            $all_students->data_seek(0);
            while($student = $all_students->fetch_assoc()): 
            ?>
            <option value="<?php echo $student['id']; ?>" 
                    <?php echo ($student['room_number'] ? 'disabled' : ''); ?>>
              <?php echo htmlspecialchars($student['full_name']) . ' (' . htmlspecialchars($student['student_number']) . ')'; ?>
              <?php echo ($student['room_number'] ? ' - Already in Room ' . $student['room_number'] : ''); ?>
            </option>
            <?php endwhile; ?>
          </select>
          <small style="color: #666;">Students already assigned to rooms are disabled</small>
        </div>
        <button type="submit" name="update_room" class="btn btn-primary">Update Room Status</button>
      </form>
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

.room-map {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.room-box {
  width: 70px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.room-box:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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

.room-legend {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.room-legend div {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
}

.legend-box {
  width: 25px;
  height: 25px;
  border-radius: 4px;
  border: 2px solid;
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
  overflow-y: auto;
}

.modal-content {
  background-color: white;
  margin: 3% auto;
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-large {
  max-width: 800px;
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  line-height: 20px;
}

.close:hover {
  color: #000;
}

.room-info-card {
  background: #f8fafc;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.room-info-card h3 {
  margin-top: 0;
  color: #1e293b;
  font-size: 1.1rem;
  border-bottom: 2px solid #2563eb;
  padding-bottom: 8px;
  margin-bottom: 15px;
}

.info-item {
  padding: 10px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #475569;
}

.info-value {
  color: #1e293b;
}

.student-card {
  background: white;
  padding: 15px;
  border-radius: 6px;
  margin-bottom: 10px;
  border-left: 4px solid #2563eb;
}

.student-card h4 {
  margin: 0 0 10px 0;
  color: #1e293b;
}

.student-detail {
  font-size: 0.9rem;
  color: #64748b;
  margin: 5px 0;
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
.form-group select {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #2563eb;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #2563eb;
  color: white;
  width: 100%;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.required {
  color: #dc2626;
}

.status-badge {
  padding: 6px 14px;
  border-radius: 14px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-available {
  background: #d1fae5;
  color: #065f46;
}

.status-occupied {
  background: #fee2e2;
  color: #991b1b;
}

.status-maintenance {
  background: #fef3c7;
  color: #92400e;
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
  
  .room-map {
    grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
    gap: 8px;
  }
  
  .room-box {
    width: 55px;
    height: 55px;
    font-size: 0.85rem;
  }
  
  .modal-content {
    width: 95%;
    padding: 20px;
    margin: 5% auto;
  }
}
</style>

<script>
function openRoomModal(roomNumber) {
    // Get Room Detail
    fetch(`get_room_details.php?room_number=${roomNumber}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const room = data.room;
                
                // Modal Titl
                document.getElementById('roomModalTitle').textContent = `Room ${room.room_number} - ${room.room_type}`;
                
                // Room Info
                document.getElementById('roomInfo').innerHTML = `
                    <div class="info-item">
                        <span class="info-label">Room Number:</span>
                        <span class="info-value"><strong>${room.room_number}</strong></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Room Type:</span>
                        <span class="info-value">${room.room_type}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Current Status:</span>
                        <span class="info-value"><span class="status-badge status-${room.status.toLowerCase()}">${room.status}</span></span>
                    </div>
                `;
                
                // Set StudentList
                if(data.students && data.students.length > 0) {
                    document.getElementById('studentsSection').style.display = 'block';
                    let studentsHTML = '';
                    data.students.forEach(student => {
                        studentsHTML += `
                            <div class="student-card">
                                <h4>${student.full_name}</h4>
                                <div class="student-detail"><strong>Student Number:</strong> ${student.student_number}</div>
                                <div class="student-detail"><strong>Email:</strong> ${student.email}</div>
                                <div class="student-detail"><strong>Phone:</strong> ${student.phone_number || 'N/A'}</div>
                                <div class="student-detail"><strong>Address:</strong> ${student.home_address || 'N/A'}</div>
                            </div>
                        `;
                    });
                    document.getElementById('studentsList').innerHTML = studentsHTML;
                } else {
                    document.getElementById('studentsSection').style.display = 'none';
                }
                
                // Set Values
                document.getElementById('modal_room_number').value = room.room_number;
                document.getElementById('modal_room_status').value = room.status;
                toggleStudentSelect();
                
                // Show
                document.getElementById('roomModal').style.display = 'block';
            } else {
                alert('Error loading room details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading room details');
        });
}

function toggleStudentSelect() {
    const status = document.getElementById('modal_room_status').value;
    const studentGroup = document.getElementById('modal_student_select_group');
    const studentSelect = document.getElementById('modal_student_id_select');
    
    if(status === 'Occupied') {
        studentGroup.style.display = 'block';
        studentSelect.required = true;
    } else {
        studentGroup.style.display = 'none';
        studentSelect.required = false;
        studentSelect.value = '';
    }
}

function closeRoomModal() {
    document.getElementById('roomModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>
