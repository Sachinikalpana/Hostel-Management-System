<?php
session_start();
include('../includes/auth_check.php');
checkRole(['admin']);
include('../includes/db.php');
include('../includes/navbar.php');

// Fetch Payments
$payments = $conn->query("SELECT p.*, u.full_name FROM payments p 
                          LEFT JOIN users u ON p.user_id = u.id 
                          ORDER BY p.paid_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Payments - Admin</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="padding: 30px;">
  <div class="page-header">
    <h1>Payment Records</h1>
    <p>View all student payments</p>
  </div>
  
  <div class="dashboard-section">
    <h2>All Payments</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Student Name</th>
            <th>Student Number</th>
            <th>Payment Type</th>
            <th>Month</th>
            <th>Amount</th>
            <th>Receipt</th>
          </tr>
        </thead>
        <tbody>
          <?php if($payments && $payments->num_rows > 0): ?>
            <?php while($payment = $payments->fetch_assoc()): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($payment['paid_at'])); ?></td>
              <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
              <td><?php echo htmlspecialchars($payment['student_number']); ?></td>
              <td><?php echo htmlspecialchars($payment['remarks']); ?></td>
              <td><?php echo $payment['payment_month'] ? htmlspecialchars($payment['payment_month']) : 'N/A'; ?></td>
              <td>Rs. <?php echo number_format($payment['amount'], 2); ?></td>
              <td>
                <a href="../uploads/receipts/<?php echo htmlspecialchars($payment['receipt']); ?>" target="_blank" class="btn btn-small">View</a>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7" style="text-align: center;">No payments found</td></tr>
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

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
  background: #2563eb;
  color: white;
}

.btn:hover {
  background: #1d4ed8;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.8rem;
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
}
</style>
</body>
</html>
