<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['warden', 'admin']);
include('../includes/db.php');
include('../includes/navbar.php');

$error = $success = "";

// Add Announcemnt
if(isset($_POST['add_announcement'])){
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    
    if(empty($title) || empty($message)){
        $error = "Title and message are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO announcements (user_id, title, message) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $_SESSION['user_id'], $title, $message);
        
        if($stmt->execute()){
            $success = "Announcement posted successfully.";
            $_POST = array();
        } else {
            $error = "Failed to post announcement.";
        }
        $stmt->close();
    }
}

// Edit Announcement
if(isset($_POST['edit_announcement'])){
    $id = intval($_POST['announcement_id']);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    
    $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssi', $title, $message, $id);
    
    if($stmt->execute()){
        $success = "Announcement updated successfully.";
    } else {
        $error = "Failed to update announcement.";
    }
    $stmt->close();
}

// Delete Announcement
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if($stmt->execute()){
        $success = "Announcement deleted successfully.";
    } else {
        $error = "Failed to delete announcement.";
    }
    $stmt->close();
}

// Get Announcements
$announcements = $conn->query("
    SELECT a.*, u.full_name 
    FROM announcements a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Announcements - Warden Portal</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>Manage Announcements</h1>
    <p>Post, edit, and delete announcements</p>
  </div>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <h2>Post New Announcement</h2>
    <form method="post" class="form">
      <div class="form-group">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" required 
               placeholder="Enter announcement title"
               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
      </div>
      
      <div class="form-group">
        <label for="message">Message *</label>
        <textarea id="message" name="message" required rows="4" 
                  placeholder="Enter announcement message"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
      </div>
      
      <button type="submit" name="add_announcement" class="btn btn-primary">Post Announcement</button>
    </form>
  </div>
  
  <div class="dashboard-section">
    <h2>All Announcements</h2>
    <div class="announcements-list">
      <?php if($announcements && $announcements->num_rows > 0): ?>
        <?php while($announcement = $announcements->fetch_assoc()): ?>
          <div class="announcement-item">
            <div class="announcement-header-row">
              <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
              <div class="announcement-actions">
                <button onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars($announcement['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($announcement['message'], ENT_QUOTES); ?>')" class="btn-small btn-edit">Edit</button>
                <a href="?delete=<?php echo $announcement['id']; ?>" onclick="return confirm('Are you sure you want to delete this announcement?');" class="btn-small btn-delete">Delete</a>
              </div>
            </div>
            <p class="announcement-message"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
            <div class="announcement-meta">
              <span>Posted by: <?php echo htmlspecialchars($announcement['full_name']); ?></span>
              <span>Date: <?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?></span>
              <?php if($announcement['updated_at']): ?>
                <span>Updated: <?php echo date('M d, Y H:i', strtotime($announcement['updated_at'])); ?></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align: center; color: #64748b;">No announcements found</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Announcement</h2>
    <form method="post">
      <input type="hidden" id="edit_announcement_id" name="announcement_id">
      <div class="form-group">
        <label for="edit_title">Title *</label>
        <input type="text" id="edit_title" name="title" required>
      </div>
      <div class="form-group">
        <label for="edit_message">Message *</label>
        <textarea id="edit_message" name="message" required rows="4"></textarea>
      </div>
      <button type="submit" name="edit_announcement" class="btn btn-primary">Update Announcement</button>
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
  margin-bottom: 35px;
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

.form-group {
  margin-bottom: 25px;
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
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 120px;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
  transform: translateY(-2px);
}

.btn-small {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  margin-left: 8px;
}

.btn-edit {
  background: #3b82f6;
  color: white;
}

.btn-edit:hover {
  background: #2563eb;
}

.btn-delete {
  background: #ef4444;
  color: white;
}

.btn-delete:hover {
  background: #dc2626;
}

.announcements-list {
  margin-top: 25px;
}

.announcement-item {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 25px;
  margin-bottom: 20px;
  border-radius: 12px;
  border-left: 5px solid #2563eb;
}

.announcement-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  flex-wrap: wrap;
  gap: 15px;
}

.announcement-header-row h3 {
  margin: 0;
  color: #1a1a1a;
  font-size: 1.4rem;
}

.announcement-actions {
  display: flex;
  gap: 8px;
}

.announcement-message {
  color: #475569;
  line-height: 1.7;
  margin-bottom: 15px;
}

.announcement-meta {
  border-top: 1px solid #e2e8f0;
  padding-top: 12px;
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  font-size: 0.875rem;
  color: #64748b;
}

.modal {
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
  padding: 40px;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.close {
  color: #64748b;
  float: right;
  font-size: 2rem;
  font-weight: bold;
  cursor: pointer;
  line-height: 1;
}

.close:hover {
  color: #1a1a1a;
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
  
  .announcement-header-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

<script>
function editAnnouncement(id, title, message) {
    document.getElementById('edit_announcement_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_message').value = message;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
</body>
</html>
