<?php
session_start();
include 'db.php';

// Include CSRF protection
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

$error = "";
$success = "";

if(isset($_POST['register'])){    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed! Please try again.";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $phone = trim($_POST['phone']);

        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        if($stmt->rowCount() > 0){
            $error = "Email already registered!";
        } else {
            // Insert user with explicit role
            $stmt = $conn->prepare("INSERT INTO users (name,email,password,phone,role) VALUES (?,?,?,?,?)");
            $result = $stmt->execute([$name,$email,$password,$phone,'user']);
            
            if ($result) {
                // Verify insertion
                $checkStmt = $conn->prepare("SELECT * FROM users WHERE email=?");
                $checkStmt->execute([$email]);
                $newUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($newUser) {
                    $_SESSION['success'] = "Registered successfully! Login now.";
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Registration failed - user verification failed.";
                }
            } else {
                $error = "Registration failed. Database error occurred.";
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">Little Lemon</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-6 col-sm-8">
      <div class="card shadow">
        <div class="card-header bg-success text-white text-center">
          <h3 class="mb-0"><i class="bi bi-person-plus"></i> Create Account</h3>
        </div>
        <div class="card-body p-4">
          <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
          <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
          
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="mb-3">
              <label for="name" class="form-label">Full Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter your full name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
              </div>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
              </div>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="phone" class="form-label">Phone Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                <input type="tel" name="phone" id="phone" class="form-control" placeholder="Enter your phone number" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
              </div>
            </div>
            <div class="d-grid">
              <button type="submit" name="register" class="btn btn-success btn-lg">
                <i class="bi bi-person-plus"></i> Register
              </button>
            </div>
          </form>
          
          <div class="text-center mt-3">
            <p>Already have an account? <a href="login.php" class="text-success">Sign in here</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4 mt-5">
  <div class="container">
    <p>&copy; 2026 Little Lemon Restaurant. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>