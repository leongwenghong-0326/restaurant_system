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

// Check for success message from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); // Clear the message after displaying
}

if(isset($_POST['login'])){    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed! Please try again.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Check what's in the database
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        if (!$user) {
            // Show all users for debugging
            $allUsers = $conn->query("SELECT user_id, email, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
            $error = "User not found with email: " . htmlspecialchars($email) . "<br><br>";
            $error .= "Users in database:<br>";
            if ($allUsers) {
                foreach ($allUsers as $u) {
                    $error .= "ID: " . $u['user_id'] . " | Email: " . htmlspecialchars($u['email']) . " | Name: " . htmlspecialchars($u['name']) . "<br>";
                }
            } else {
                $error .= "No users found in database.";
            }
        } else {
            if(password_verify($password, $user['password'])){        
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'] ?? 'user'; // Default to 'user' if role is null
                // Redirect all users to dashboard (admin panel removed)
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password!";
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
<title>Login - Little Lemon</title>
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
        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-6 col-sm-8">
      <div class="card shadow">
        <div class="card-header bg-success text-white text-center">
          <h3 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Sign In</h3>
        </div>
        <div class="card-body p-4">
          <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
          <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
          
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
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
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
              </div>
            </div>
            <div class="d-grid">
              <button type="submit" name="login" class="btn btn-success btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
              </button>
            </div>
          </form>
          
          <div class="text-center mt-3">
            <p>Don't have an account? <a href="register.php" class="text-success">Register here</a></p>
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