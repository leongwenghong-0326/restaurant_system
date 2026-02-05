<?php
session_start();
include 'db.php';

// Only logged in users
if(!isset($_SESSION['user_id'])){    
    header("Location: login.php");
    exit;
}

$reservation_id = $_GET['reservation_id'] ?? 0;
$error = "";
$success = "";

// Check reservation belongs to user
$stmt = $conn->prepare("SELECT * FROM reservations WHERE reservation_id=? AND user_id=?");
$stmt->execute([$reservation_id, $_SESSION['user_id']]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$reservation){    
    die("Reservation not found or you don't have permission!");
}

// Handle food order
if(isset($_POST['order_food'])){    
    $menu_id = $_POST['menu_id'];
    $quantity = (int)$_POST['quantity'];

    $menu_item = $conn->prepare("SELECT * FROM menu WHERE menu_id=?");
    $menu_item->execute([$menu_id]);
    $item = $menu_item->fetch(PDO::FETCH_ASSOC);

    if($item){
        $total_price = $item['price'] * $quantity;
        $stmt = $conn->prepare("INSERT INTO orders (reservation_id, menu_id, quantity, total_price) VALUES (?,?,?,?)");
        if($stmt->execute([$reservation_id, $menu_id, $quantity, $total_price])){
            $success = "Food ordered successfully!";
        }
    } else {
        $error = "Menu item not found!";
    }
}

// Fetch all menu items
$menu_items = $conn->query("SELECT * FROM menu")->fetchAll(PDO::FETCH_ASSOC);

// Fetch orders for this reservation
$orders = $conn->prepare("SELECT o.*, m.name, m.image FROM orders o JOIN menu m ON o.menu_id=m.menu_id WHERE o.reservation_id=?");
$orders->execute([$reservation_id]);
$order_list = $orders->fetchAll(PDO::FETCH_ASSOC);

// Calculate total amount
$total_amount = 0;
foreach($order_list as $order) {
    $total_amount += $order['total_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Food - Little Lemon</title>
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
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-light">
        <div class="card-body">
          <h3 class="card-title">Order Food for Your Reservation</h3>
          <p class="card-text">
            <i class="bi bi-calendar-event"></i> Date: <strong><?= date('M j, Y', strtotime($reservation['date'])) ?></strong> | 
            <i class="bi bi-clock"></i> Time: <strong><?= date('g:i A', strtotime($reservation['time'])) ?></strong> | 
            <i class="bi bi-table"></i> Table: <strong><?= htmlspecialchars($reservation['table_id']) ?></strong>
          </p>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header bg-success text-white">
          <h3 class="mb-0"><i class="bi bi-cart-plus"></i> Order Food</h3>
        </div>
        <div class="card-body">
          <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

          <form method="POST" class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Select Menu Item</label>
              <select name="menu_id" class="form-select" required>
                <option value="">Choose a menu item...</option>
                <?php foreach($menu_items as $item): ?>
                  <option value="<?= $item['menu_id'] ?>">
                    <?= htmlspecialchars($item['name']) ?> - RM <?= number_format($item['price'], 2) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" class="form-control" min="1" value="1" required>
            </div>
            <div class="col-12">
              <button type="submit" name="order_food" class="btn btn-success w-100">
                <i class="bi bi-cart"></i> Add to Order
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h3 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h3>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold">Total Amount:</span>
            <span class="h4 text-success">RM <?= number_format($total_amount, 2) ?></span>
          </div>
          <div class="d-grid gap-2">
            <a href="reservation.php" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left"></i> Back to Reservations
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h3 class="mb-0"><i class="bi bi-list-check"></i> Current Orders</h3>
        </div>
        <div class="card-body">
          <?php if(empty($order_list)): ?>
            <div class="text-center py-4">
              <i class="bi bi-cart-x" style="font-size: 3rem; color: #ccc;"></i>
              <p class="mt-3">No orders placed yet for this reservation.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Menu Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($order_list as $o): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if(!empty($o['image'])): ?>
                            <img src="assets/img/<?= $o['image'] ?>" alt="<?= htmlspecialchars($o['name']) ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                          <?php endif; ?>
                          <span><?= htmlspecialchars($o['name']) ?></span>
                        </div>
                      </td>
                      <td><?= $o['quantity'] ?></td>
                      <td>RM <?= number_format($o['price'] ?? $o['total_price']/$o['quantity'], 2) ?></td>
                      <td>RM <?= number_format($o['total_price'], 2) ?></td>
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