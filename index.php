<?php
session_start();
require 'db.php';

$foods = $conn->query(
    "SELECT * FROM foods ORDER BY food_name"
);

$foodList = [];
while ($food = $foods->fetch_assoc()) {
    $foodList[] = $food;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $quantities = $_POST['quantity'] ?? [];

    if (
        $name === '' ||
        !preg_match("/^[\p{L}\p{M} .'-]+$/u", $name) ||
        !is_array($quantities)
    ) {
        $message = 'Please enter a valid customer name.';
    } else {
        $items = [];
        $total = 0;

        foreach ($quantities as $id => $qty) {
            if (!ctype_digit((string)$id) ||
                !ctype_digit((string)$qty)) {
                continue;
            }

            $id = (int)$id;
            $qty = (int)$qty;

            if ($qty < 1 || $qty > 20) {
                continue;
            }

            $stmt = $conn->prepare(
                "SELECT id, price FROM foods WHERE id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $food = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($food) {
                $subtotal = (float)$food['price'] * $qty;
                $items[] = [
                    'id' => $id,
                    'qty' => $qty,
                    'price' => (float)$food['price']
                ];
                $total += $subtotal;
            }
        }

        if (!$items) {
            $message = 'Please select at least one food item.';
        } else {
            try {
                $conn->begin_transaction();

                $stmt = $conn->prepare(
                    "INSERT INTO orders (customer_name, total)
                     VALUES (?, ?)"
                );
                $stmt->bind_param("sd", $name, $total);
                $stmt->execute();
                $orderId = $conn->insert_id;
                $stmt->close();

                $stmt = $conn->prepare(
                    "INSERT INTO order_items
                     (order_id, food_id, quantity, price)
                     VALUES (?, ?, ?, ?)"
                );

                foreach ($items as $item) {
                    $stmt->bind_param(
                        "iiid",
                        $orderId,
                        $item['id'],
                        $item['qty'],
                        $item['price']
                    );
                    $stmt->execute();
                }

                $stmt->close();
                $conn->commit();

                header("Location: orders.php?success=1");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $message = 'Unable to save order. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Food Ordering System</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 25px;
}
.container {
    max-width: 800px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0001;
}
h1 { text-align: center; color: #20734a; }
input, select, button {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
input[type=text] { width: 95%; margin: 10px 0 20px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 8px; border-bottom: 1px solid #ddd; }
th { background: #20734a; color: white; }
select { width: 75px; }
button {
    width: 100%;
    background: #20734a;
    color: white;
    border: none;
    padding: 13px;
    margin-top: 20px;
    cursor: pointer;
}
.message { color: #b42318; }
.total { text-align: right; color: #20734a; }
a { color: #20734a; }
</style>
</head>
<body>
<div class="container">
<h1>Food Ordering System</h1>
<p>Enter your name and select the food you want.</p>

<?php if ($message): ?>
<p class="message"><?=
