
<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $conn->prepare(
            "UPDATE orders SET status = 'Cancelled'
             WHERE id = ? AND status = 'Pending'"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: orders.php");
    exit;
}

$sql = "SELECT o.id, o.customer_name, o.order_date,
               o.total, o.status,
               GROUP_CONCAT(
                   CONCAT(f.food_name, ' x', oi.quantity)
                   ORDER BY f.food_name SEPARATOR ', '
               ) AS items
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN foods f ON oi.food_id = f.id
        GROUP BY o.id, o.customer_name, o.order_date,
                 o.total, o.status
        ORDER BY o.order_date DESC";

$orders = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders List</title>
<style>
body { font-family: Arial; background: #f4f6f8; padding: 20px; }
.container { background: white; padding: 20px; border-radius: 10px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border-bottom: 1px solid #ddd; padding: 12px; text-align: left; }
th { background: #20734a; color: white; }
button { background: #c62828; color: white; border: none; padding: 8px; cursor: pointer; }
a { color: #20734a; }
</style>
</head>
<body>
<div class="container">
<h1>Orders List</h1>

<?php if (isset($_GET['success'])): ?>
<p>Order successfully placed!</p>
<?php endif; ?>

<a href="index.php">+ Create New Order</a>

<table>
<thead>
<tr>
<th>Order ID</th>
<th>Customer</th>
<th>Food Items</th>
<th>Total</th>
<th>Date</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php while ($row = $orders->fetch_assoc()): ?>
<tr>
<td><?= (int)$row['id'] ?></td>
<td><?= htmlspecialchars($row['customer_name']) ?></td>
<td><?= htmlspecialchars($row['items']) ?></td>
<td>₱<?= number_format($row['total'], 2) ?></td>
<td><?= htmlspecialchars($row['order_date']) ?></td>
<td><?= htmlspecialchars($row['status']) ?></td>
<td>
<?php if ($row['status'] === 'Pending'): ?>
<form method="POST"
      onsubmit="return confirm('Cancel this order?')">
<input type="hidden" name="order_id"
       value="<?= (int)$row['id'] ?>">
<button type="submit">Cancel</button>
</form>
<?php else: ?>
—
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body>
</html>
