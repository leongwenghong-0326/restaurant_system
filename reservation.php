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

// Handle booking
if(isset($_POST['book'])){    
    try {
        $table_id = (int)$_POST['table_id'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        
        // Validate inputs
        if (empty($table_id) || empty($date) || empty($time)) {
            $error = "All fields are required!";
        } elseif (!strtotime($date) || !strtotime($time)) {
            $error = "Invalid date or time format!";
        } else {
            // Prevent double booking
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE table_id=? AND date=? AND time=? AND status='booked'");
            $stmt->execute([$table_id, $date, $time]);
            if($stmt->rowCount() > 0){
                $error = "Sorry, this table is already booked at this time!";
            } else {
                $stmt = $conn->prepare("INSERT INTO reservations (user_id, table_id, date, time) VALUES (?,?,?,?)");
                if($stmt->execute([$user_id, $table_id, $date, $time])){
                    $success = "Table booked successfully!";
                } else {
                    $error = "Failed to book table. Please try again.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "An error occurred while processing your request. Please try again.";
    }
}

// Fetch user's reservations
try {
    $reservations = $conn->prepare("SELECT r.*, t.table_name FROM reservations r JOIN tables t ON r.table_id=t.table_id WHERE r.user_id=? ORDER BY r.date, r.time");
    $reservations->execute([$user_id]);
    $reservation_list = $reservations->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reservation_list = [];
    $error = "Unable to load reservations. Please try again later.";
}

// Fetch all available tables
try {
    $tables = $conn->query("SELECT * FROM tables WHERE status='available' ORDER BY table_id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tables = [];
    $error = "Unable to load tables. Please try again later.";
}

// Get stats
try {
    $total_reservations = $conn->prepare("SELECT COUNT(*) as total_reservations FROM reservations WHERE user_id=?");
    $total_reservations->execute([$user_id]);
    $total_count = $total_reservations->fetch(PDO::FETCH_ASSOC)['total_reservations'];
    
    $upcoming_reservations = $conn->prepare("SELECT COUNT(*) as upcoming_reservations FROM reservations WHERE user_id=? AND date >= CURDATE()");
    $upcoming_reservations->execute([$user_id]);
    $upcoming_count = $upcoming_reservations->fetch(PDO::FETCH_ASSOC)['upcoming_reservations'];
    
    $total_orders = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders o JOIN reservations r ON o.reservation_id=r.reservation_id WHERE r.user_id=?");
    $total_orders->execute([$user_id]);
    $orders_count = $total_orders->fetch(PDO::FETCH_ASSOC)['total_orders'];
} catch (Exception $e) {
    $total_count = 0;
    $upcoming_count = 0;
    $orders_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Table - Little Lemon</title>
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
        <li class="nav-item"><a class="nav-link active" href="reservation.php">Reservations</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="row">
    <div class="col-lg-8">
      <div class="card reservation-form">
        <div class="card-header bg-success text-white">
          <h3 class="mb-0"><i class="bi bi-calendar-plus"></i> Book a Table</h3>
        </div>
        <div class="card-body">
          <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

          <form method="POST" class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Select Table</label>
              <select name="table_id" class="form-select" required>
                <option value="">Choose a table...</option>
                <?php 
                if (!empty($tables)) {
                    foreach($tables as $table): 
                ?>
                  <option value="<?= $table['table_id'] ?>">
                    <?= htmlspecialchars($table['table_name']) ?> (Seats: <?= $table['seats'] ?>)
                  </option>
                <?php 
                    endforeach; 
                } else {
                    echo "<option value='' disabled>No tables available at the moment</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date</label>
              <input type="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Time</label>
              <input type="time" name="time" class="form-control" required min="11:00" max="22:00">
            </div>
            <div class="col-12">
              <button type="submit" name="book" class="btn btn-success w-100">
                <i class="bi bi-calendar-check"></i> Book Table
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h3 class="mb-0"><i class="bi bi-info-circle"></i> Reservation Info</h3>
        </div>
        <div class="card-body">
          <p><i class="bi bi-clock"></i> Open: 11:00 AM - 10:00 PM</p>
          <p><i class="bi bi-calendar-event"></i> Reservations available up to 30 days in advance</p>
          <p><i class="bi bi-person"></i> Maximum 8 guests per table</p>
          <?php if (!empty($tables)): ?>
            <p><i class="bi bi-check-circle"></i> <strong><?= count($tables) ?> tables</strong> currently available</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h3 class="mb-0"><i class="bi bi-list-check"></i> Your Reservations</h3>
        </div>
        <div class="card-body">
          <?php if(empty($reservation_list)): ?>
            <div class="text-center py-4">
              <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
              <p class="mt-3">You don't have any reservations yet.</p>
              <a href="#" class="btn btn-success" onclick="document.querySelector('select[name=table_id]').focus()">Book Your First Table</a>
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
                    <th>Order</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($reservation_list as $res): ?>
                    <tr>
                      <td><?= htmlspecialchars($res['table_name']) ?></td>
                      <td><?= date('M j, Y', strtotime($res['date'])) ?></td>
                      <td><?= date('g:i A', strtotime($res['time'])) ?></td>
                      <td><span class="badge bg-<?= $res['status'] == 'cancelled' ? 'danger' : 'success' ?>"><?= ucfirst(htmlspecialchars($res['status'])) ?></span></td>
                      <td>
                        <a href="order.php?reservation_id=<?= $res['reservation_id'] ?>" class="btn btn-sm btn-success">
                          <i class="bi bi-cart"></i> Order
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