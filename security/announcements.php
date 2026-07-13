<?php
session_start();
include('../includes/auth_check.php');
include('../includes/db.php');

if($_SESSION['role'] != 'security'){
    header('Location: ../dashboard.php');
    exit();
}

$announcements = $conn->query("SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Announcements - Security</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include('../includes/navbar.php'); ?>

<div class="container" style="padding: 30px;">
  <h1>Announcements</h1>
  
  <div class="announcements-grid" style="display: grid; gap: 20px; margin-top: 20px;">
    <?php if($announcements->num_rows > 0): ?>
      <?php while($ann = $announcements->fetch_assoc()): ?>
      <div class="card">
        <div class="card-header">
          <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
        </div>
        <div class="card-body">
          <p><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
          <small class="text-muted">Posted by <?php echo htmlspecialchars($ann['full_name']); ?> on <?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="card">
        <div class="card-body">
          <p>No announcements available.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
