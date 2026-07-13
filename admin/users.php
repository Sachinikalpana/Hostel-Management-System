<?php
session_start();
include('../includes/auth_check.php');
include('../includes/db.php');

// Only Admin
if($_SESSION['role'] != 'admin'){
    header('Location: ../dashboard.php');
    exit();
}

$success = $error = "";

// User Creat
if(isset($_POST['create_user'])){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $phone_digits = trim($_POST['phone_number']);
    $phone_number = '+94' . $phone_digits;
    
    if(!preg_match('/^\d{9}$/', $phone_digits)){
        $error = "Phone number must be exactly 9 digits.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name, phone_number, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssi', $username, $email, $hashed_password, $role, $full_name, $phone_number, $_SESSION['user_id']);
        
        if($stmt->execute()){
            $success = "User created successfully!";
        } else {
            $error = "Failed to create user.";
        }
        $stmt->close();
    }
}

// User Delete
if(isset($_GET['delete'])){
    $user_id = (int)$_GET['delete'];
    if($user_id != $_SESSION['user_id']){
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        if($stmt->execute()){
            $success = "User deleted successfully!";
        }
        $stmt->close();
    }
}

// Get All Users
$users = $conn->query("SELECT u.*, creator.full_name as created_by_name FROM users u LEFT JOIN users creator ON u.created_by = creator.id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include('../includes/navbar.php'); ?>

<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>User Management</h1>
    <button class="btn btn-primary" onclick="showCreateModal()">Create New User</button>
  </div>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
  <?php endif; ?>
  
  <div class="card" style="margin-top: 20px;">
    <div class="card-header">
      <h3>All Users</h3>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Phone</th>
            <th>Room</th>
            <th>Created By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($user = $users->fetch_assoc()): ?>
          <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
            <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
            <td><?php echo $user['room_number'] ?? 'N/A'; ?></td>
            <td><?php echo htmlspecialchars($user['created_by_name'] ?? 'System'); ?></td>
            <td>
              <?php if($user['id'] != $_SESSION['user_id']): ?>
                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
              <?php else: ?>
                <span class="text-muted">Current User</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create User -->
<div id="createModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="hideCreateModal()">&times;</span>
    <h2>Create New User</h2>
    <form method="post">
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" required>
      </div>
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" required>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Phone Number *</label>
        <div style="display: flex; gap: 8px;">
          <span style="padding: 12px 16px; background: #e2e8f0; border-radius: 8px;">+94</span>
          <input type="text" name="phone_number" pattern="\d{9}" maxlength="9" required style="flex: 1;">
        </div>
      </div>
      <div class="form-group">
        <label>Role *</label>
        <select name="role" required>
          <option value="admin">Admin</option>
          <option value="warden">Warden</option>
          <option value="security">Security</option>
          <option value="student">Student</option>
        </select>
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" required minlength="8">
      </div>
      <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
    </form>
  </div>
</div>

<style>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge-admin { background: #dbeafe; color: #1e40af; }
.badge-warden { background: #fef3c7; color: #92400e; }
.badge-security { background: #d1fae5; color: #065f46; }
.badge-student { background: #e9d5ff; color: #6b21a8; }

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.close {
  float: right;
  font-size: 28px;
  cursor: pointer;
}
</style>

<script>
function showCreateModal() {
  document.getElementById('createModal').style.display = 'flex';
}

function hideCreateModal() {
  document.getElementById('createModal').style.display = 'none';
}
</script>
</body>
</html>
