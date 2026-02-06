<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_reservations FROM reservations WHERE user_id=?");
$stmt->execute([$user_id]);
$total_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total_reservations'];

$stmt = $conn->prepare("SELECT COUNT(*) as upcoming_reservations FROM reservations WHERE user_id=? AND date >= CURDATE()");
$stmt->execute([$user_id]);
$upcoming_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['upcoming_reservations'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders o JOIN reservations r ON o.reservation_id=r.reservation_id WHERE r.user_id=?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Get reservations
$reservations = $conn->prepare("SELECT r.*, t.table_name FROM reservations r JOIN tables t ON r.table_id=t.table_id WHERE r.user_id=? ORDER BY r.date, r.time");
$reservations->execute([$user_id]);
$res_list = $reservations->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Little Lemon</title>
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
        <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="reservation.php">Reservations</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="row mb-4">
    <div class="col-12">
      <h1>Dashboard</h1>
      <p class="lead">Welcome back, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>!</p>
    </div>
  </div>
  
  <!-- Stats Section -->
  <div class="dashboard-stats">
    <div class="stat-card">
      <i class="bi bi-calendar-check text-success"></i>
      <h3><?= $total_reservations ?></h3>
      <p>Total Reservations</p>
    </div>
    <div class="stat-card">
      <i class="bi bi-clock text-primary"></i>
      <h3><?= $upcoming_reservations ?></h3>
      <p>Upcoming Reservations</p>
    </div>
    <div class="stat-card">
      <i class="bi bi-cart text-warning"></i>
      <h3><?= $total_orders ?></h3>
      <p>Total Orders</p>
    </div>
  </div>
  
  <!-- Quick Actions -->
  <div class="row mb-4">
    <div class="col-12">
      <h3>Quick Actions</h3>
      <div class="d-flex flex-wrap gap-2">
        <a href="reservation.php" class="btn btn-success"><i class="bi bi-calendar-plus"></i> Make Reservation</a>
        
        <a href="index.php" class="btn btn-primary"><i class="bi bi-menu-app"></i> View Menu</a>

        <a href="profile.php" class="btn btn-outline-dark"><i class="bi bi-person-circle"></i> Profile</a>
      </div>
    </div>
  </div>
  
  <!-- Reservations Section -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-success text-white">
          <h3 class="mb-0"><i class="bi bi-calendar-week"></i> Your Reservations</h3>
        </div>
        <div class="card-body">
          <?php if(count($res_list) == 0): ?>
            <div class="text-center py-4">
              <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
              <p class="mt-3">You don't have any reservations yet.</p>
              <a href="reservation.php" class="btn btn-success"><i class="bi bi-calendar-plus"></i> Book a Table Now</a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Table</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($res_list as $r): ?>
                    <tr>
                      <td><?= htmlspecialchars($r['table_name']) ?></td>
                      <td><?= date('M j, Y', strtotime($r['date'])) ?></td>
                      <td><?= date('g:i A', strtotime($r['time'])) ?></td>
                      <td><span class="badge bg-<?= $r['status'] == 'cancelled' ? 'danger' : 'success' ?>"><?= ucfirst(htmlspecialchars($r['status'])) ?></span></td>
                      <td>
                        <a href="order.php?reservation_id=<?= $r['reservation_id'] ?>" class="btn btn-sm btn-outline-success me-1" title="Order Food">
                          <i class="bi bi-cart"></i>
                        </a>
                        <a href="reservation.php" class="btn btn-sm btn-outline-primary" title="View Details">
                          <i class="bi bi-eye"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
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