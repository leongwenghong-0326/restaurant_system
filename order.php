<?php
session_start();
include 'db.php';

$is_guest = !isset($_SESSION['user_id']);
$user_id = $is_guest ? null : $_SESSION['user_id'];
$guest_session = $is_guest ? session_id() : null;

$error = "";
$success = "";

// Get reservation ID
$reservation_id = $_GET['reservation_id'] ?? null;
if (!$reservation_id) {
    die("Reservation not specified.");
}

// Handle adding new food items
if (isset($_POST['add_order'])) {
    $menu_id = $_POST['menu_id'];
    $quantity = (int)$_POST['quantity'];
    $special_instructions = trim($_POST['special_instructions']);

    if (!$menu_id || $quantity < 1) {
        $error = "Please select a menu item and quantity.";
    } else {
        // Get menu price
        $menu_stmt = $conn->prepare("SELECT price FROM menu WHERE menu_id=?");
        $menu_stmt->execute([$menu_id]);
        $menu = $menu_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$menu) {
            $error = "Menu item not found.";
        } else {
            $total_price = $menu['price'] * $quantity;

            $stmt = $conn->prepare("
                INSERT INTO orders (reservation_id, menu_id, quantity, total_price, special_instructions)
                VALUES (?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$reservation_id, $menu_id, $quantity, $total_price, $special_instructions])) {
                $success = "Order added successfully!";
            } else {
                $error = "Failed to add order.";
            }
        }
    }
}

// Handle editing an order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $quantity = (int)$_POST['quantity'];
    $special_instructions = trim($_POST['special_instructions']);

    if ($quantity < 1) {
        $error = "Quantity must be at least 1.";
    } else {
        // Get menu price for calculation
        $menu_stmt = $conn->prepare("
            SELECT m.price 
            FROM orders o 
            JOIN menu m ON o.menu_id = m.menu_id 
            WHERE o.order_id=?
        ");
        $menu_stmt->execute([$order_id]);
        $menu = $menu_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$menu) {
            $error = "Order not found.";
        } else {
            $total_price = $menu['price'] * $quantity;
            $stmt = $conn->prepare("
                UPDATE orders 
                SET quantity=?, total_price=?, special_instructions=? 
                WHERE order_id=?
            ");
            if ($stmt->execute([$quantity, $total_price, $special_instructions, $order_id])) {
                $success = "Order updated successfully!";
            } else {
                $error = "Failed to update order.";
            }
        }
    }
}

// Handle canceling an order
if (isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE order_id=?");
    if ($stmt->execute([$order_id])) {
        $success = "Order cancelled successfully!";
    } else {
        $error = "Failed to cancel order.";
    }
}

// Fetch all orders for this reservation
try {
    $orders_stmt = $conn->prepare("
        SELECT o.*, m.name AS menu_name, m.price AS unit_price 
        FROM orders o 
        JOIN menu m ON o.menu_id = m.menu_id 
        WHERE o.reservation_id=?
        ORDER BY o.created_at DESC
    ");
    $orders_stmt->execute([$reservation_id]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
    $error = "Failed to load orders.";
}

// Fetch all menu items
$menu_items = $conn->query("SELECT * FROM menu ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Food - Little Lemon</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3><i class="bi bi-cart"></i> Orders for Reservation #<?= htmlspecialchars($reservation_id) ?></h3>

    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

    <!-- Add new order -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Add Food Item</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Menu Item</label>
                    <select name="menu_id" class="form-select" required>
                        <option value="">Select Menu</option>
                        <?php foreach($menu_items as $menu): ?>
                            <option value="<?= $menu['menu_id'] ?>"><?= htmlspecialchars($menu['name']) ?> (RM<?= $menu['price'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" value="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Special Instructions</label>
                    <input type="text" name="special_instructions" class="form-control" placeholder="Optional">
                </div>
                <div class="col-12">
                    <button type="submit" name="add_order" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add to Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">Your Orders</div>
        <div class="card-body">
            <?php if(empty($orders)): ?>
                <p>No orders yet. Add some food items above.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Food Item</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Instructions</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o['menu_name']) ?></td>
                                    <td>RM<?= number_format($o['unit_price'],2) ?></td>
                                    <td><?= $o['quantity'] ?></td>
                                    <td>RM<?= number_format($o['total_price'],2) ?></td>
                                    <td><?= htmlspecialchars($o['special_instructions']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $o['status']=='cancelled'?'danger':'success' ?>"><?= ucfirst($o['status']) ?></span>
                                    </td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $o['order_id'] ?>"><i class="bi bi-pencil"></i></button>
                                        <!-- Cancel Form -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                            <button type="submit" name="cancel_order" class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $o['order_id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $o['order_id'] ?>" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h5 class="modal-title">Edit Order: <?= htmlspecialchars($o['menu_name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                            <div class="mb-3">
                                                <label>Quantity</label>
                                                <input type="number" name="quantity" class="form-control" min="1" value="<?= $o['quantity'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label>Special Instructions</label>
                                                <input type="text" name="special_instructions" class="form-control" value="<?= htmlspecialchars($o['special_instructions']) ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="edit_order" class="btn btn-warning">Save Changes</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>