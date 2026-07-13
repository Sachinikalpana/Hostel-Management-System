<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['student']);
include('../includes/db.php');
include('../includes/navbar.php');

$user_id = $_SESSION['user_id'];

// Mark Read
$announcements_result = $conn->query("SELECT id FROM announcements");
while($announcement = $announcements_result->fetch_assoc()) {
    $ann_id = $announcement['id'];
    $conn->query("INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES ($ann_id, $user_id)");
}

// Get Announcemnts
$announcements = $conn->query("
    SELECT a.*, u.full_name,
    (SELECT read_at FROM announcement_reads WHERE announcement_id = a.id AND user_id = $user_id) as read_at
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
  <title>Announcements - StaySmart Hostel</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>📢 Announcements</h1>
    <p>Stay updated with hostel news and important notices</p>
  </div>
  
  <div class="content-section">
    <div class="announcements-list">
      <?php if($announcements && $announcements->num_rows > 0): ?>
        <?php while($row = $announcements->fetch_assoc()): ?>
          <div class="announcement-card <?php echo $row['read_at'] ? 'read' : 'unread'; ?>">
            <div class="announcement-header">
              <div>
                <strong class="announcement-title"><?php echo htmlspecialchars($row['title']); ?></strong>
                <?php if(!$row['read_at']): ?>
                  <span class="new-badge">New</span>
                <?php endif; ?>
              </div>
              <span class="announcement-date">
                <?php echo date('M d, Y - h:i A', strtotime($row['created_at'])); ?>
              </span>
            </div>
            <div class="announcement-content">
              <?php echo nl2br(htmlspecialchars($row['message'])); ?>
            </div>
            <div class="announcement-footer">
              <small>Posted by: <?php echo htmlspecialchars($row['full_name'] ?? 'Admin'); ?></small>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-data">
          <p>📭 No announcements available at the moment.</p>
        </div>
      <?php endif; ?>
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

.content-section {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.announcements-list {
  max-height: none;
}

.announcement-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 25px;
  margin-bottom: 20px;
  border-radius: 12px;
  border-left: 5px solid #2563eb;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.announcement-card.unread {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  border-left-color: #3b82f6;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
}

.announcement-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.announcement-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
  gap: 15px;
}

.announcement-title {
  font-size: 1.25rem;
  color: #1a1a1a;
}

.new-badge {
  display: inline-block;
  background: #ef4444;
  color: white;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 10px;
  animation: pulse 2s infinite;
}

.announcement-date {
  color: #64748b;
  font-size: 0.875rem;
  white-space: nowrap;
}

.announcement-content {
  color: #475569;
  line-height: 1.7;
  margin-bottom: 15px;
  font-size: 1rem;
}

.announcement-footer small {
  color: #94a3b8;
  font-style: italic;
}

.no-data {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
  font-size: 1.1rem;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
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
  
  .announcement-card {
    padding: 20px;
  }
  
  .announcement-header {
    flex-direction: column;
    gap: 8px;
  }
  
  .announcement-title {
    font-size: 1.1rem;
  }
}
</style>
</body>
</html>
