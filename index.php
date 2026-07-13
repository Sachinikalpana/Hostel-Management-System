<?php
session_start();

if(isset($_SESSION['user_id']) && isset($_SESSION['role'])){
    header('Location: dashboard.php');
    exit();
}

include('includes/db.php');

$error = $success = "";

if(isset($_POST['login_submit'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)){
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role, full_name, room_number FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $user = $result->fetch_assoc();
            //hashing
            if(password_verify($password, $user['password'])){
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['room_number'] = $user['room_number'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}

if(isset($_POST['register_submit'])){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $home_address = trim($_POST['home_address']);
    $phone_digits = trim($_POST['phone_number']);
    $phone_number = '+94' . $phone_digits; // Store with +94 prefix
    $student_number = strtoupper(trim($_POST['student_number']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($full_name) || empty($email) || empty($home_address) || empty($phone_digits) || empty($student_number) || empty($password) || empty($confirm_password)){
        $error = "All fields are required.";
    } elseif(!preg_match('/@cmb\.ac\.lk$/', $email)){
        $error = "Email must end with @cmb.ac.lk";
    } elseif(!preg_match('/^\d{9}$/', $phone_digits)){  // Validate 9 digits only
        $error = "Phone number must be exactly 9 digits.";
    } elseif(!preg_match('/^[A-Z0-9]{10}$/', $student_number)){  // Validate 10-character alphanumeric
        $error = "Student number must be exactly 10 characters (letters and numbers only).";
    } elseif($password !== $confirm_password){
        $error = "Passwords do not match.";
    } elseif(strlen($password) < 8){
        $error = "Password must be at least 8 characters long.";
    } elseif(!preg_match('/[A-Z]/', $password)){
        $error = "Password must contain at least one uppercase letter.";
    } elseif(!preg_match('/[a-z]/', $password)){
        $error = "Password must contain at least one lowercase letter.";
    } elseif(!preg_match('/[0-9]/', $password)){
        $error = "Password must contain at least one number.";
    } else {
        // Check Email Already Used
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0){
            $error = "Email already registered.";
        } else {
            // Gen Username by Email
            $username = explode('@', $email)[0];
            
            // Hash Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name, home_address, phone_number, student_number) VALUES (?, ?, ?, 'student', ?, ?, ?, ?)");
            $stmt->bind_param('sssssss', $username, $email, $hashed_password, $full_name, $home_address, $phone_number, $student_number);
            
            if($stmt->execute()){
                $success = "Registration successful! You can now login.";
                $_POST = array(); // Clear form
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StaySmart Hostel Management - Login</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
  <div class="auth-header">
    <h1>StaySmart Hostel Management</h1>
    <p>Secure Access Portal</p>
  </div>
  
  <div class="auth-forms">
    <!-- Login Form -->
    <div class="auth-form-wrapper" id="login-form">
      <h2>Login</h2>
      
      <?php if($error && isset($_POST['login_submit'])): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <?php if($success): ?>
        <div class="alert alert-success">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>
      
      <form method="post" autocomplete="off">
        <div class="form-group">
          <label for="login_email">Email Address</label>
          <input type="email" id="login_email" name="email" required 
                 placeholder="your.email@cmb.ac.lk"
                 value="<?php echo isset($_POST['email']) && isset($_POST['login_submit']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
          <label for="login_password">Password</label>
          <input type="password" id="login_password" name="password" required 
                 placeholder="Enter your password">
        </div>
        
        <button type="submit" name="login_submit" class="btn btn-primary btn-block">Login</button>
      </form>
      
      <div class="auth-switch">
        <p>Don't have an account? <a href="#" onclick="showRegisterForm(); return false;">Register as Student</a></p>
      </div>
    </div>
    
    <!-- Reg Form -->
    <div class="auth-form-wrapper" id="register-form" style="display: none;">
      <h2>Student Registration</h2>
      
      <?php if($error && isset($_POST['register_submit'])): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <form method="post" autocomplete="off">
        <div class="form-group">
          <label for="full_name">Full Name *</label>
          <input type="text" id="full_name" name="full_name" required 
                 placeholder="Enter your full name"
                 value="<?php echo isset($_POST['full_name']) && isset($_POST['register_submit']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
        </div>
        
        <div class="form-group">
          <label for="register_email">Email Address *</label>
          <input type="email" id="register_email" name="email" required 
                 placeholder="your.name@cmb.ac.lk"
                 value="<?php echo isset($_POST['email']) && isset($_POST['register_submit']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          <small>Must end with @cmb.ac.lk</small>
        </div>
        
        <div class="form-group">
          <label for="home_address">Home Address *</label>
          <textarea id="home_address" name="home_address" required rows="2" 
                    placeholder="Enter your home address"><?php echo isset($_POST['home_address']) && isset($_POST['register_submit']) ? htmlspecialchars($_POST['home_address']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
          <label for="student_number">Student Number *</label>
          <input type="text" id="student_number" name="student_number" required 
                 placeholder="ex:2023T01205" 
                 pattern="[A-Za-z0-9]{10}" 
                 maxlength="10"
                 style="text-transform: uppercase;"
                 value="<?php echo isset($_POST['student_number']) && isset($_POST['register_submit']) ? htmlspecialchars($_POST['student_number']) : ''; ?>">
          <small>Must be exactly 10 characters (letters and numbers)</small>
        </div>
        
        <div class="form-group">
          <label for="phone_number">Phone Number *</label>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="padding: 12px 16px; background: #e2e8f0; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; font-weight: 600;">+94</span>
            <input type="text" id="phone_number" name="phone_number" required 
                   placeholder="771234567" 
                   pattern="\d{9}" 
                   maxlength="9"
                   style="flex: 1;"
                   value="<?php echo isset($_POST['phone_number']) && isset($_POST['register_submit']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
          </div>
          <small>Enter 9 digits after +94 (e.g., 771234567)</small>
        </div>
        
        <div class="form-group">
          <label for="register_password">Password *</label>
          <input type="password" id="register_password" name="password" required 
                 placeholder="Enter password">
          <small>Must contain: uppercase, lowercase, number, and be 8+ characters</small>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm Password *</label>
          <input type="password" id="confirm_password" name="confirm_password" required 
                 placeholder="Re-enter password">
        </div>
        
        <button type="submit" name="register_submit" class="btn btn-primary btn-block">Register</button>
      </form>
      
      <div class="auth-switch">
        <p>Already have an account? <a href="#" onclick="showLoginForm(); return false;">Login</a></p>
      </div>
    </div>
  </div>
</div>

<style>
.auth-page {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.95) 100%), 
              url('/images/login_bg.jpg') no-repeat center bottom;
  background-size: cover;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
}

.auth-page::before {
  content: '';
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 60vh;
  background: url('/images/login_bg.jpg') no-repeat center bottom;
  background-size: cover;
  opacity: 0.3;
  z-index: 0;
  pointer-events: none;
}

.auth-container {
  max-width: 500px;
  width: 100%;
  position: relative;
  z-index: 1;
}

.auth-header {
  text-align: center;
  color: white;
  margin-bottom: 40px;
}

.auth-header h1 {
  font-size: 2.5rem;
  margin-bottom: 10px;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), 0 2px 4px rgba(0, 0, 0, 0.3);
  font-weight: 700;
}

.auth-header p {
  font-size: 1.2rem;
  opacity: 1;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
  font-weight: 500;
  color: #f1f5f9;
}

.auth-forms {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.auth-form-wrapper {
  padding: 40px;
}

.auth-form-wrapper h2 {
  margin-top: 0;
  margin-bottom: 30px;
  color: #0f172a;
  font-size: 1.875rem;
  text-align: center;
  font-weight: 700;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #1e293b;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s ease;
  font-family: inherit;
  color: #0f172a;
  background: #ffffff;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #94a3b8;
  opacity: 1;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group small {
  display: block;
  margin-top: 6px;
  color: #475569;
  font-size: 0.875rem;
}

.form-group textarea {
  resize: vertical;
  min-height: 60px;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 8px;
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
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.btn-block {
  width: 100%;
  margin-top: 10px;
}

.alert {
  padding: 15px 18px;
  margin-bottom: 20px;
  border-radius: 8px;
  font-weight: 500;
}

.alert-error {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #7f1d1d;
  border-left: 4px solid #dc2626;
}

.alert-success {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #064e3b;
  border-left: 4px solid #10b981;
}

.auth-switch {
  text-align: center;
  margin-top: 25px;
  padding-top: 25px;
  border-top: 1px solid #e2e8f0;
}

.auth-switch p {
  color: #475569;
  margin: 0;
  font-size: 0.95rem;
}

.auth-switch a {
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s ease;
}

.auth-switch a:hover {
  color: #1d4ed8;
  text-decoration: underline;
}

@media (max-width: 768px) {
  .auth-header h1 {
    font-size: 2rem;
  }
  
  .auth-header p {
    font-size: 1rem;
  }
  
  .auth-form-wrapper {
    padding: 30px 25px;
  }
}
</style>

<script>
function showRegisterForm() {
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('register-form').style.display = 'block';
}

function showLoginForm() {
    document.getElementById('register-form').style.display = 'none';
    document.getElementById('login-form').style.display = 'block';
}

// Show Error
<?php if($error && isset($_POST['register_submit'])): ?>
    showRegisterForm();
<?php endif; ?>
</script>
</body>
</html>
