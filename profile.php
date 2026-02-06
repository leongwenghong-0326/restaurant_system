<?php
session_start();
include 'db.php';

// Only logged in users
if(!isset($_SESSION['user_id'])){    
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Fetch user data
try {
    $user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Failed to load user data.";
}

// Handle profile update
if(isset($_POST['update_profile'])){    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $error = "Name and email are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        try {
            // Check if email is already taken by another user
            $email_check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $email_check->execute([$email, $user_id]);
            if($email_check->rowCount() > 0){
                $error = "Email is already registered to another account!";
            } else {
                // Handle password change
                if (!empty($current_password)) {
                    if (empty($new_password) || empty($confirm_password)) {
                        $error = "Please enter both new password and confirmation!";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match!";
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error = "Current password is incorrect!";
                    } else {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE user_id=?");
                        $result = $stmt->execute([$name, $email, $phone, $hashed_password, $user_id]);
                        
                        if ($result) {
                            $success = "Profile updated successfully!";
                            // Refresh user data
                            $user_stmt->execute([$user_id]);
                            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $error = "Failed to update profile.";
                        }
                    }
                } else {
                    // Update without password change
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE user_id=?");
                    $result = $stmt->execute([$name, $email, $phone, $user_id]);
                    
                    if ($result) {
                        $success = "Profile updated successfully!";
                        // Refresh user data
                        $user_stmt->execute([$user_id]);
                        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = "Failed to update profile.";
                    }
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Fetch user's favorite items
try {
    $favorites = $conn->prepare("SELECT m.* FROM user_favorites uf JOIN menu m ON uf.menu_id = m.menu_id WHERE uf.user_id = ? ORDER BY uf.created_at DESC");
    $favorites->execute([$user_id]);
    $favorite_items = $favorites->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $favorite_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Little Lemon</title>
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
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="reservation.php">Reservations</a></li>

        <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header bg-success text-white">
          <h3 class="mb-0"><i class="bi bi-person-circle"></i> Edit Profile</h3>
        </div>
        <div class="card-body">
          <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
          
          <form method="POST">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            
            <hr>
            <h5 class="mb-3"><i class="bi bi-shield-lock"></i> Change Password (Optional)</h5>
            
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" placeholder="Enter current password to change">
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
              </div>
            </div>
            
            <div class="d-grid">
              <button type="submit" name="update_profile" class="btn btn-success btn-lg">
                <i class="bi bi-save"></i> Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="bi bi-person-badge"></i> Account Information</h4>
        </div>
        <div class="card-body">
          <p><i class="bi bi-person text-success"></i> <strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? 'N/A') ?></p>
          <p><i class="bi bi-envelope text-success"></i> <strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
          <p><i class="bi bi-telephone text-success"></i> <strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
          <p><i class="bi bi-calendar text-success"></i> <strong>Member Since:</strong> <?= date('M j, Y', strtotime($user['created_at'] ?? 'now')) ?></p>
          <p><i class="bi bi-shield text-success"></i> <strong>Account Type:</strong> <?= ucfirst(htmlspecialchars($user['role'] ?? 'user')) ?></p>
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