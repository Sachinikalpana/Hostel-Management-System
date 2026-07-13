<?php 
session_start();
include('../includes/auth_check.php');
checkRole(['student']);
include('../includes/db.php');
include('../includes/navbar.php');

$error = $success = "";

$student_query = $conn->prepare("SELECT student_number, full_name FROM users WHERE id = ?");
$student_query->bind_param('i', $_SESSION['user_id']);
$student_query->execute();
$student_result = $student_query->get_result();
$student_data = $student_result->fetch_assoc();
$student_query->close();

$current_student_number = $student_data['student_number'];
$student_name = $student_data['full_name'];
$is_first_payment = empty($current_student_number);

// Payment Submit
if(isset($_POST['payment_submit'])){
    $student_number = strtoupper(trim($_POST['student_number'])); // Capital Letters
    $remarks = $_POST['remarks'];
    $payment_month = isset($_POST['payment_month']) ? $_POST['payment_month'] : NULL;
    $amount = floatval($_POST['amount']);
    
    if(empty($student_number)){
        $error = "Student number is required.";
    } elseif(!preg_match('/^[A-Z0-9]{10}$/i', $student_number)){
        $error = "Student number must be exactly 10 characters (letters and numbers only).";
    } elseif(empty($remarks) || $amount <= 0){
        $error = "All fields are required and amount must be greater than 0.";
    } elseif($remarks == 'Monthly Rent' && empty($payment_month)){
        $error = "Please select the month for rent payment.";
    } elseif(!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid receipt file.";
    } else {
        if($is_first_payment){
            $update_stmt = $conn->prepare("UPDATE users SET student_number = ? WHERE id = ?");
            $update_stmt->bind_param('si', $student_number, $_SESSION['user_id']);
            if(!$update_stmt->execute()){
                $error = "Failed to save student number.";
                $update_stmt->close();
            } else {
                $update_stmt->close();
                $current_student_number = $student_number;
                $is_first_payment = false;
            }
        }
        
        if(empty($error)){
            // Check Already Paid
            if($remarks == 'Monthly Rent'){
                $check_stmt = $conn->prepare("SELECT id FROM payments WHERE user_id = ? AND payment_month = ?");
                $check_stmt->bind_param('is', $_SESSION['user_id'], $payment_month);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if($check_result->num_rows > 0){
                    $error = "You have already paid rent for " . $payment_month . ".";
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    $file = $_FILES['receipt'];
                    $allowed_ext = ['pdf','jpg','jpeg','png'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if(!in_array($ext, $allowed_ext)){
                        $error = "Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.";
                    } elseif($file['size'] > 5*1024*1024){
                        $error = "File size must be under 5MB.";
                    } else {
                        $upload_dir = "../uploads/receipts/";
                        if(!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $date_str = date('Ymd_His');
                        $payment_identifier = str_replace(' ', '_', $remarks);
                        $month_str = $payment_month ? '_' . str_replace(' ', '_', $payment_month) : '';
                        $filename = $student_number . '_' . $payment_identifier . $month_str . '_' . $date_str . '.' . $ext;
                        $uploaded_path = $upload_dir . $filename;
                        
                        if(move_uploaded_file($file['tmp_name'], $uploaded_path)){
                            $stmt = $conn->prepare("INSERT INTO payments (user_id, student_number, remarks, payment_month, amount, receipt) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param('isssds', $_SESSION['user_id'], $student_number, $remarks, $payment_month, $amount, $filename);
                            
                            if($stmt->execute()){
                                $success = "Payment recorded successfully! Receipt uploaded.";
                                $_POST = array();
                            } else {
                                $error = "Failed to save payment record.";
                                if(file_exists($uploaded_path)) unlink($uploaded_path);
                            }
                            $stmt->close();
                        } else {
                            $error = "Failed to upload receipt file.";
                        }
                    }
                }
            } else {
                // Non-rent Payments
                $file = $_FILES['receipt'];
                $allowed_ext = ['pdf','jpg','jpeg','png'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if(!in_array($ext, $allowed_ext)){
                    $error = "Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.";
                } elseif($file['size'] > 5*1024*1024){
                    $error = "File size must be under 5MB.";
                } else {
                    $upload_dir = "../uploads/receipts/";
                    if(!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $date_str = date('Ymd_His');
                    $payment_identifier = str_replace(' ', '_', $remarks);
                    $month_str = $payment_month ? '_' . str_replace(' ', '_', $payment_month) : '';
                    $filename = $student_number . '_' . $payment_identifier . $month_str . '_' . $date_str . '.' . $ext;
                    $uploaded_path = $upload_dir . $filename;
                    
                    if(move_uploaded_file($file['tmp_name'], $uploaded_path)){
                        $stmt = $conn->prepare("INSERT INTO payments (user_id, student_number, remarks, payment_month, amount, receipt) VALUES (?, ?, ?, NULL, ?, ?)");
                        $stmt->bind_param('issds', $_SESSION['user_id'], $student_number, $remarks, $amount, $filename);
                        
                        if($stmt->execute()){
                            $success = "Payment recorded successfully! Receipt uploaded.";
                            $_POST = array();
                        } else {
                            $error = "Failed to save payment record.";
                            if(file_exists($uploaded_path)) unlink($uploaded_path);
                        }
                        $stmt->close();
                    } else {
                        $error = "Failed to upload receipt file.";
                    }
                }
            }
        }
    }
}

// Get Paid Months
$paid_months = [];
$paid_query = $conn->prepare("SELECT payment_month FROM payments WHERE user_id = ? AND payment_month IS NOT NULL");
$paid_query->bind_param('i', $_SESSION['user_id']);
$paid_query->execute();
$paid_result = $paid_query->get_result();
while($row = $paid_result->fetch_assoc()){
    $paid_months[] = $row['payment_month'];
}
$paid_query->close();

// Recent Payments
$recent_payments = $conn->query("SELECT * FROM payments WHERE user_id = {$_SESSION['user_id']} ORDER BY paid_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments - Student Portal</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>Fee Payment</h1>
    <p>Submit your payment records</p>
  </div>
  
  <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  
  <div class="dashboard-section">
    <h2>Submit Payment</h2>
    <form method="post" enctype="multipart/form-data" class="form">
      <div class="form-group">
        <label for="student_number">Student Number *</label>
        <!-- Auto-fill -->
        <input type="text" id="student_number" name="student_number" required 
               value="<?php echo $is_first_payment ? (isset($_POST['student_number']) ? htmlspecialchars($_POST['student_number']) : '') : htmlspecialchars($current_student_number); ?>"
               <?php echo $is_first_payment ? '' : 'readonly'; ?>
               placeholder="Enter your 10-character student number"
               maxlength="10"
               style="<?php echo $is_first_payment ? '' : 'background-color: #f0f0f0; cursor: not-allowed;'; ?>">
        <?php if($is_first_payment): ?>
        <small style="color: #e74c3c; font-weight: 600;">This is your first payment. Please enter your 10-character student number (letters and numbers).</small>
        <?php else: ?>
        <small style="color: #27ae60; font-weight: 600;">Your student number is saved and will be used for all payments.</small>
        <?php endif; ?>
      </div>
      
      <div class="form-group">
        <label for="remarks">Payment Type *</label>
        <select id="remarks" name="remarks" required onchange="toggleMonthSelection()">
          <option value="">-- Select Payment Type --</option>
          <option value="Monthly Rent" <?php echo (isset($_POST['remarks']) && $_POST['remarks'] == 'Monthly Rent') ? 'selected' : ''; ?>>Monthly Rent</option>
          <option value="Hostel Fee" <?php echo (isset($_POST['remarks']) && $_POST['remarks'] == 'Hostel Fee') ? 'selected' : ''; ?>>Hostel Fee</option>
          <option value="Mess Fee" <?php echo (isset($_POST['remarks']) && $_POST['remarks'] == 'Mess Fee') ? 'selected' : ''; ?>>Mess Fee</option>
          <option value="Security Deposit" <?php echo (isset($_POST['remarks']) && $_POST['remarks'] == 'Security Deposit') ? 'selected' : ''; ?>>Security Deposit</option>
          <option value="Other" <?php echo (isset($_POST['remarks']) && $_POST['remarks'] == 'Other') ? 'selected' : ''; ?>>Other</option>
        </select>
      </div>
      
      <div class="form-group" id="month-selection" style="display: none;">
        <label for="payment_month">Select Month *</label>
        <select id="payment_month" name="payment_month">
          <option value="">-- Select Month --</option>
          <?php
          $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
          $current_year = date('Y');
          foreach($months as $month){
            $month_value = $month . ' ' . $current_year;
            $is_paid = in_array($month_value, $paid_months);
            $disabled = $is_paid ? 'disabled' : '';
            $style = $is_paid ? 'color: green; font-weight: bold;' : '';
            echo "<option value='$month_value' $disabled style='$style'>$month_value" . ($is_paid ? ' (Paid)' : '') . "</option>";
          }
          ?>
        </select>
        <small>Green months are already paid</small>
      </div>
      
      <div class="form-group">
        <label for="amount">Amount (Rs.) *</label>
        <input type="number" id="amount" name="amount" min="1" step="0.01" required 
               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
               placeholder="Enter amount in rupees">
      </div>
      
      <div class="form-group">
        <label for="receipt">Upload Receipt *</label>
        <input type="file" id="receipt" name="receipt" accept=".pdf,.jpg,.jpeg,.png" required>
        <small>Accepted formats: PDF, JPG, PNG (Max 5MB)</small>
      </div>
      
      <button type="submit" name="payment_submit" class="btn btn-primary">Submit Payment</button>
    </form>
  </div>
  
  <?php if($recent_payments && $recent_payments->num_rows > 0): ?>
  <div class="dashboard-section">
    <h2>Your Payment History</h2>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Student Number</th>
            <th>Payment Type</th>
            <th>Month</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($payment = $recent_payments->fetch_assoc()): ?>
          <tr>
            <td><?php echo date('M d, Y', strtotime($payment['paid_at'])); ?></td>
            <td><?php echo htmlspecialchars($payment['student_number']); ?></td>
            <td><?php echo htmlspecialchars($payment['remarks']); ?></td>
            <td><?php echo $payment['payment_month'] ? htmlspecialchars($payment['payment_month']) : 'N/A'; ?></td>
            <td>Rs. <?php echo number_format($payment['amount'], 2); ?></td>
            <td><span class="status-badge status-completed">Completed</span></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
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
.form-group select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s ease;
  background-color: white;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group small {
  display: block;
  margin-top: 6px;
  color: #64748b;
  font-size: 0.875rem;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
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

.status-badge {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
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
}
</style>

<script>
function toggleMonthSelection() {
    var paymentType = document.getElementById('remarks').value;
    var monthSelection = document.getElementById('month-selection');
    var monthSelect = document.getElementById('payment_month');
    
    if(paymentType === 'Monthly Rent') {
        monthSelection.style.display = 'block';
        monthSelect.required = true;
    } else {
        monthSelection.style.display = 'none';
        monthSelect.required = false;
        monthSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleMonthSelection();
});
</script>
</body>
</html>
