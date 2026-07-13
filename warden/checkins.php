<?php
session_start();
include('../includes/auth_check.php');
checkRole(['warden']);
include('../includes/db.php');
include('../includes/navbar.php');

$filter_room = isset($_GET['room']) ? (int)$_GET['room'] : 0;
$filter_student = isset($_GET['student']) ? (int)$_GET['student'] : 0;

$where_clauses = [];
$params = [];
$types = '';

if($filter_room > 0){
    $where_clauses[] = "cc.room_number = ?";
    $params[] = $filter_room;
    $types .= 'i';
}

if($filter_student > 0){
    $where_clauses[] = "cc.user_id = ?";
    $params[] = $filter_student;
    $types .= 'i';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "SELECT cc.*, u.full_name 
          FROM checkins_checkouts cc 
          LEFT JOIN users u ON cc.user_id = u.id 
          $where_sql
          ORDER BY cc.time DESC";

if(count($params) > 0){
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $checkins = $stmt->get_result();
} else {
    $checkins = $conn->query($query);
}

$rooms_list = $conn->query("SELECT DISTINCT room_number FROM rooms WHERE room_number > 0 ORDER BY room_number ASC");
$students_list = $conn->query("SELECT id, full_name, student_number FROM users WHERE role = 'student' ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Ins/Outs - Warden</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>Check-In/Out Records</h1>
    <p>View all student check-in and check-out records</p>
  </div>
  
  <div class="dashboard-section">
    <h2>Filter Records</h2>
    <form method="get" class="filter-form">
      <div class="filter-row">
        <div class="form-group">
          <label>Filter by Room</label>
          <select name="room" onchange="this.form.submit()">
            <option value="0">All Rooms</option>
            <?php while($room = $rooms_list->fetch_assoc()): ?>
            <option value="<?php echo $room['room_number']; ?>" <?php echo ($filter_room == $room['room_number'] ? 'selected' : ''); ?>>
              Room <?php echo $room['room_number']; ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label>Filter by Student</label>
          <select name="student" onchange="this.form.submit()">
            <option value="0">All Students</option>
            <?php while($student = $students_list->fetch_assoc()): ?>
            <option value="<?php echo $student['id']; ?>" <?php echo ($filter_student == $student['id'] ? 'selected' : ''); ?>>
              <?php echo htmlspecialchars($student['full_name']) . ' (' . htmlspecialchars($student['student_number']) . ')'; ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <?php if($filter_room > 0 || $filter_student > 0): ?>
        <div class="form-group">
          <label>&nbsp;</label>
          <a href="checkins.php" class="btn btn-secondary">Clear Filters</a>
        </div>
        <?php endif; ?>
      </div>
    </form>
  </div>
  
  <div class="dashboard-section">
    <h2>
      <?php 
      if($filter_room > 0) echo "Records for Room $filter_room";
      elseif($filter_student > 0) {
        $s = $conn->query("SELECT full_name FROM users WHERE id = $filter_student")->fetch_assoc();
        echo "Records for " . htmlspecialchars($s['full_name']);
      } else echo "All Records";
      ?>
    </h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Type</th>
            <th>Student Name</th>
            <th>Student Number</th>
            <th>Room</th>
            <th>Destination</th>
          </tr>
        </thead>
        <tbody>
          <?php if($checkins && $checkins->num_rows > 0): ?>
            <?php while($checkin = $checkins->fetch_assoc()): ?>
            <tr>
              <td><?php echo date('M d, Y h:i A', strtotime($checkin['time'])); ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower(str_replace('-', '', $checkin['type'])); ?>">
                  <?php echo $checkin['type']; ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($checkin['full_name']); ?></td>
              <td><?php echo htmlspecialchars($checkin['student_number']); ?></td>
              <td>Room <?php echo $checkin['room_number']; ?></td>
              <td><?php echo $checkin['place'] ? htmlspecialchars($checkin['place']) : 'N/A'; ?></td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" style="text-align: center;">No records found</td></tr>
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

.filter-form {
  margin-bottom: 0;
}

.filter-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.form-group {
  flex: 1;
  min-width: 200px;
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
  background: white;
}

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
  font-size: 0.875rem;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
}

.btn-secondary {
  background: #64748b;
  color: white;
}

.btn-secondary:hover {
  background: #475569;
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

.status-checkin {
  background: #d1fae5;
  color: #065f46;
}

.status-checkout {
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
  
  .filter-row {
    flex-direction: column;
  }
  
  .form-group {
    width: 100%;
  }
}
</style>
</body>
</html>
