<?php
session_start();
include('../includes/auth_check.php');
include('../includes/db.php');

if($_SESSION['role'] != 'admin'){
    header('Location: ../dashboard.php');
    exit();
}

$success = $error = "";

// New Annoucement
if(isset($_POST['create'])){
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    
    $stmt = $conn->prepare("INSERT INTO announcements (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $_SESSION['user_id'], $title, $message);
    if($stmt->execute()){
        $success = "Announcement created successfully!";
    }
    $stmt->close();
}

// Announcement Edit
if(isset($_POST['update'])){
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    
    $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ? WHERE id = ?");
    $stmt->bind_param('ssi', $title, $message, $id);
    if($stmt->execute()){
        $success = "Announcement updated successfully!";
    }
    $stmt->close();
}

// Delete announcement
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM announcements WHERE id = $id");
    $success = "Announcement deleted successfully!";
}

$announcements = $conn->query("SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  <!-- make the page match the device screen size.-->
  <title>Announcements - Admin</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include('../includes/navbar.php'); ?>

<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>Manage Announcements</h1>
    <button class="btn btn-primary" onclick="showCreateModal()">Create Announcement</button>
  </div>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>
  
  <div class="announcements-grid" style="display: grid; gap: 20px; margin-top: 20px;">
    <?php while($ann = $announcements->fetch_assoc()): ?>
    <div class="card">
      <div class="card-header" style="display: flex; justify-content: space-between;">
        <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
        <div style="display: flex; gap: 10px;">
          <button class="btn btn-sm btn-primary" onclick='editAnnouncement(<?php echo json_encode($ann); ?>)'>Edit</button>
          <a href="?delete=<?php echo $ann['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
        </div>
      </div>
      <div class="card-body">
        <p><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
        <small class="text-muted">Posted by <?php echo htmlspecialchars($ann['full_name']); ?> on <?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Create Box -->
<div id="createModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="hideCreateModal()">&times;</span>
    <h2>Create Announcement</h2>
    <form method="post">
      <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" required>
      </div>
      <div class="form-group">
        <label>Message *</label>
        <textarea name="message" rows="5" required></textarea>
      </div>
      <button type="submit" name="create" class="btn btn-primary">Create</button>
    </form>
  </div>
</div>

<!-- Edit Box -->
<div id="editModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="hideEditModal()">&times;</span>
    <h2>Edit Announcement</h2>
    <form method="post">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" id="edit_title" required>
      </div>
      <div class="form-group">
        <label>Message *</label>
        <textarea name="message" id="edit_message" rows="5" required></textarea>
      </div>
      <button type="submit" name="update" class="btn btn-primary">Update</button>
    </form>
  </div>
</div>

<style>
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
  max-width: 600px;
  width: 90%;
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

function editAnnouncement(ann) {
  document.getElementById('edit_id').value = ann.id;
  document.getElementById('edit_title').value = ann.title;
  document.getElementById('edit_message').value = ann.message;
  document.getElementById('editModal').style.display = 'flex';
}

function hideEditModal() {
  document.getElementById('editModal').style.display = 'none';
}
</script>
</body>
</html>
