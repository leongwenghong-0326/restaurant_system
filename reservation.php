<?php
session_start();
include 'db.php';

// Check if user is logged in
$is_guest = !isset($_SESSION['user_id']);
$user_id = $is_guest ? ($_SESSION['guest_user_id'] ?? null) : $_SESSION['user_id'];
$error = "";
$success = "";

// Handle table booking
if(isset($_POST['book'])) {
    try {
        $table_id = (int)$_POST['table_id'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $party_size = (int)$_POST['party_size'];

        // Validate inputs
        if (empty($table_id) || empty($date) || empty($time) || empty($party_size)) {
            $error = "All fields are required!";
        } elseif (!strtotime($date) || !strtotime($time)) {
            $error = "Invalid date or time!";
        } elseif ($party_size < 1 || $party_size > 8) {
            $error = "Party size must be 1-8 guests!";
        } else {
            // Prevent double booking
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE table_id=? AND date=? AND time=? AND status='booked'");
            $stmt->execute([$table_id, $date, $time]);
            if($stmt->rowCount() > 0){
                $error = "This table is already booked at this time!";
            } else {
                // Guest user handling
                if ($is_guest) {
                    // Create guest session and DB user if not exist
                    if (!isset($_SESSION['guest_user_id'])) {
                        $guest_email = 'guest_' . time() . '@temporary.com';
                        $guest_password = password_hash('temp_' . time(), PASSWORD_DEFAULT);

                        $guest_stmt = $conn->prepare("INSERT INTO users (name,email,password,role,is_guest) VALUES (?,?,?,?,true)");
                        if ($guest_stmt->execute(['Guest User', $guest_email, $guest_password, 'user'])) {
                            $guest_user_id = $conn->lastInsertId();
                            $_SESSION['guest_user_id'] = $guest_user_id;
                        } else {
                            $error = "Failed to create guest account!";
                        }
                    }
                    $user_id = $_SESSION['guest_user_id'];
                }

                // Insert reservation
                $res_stmt = $conn->prepare("INSERT INTO reservations (user_id, table_id, date, time, party_size, status) VALUES (?,?,?,?,?,?)");
                if ($res_stmt->execute([$user_id, $table_id, $date, $time, $party_size, 'booked'])) {
                    $success = "Table booked successfully! You can now order food.";
                } else {
                    $error = "Failed to book table!";
                }
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch user's reservations (logged-in or guest)
$reservation_list = [];
if ($user_id) {
    $res_query = $conn->prepare("
        SELECT r.*, t.table_name 
        FROM reservations r
        JOIN tables t ON r.table_id=t.table_id
        WHERE r.user_id=?
        ORDER BY r.date, r.time
    ");
    $res_query->execute([$user_id]);
    $reservation_list = $res_query->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch available tables
try {
    $tables = $conn->query("SELECT * FROM tables WHERE status='available' ORDER BY table_id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tables = [];
    $error = "Unable to load tables!";
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
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <?php if(!$is_guest): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link active" href="reservation.php">Reservations</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header bg-success text-white"><h3><i class="bi bi-calendar-plus"></i> Book a Table</h3></div>
        <div class="card-body">
          <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
          <form method="POST" class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Select Table</label>
              <select name="table_id" class="form-select" required>
                <option value="">Choose a table...</option>
                <?php foreach($tables as $table): ?>
                  <option value="<?= $table['table_id'] ?>"><?= htmlspecialchars($table['table_name']) ?> (Seats: <?= $table['seats'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date</label>
              <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Time</label>
              <input type="time" name="time" class="form-control" required min="11:00" max="22:00">
            </div>
            <div class="col-md-6">
              <label class="form-label">Party Size</label>
              <input type="number" name="party_size" class="form-control" required min="1" max="8" value="2">
            </div>
            <div class="col-12">
              <button type="submit" name="book" class="btn btn-success w-100"><i class="bi bi-calendar-check"></i> Book Table</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-primary text-white"><h3><i class="bi bi-info-circle"></i> Info</h3></div>
        <div class="card-body">
          <p><i class="bi bi-clock"></i> Open: 11:00 AM - 10:00 PM</p>
          <p><i class="bi bi-person"></i> Max 8 guests per table</p>
          <p><i class="bi bi-check-circle"></i> <?= count($tables) ?> tables available</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Reservation List -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h3><i class="bi bi-list-check"></i> <?= $is_guest ? 'Your' : 'Your' ?> Reservations</h3>
        </div>
        <div class="card-body">
          <?php if(empty($reservation_list)): ?>
            <p class="text-center">No reservations yet. Book your first table above.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
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
                      <td><span class="badge bg-<?= $res['status']=='cancelled'?'danger':'success' ?>"><?= ucfirst($res['status']) ?></span></td>
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
  <div class="container">&copy; 2026 Little Lemon Restaurant</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>