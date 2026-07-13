<?php
session_start();
include('../includes/auth_check.php');
checkRole(['warden']);
include('../includes/db.php');
include('../includes/navbar.php');

$success = $error = "";

if(isset($_POST['update_room'])){
    $room_number = (int)$_POST['room_number'];
    $status = $_POST['status'];
    
    if($status == 'Occupied' && empty($_POST['student_id'])){
        $error = "Please select a student to assign to this room.";
    } else {
        $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE room_number = ?");
        $stmt->bind_param('si', $status, $room_number);
        
        if($stmt->execute()){
            if($status == 'Available' || $status == 'Maintenance'){
                $conn->query("UPDATE users SET room_number = NULL WHERE room_number = $room_number");
                $success = "Room status updated and students released successfully!";
            } elseif($status == 'Occupied' && !empty($_POST['student_id'])){
                $student_id = (int)$_POST['student_id'];
                
                $check_student = $conn->query("SELECT room_number FROM users WHERE id = $student_id AND room_number IS NOT NULL");
                if($check_student && $check_student->num_rows > 0){
                    $error = "This student is already assigned to another room. Please release them first.";
                    $conn->query("UPDATE rooms SET status = 'Available' WHERE room_number = $room_number");
                } else {
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

$all_students = $conn->query("SELECT id, full_name, student_number, email, room_number FROM users WHERE role = 'student' ORDER BY full_name ASC");
$rooms_query = "SELECT r.room_number, r.room_type, r.status,
                GROUP_CONCAT(u.id SEPARATOR ',') as student_ids,
                GROUP_CONCAT(u.full_name SEPARATOR ', ') as student_names,
                GROUP_CONCAT(u.student_number SEPARATOR ', ') as student_numbers
                FROM rooms r 
                LEFT JOIN users u ON r.room_number = u.room_number 
                GROUP BY r.room_number
                ORDER BY r.room_number ASC";
$rooms = $conn->query($rooms_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Rooms - Warden</title>
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
  
  <div class="dashboard-section">
    <h2>All Rooms</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Room #</th>
            <th>Type</th>
            <th>Status</th>
            <th>Assigned Students</th>
            <th>Student Numbers</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($room = $rooms->fetch_assoc()): ?>
          <tr>
            <td><strong><?php echo $room['room_number']; ?></strong></td>
            <td><?php echo $room['room_type']; ?></td>
            <td>
              <span class="status-badge status-<?php echo strtolower($room['status']); ?>">
                <?php echo $room['status']; ?>
              </span>
            </td>
            <td><?php echo $room['student_names'] ? htmlspecialchars($room['student_names']) : '-'; ?></td>
            <td><?php echo $room['student_numbers'] ? htmlspecialchars($room['student_numbers']) : '-'; ?></td>
            <td>
              <button onclick="openRoomModal(<?php echo $room['room_number']; ?>)" class="btn btn-small">Manage</button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
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

<!-- Student Details -->
<div id="studentModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeStudentModal()">&times;</span>
    <h2>Student Details</h2>
    <div id="studentDetailsContent">
      <p>Loading...</p>
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
  grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.room-box {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.room-box:hover {
  transform: scale(1.05);
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

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  margin-right: 5px;
  margin-bottom: 5px;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.8rem;
}

.btn-primary {
  background: #2563eb;
  color: white;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-info {
  background: #0891b2;
  color: white;
}

.btn-info:hover {
  background: #0e7490;
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
  margin: 5% auto;
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

.student-detail-item {
  padding: 10px;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 10px;
}

.student-detail-item:last-child {
  border-bottom: none;
}

.required {
  color: #dc2626;
}

.room-info-card {
  margin-bottom: 20px;
}

.modal-large {
  width: 90%;
  max-width: 800px;
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
    grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
    gap: 8px;
  }
  
  .room-box {
    width: 50px;
    height: 50px;
    font-size: 0.8rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 8px;
    font-size: 0.875rem;
  }
}
</style>

<script>
function openRoomModal(roomNumber) {
    document.getElementById('modal_room_number').value = roomNumber;
    document.getElementById('roomModal').style.display = 'block';
    fetchRoomDetails(roomNumber);
}

function fetchRoomDetails(roomNumber) {
    fetch('get_room_details.php?room_number=' + roomNumber)
        .then(response => response.json())
        .then(data => {
            if(data.success){
                let roomInfoHtml = `
                    <div class="student-detail-item"><strong>Room Number:</strong> ${data.room.room_number}</div>
                    <div class="student-detail-item"><strong>Room Type:</strong> ${data.room.room_type}</div>
                    <div class="student-detail-item"><strong>Status:</strong> ${data.room.status}</div>
                `;
                document.getElementById('roomInfo').innerHTML = roomInfoHtml;
                
                if(data.room.student_names){
                    document.getElementById('studentsSection').style.display = 'block';
                    let studentsListHtml = '';
                    data.room.student_ids.split(',').forEach(studentId => {
                        studentsListHtml += `<div class="student-detail-item" onclick="viewStudentDetails(${studentId})">
                                                  <strong>${data.room.student_names.split(', ')[data.room.student_ids.split(',').indexOf(studentId)]}</strong>
                                                  (${data.room.student_numbers.split(', ')[data.room.student_ids.split(',').indexOf(studentId)]})
                                              </div>`;
                    });
                    document.getElementById('studentsList').innerHTML = studentsListHtml;
                } else {
                    document.getElementById('studentsSection').style.display = 'none';
                }
                
                document.getElementById('modal_room_status').value = data.room.status;
                toggleStudentSelect();
            } else {
                document.getElementById('roomInfo').innerHTML = '<p>Error loading room details.</p>';
                document.getElementById('studentsSection').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('roomInfo').innerHTML = '<p>Error loading room details.</p>';
            document.getElementById('studentsSection').style.display = 'none';
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

function viewStudentDetails(studentId) {
    document.getElementById('studentModal').style.display = 'block';
    document.getElementById('studentDetailsContent').innerHTML = '<p>Loading...</p>';
    
    fetch('get_student_details.php?id=' + studentId)
        .then(response => response.json())
        .then(data => {
            if(data.success){
                let html = `
                    <div class="student-detail-item"><strong>Name:</strong> ${data.student.full_name}</div>
                    <div class="student-detail-item"><strong>Student Number:</strong> ${data.student.student_number}</div>
                    <div class="student-detail-item"><strong>Email:</strong> ${data.student.email}</div>
                    <div class="student-detail-item"><strong>Phone:</strong> ${data.student.phone_number || 'N/A'}</div>
                    <div class="student-detail-item"><strong>Room:</strong> ${data.student.room_number || 'Not Assigned'}</div>
                    <div class="student-detail-item"><strong>Address:</strong> ${data.student.home_address || 'N/A'}</div>
                `;
                document.getElementById('studentDetailsContent').innerHTML = html;
            } else {
                document.getElementById('studentDetailsContent').innerHTML = '<p>Error loading student details.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('studentDetailsContent').innerHTML = '<p>Error loading student details.</p>';
        });
}

function closeStudentModal() {
    document.getElementById('studentModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>
