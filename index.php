<?php session_start(); include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Little Lemon Restaurant</title>
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



        <?php if(isset($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          
          <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>

          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<div class="bg-light py-5">
  <div class="container text-center">
    <h1 class="display-4 fw-bold">Welcome to Little Lemon</h1>
    <p class="lead">Experience the finest dining with our delicious Mediterranean cuisine</p>
    <?php if(!isset($_SESSION['user_id'])): ?>
      <a href="register.php" class="btn btn-success btn-lg mx-2">Join Us</a>
      <a href="login.php" class="btn btn-primary btn-lg mx-2">Login</a>
      <a href="reservation.php" class="btn btn-info btn-lg mx-2">Book Table (Guest)</a>
    <?php else: ?>
      <a href="reservation.php" class="btn btn-success btn-lg mx-2">Book a Table</a>

      <a href="dashboard.php" class="btn btn-primary btn-lg mx-2">My Account</a>
    <?php endif; ?>



  </div>
</div>

<!-- Menu Preview -->
<div class="container my-5">
  <h2 class="text-center mb-4">Our Popular Dishes</h2>
  <div class="row">
    <?php
    $menu_items = $conn->query("SELECT * FROM menu LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
    if(count($menu_items) > 0):
      foreach($menu_items as $item):
    ?>
        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card h-100 menu-card">
            <?php if(!empty($item['image'])): ?>
              <img src="assets/img/<?php echo htmlspecialchars($item['image']); ?>" class="card-img-top menu-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
            <?php else: ?>
              <div class="placeholder-image d-flex align-items-center justify-content-center" style="height: 200px; background-color: #f8f9fa;">
                <i class="bi bi-image" style="font-size: 3rem; color: #ccc;"></i>
              </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
              <?php if(!empty($item['description'])): ?>
                <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($item['description'], 0, 80)) . '...'; ?></p>
              <?php endif; ?>
              <div class="mt-auto pt-2">
                <p class="card-text h5 text-success fw-bold">RM <?php echo number_format($item['price'], 2); ?></p>
              </div>
            </div>
          </div>
        </div>
    <?php
      endforeach;
    else:
      echo '<div class="col-12"><p class="text-center">No menu items available yet.</p></div>';
    endif;
    ?>
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