<?php
require_once 'db.php';
$site_info = getSiteInfo();
requireLogin();

$order_id = intval($_GET['order_id'] ?? 0);
$error = '';
$order = null;
$order_items = [];
$isModal = isset($_GET['modal']);

function formatCustomizationSummary($customizationJson) {
    if (empty($customizationJson)) {
        return '';
    }
    $decoded = json_decode($customizationJson, true);
    if (!is_array($decoded)) {
        return '';
    }
    $parts = [];
    if (!empty($decoded['milk'])) {
        $parts[] = 'Milk: ' . $decoded['milk'];
    }
    if (!empty($decoded['sugar'])) {
        $parts[] = 'Sugar: ' . $decoded['sugar'];
    }
    if (!empty($decoded['ice'])) {
        $parts[] = 'Ice: ' . $decoded['ice'];
    }
    if (!empty($decoded['addons']) && is_array($decoded['addons'])) {
        $parts[] = 'Add-ons: ' . implode(', ', $decoded['addons']);
    }
    return implode(' | ', $parts);
}

if ($order_id <= 0) {
    $error = 'Order receipt could not be loaded. Invalid order ID.';
} else {
    if (isAdmin()) {
        $stmt = $mysqli->prepare(
            'SELECT o.id, o.total_amount, o.status, o.slot_time, o.created_at, u.name AS customer_name, u.email AS customer_email
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?'
        );
        $stmt->bind_param('i', $order_id);
    } else {
        $stmt = $mysqli->prepare(
            'SELECT o.id, o.total_amount, o.status, o.slot_time, o.created_at, u.name AS customer_name, u.email AS customer_email
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ? AND o.user_id = ?'
        );
        $stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
    }
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $error = 'Order not found or access denied.';
    } else {
        $stmt = $mysqli->prepare(
            'SELECT oi.quantity, oi.price, oi.customizations, p.name
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?'
        );
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        while ($row = $items_result->fetch_assoc()) {
            $row['customization_summary'] = formatCustomizationSummary($row['customizations'] ?? '');
            $order_items[] = $row;
        }
        $stmt->close();
    }
}

$lastOrderPayload = [];
if (!empty($order_items) && $order) {
    foreach ($order_items as $item) {
        $lastOrderPayload[] = [
            'name' => $item['name'],
            'quantity' => intval($item['quantity']),
            'customization' => $item['customization_summary'] ?? ''
        ];
    }
}
?>
<?php if (!$isModal): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-row">
            <a class="brand" href="index.php"><?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?></a>
            <nav class="site-nav nav-center">
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="cart.php">Cart (<?= getCartCount() ?>)</a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
            </nav>
            <div class="nav-right">
                <div class="theme-switcher" data-theme-switcher>
                    <button class="btn btn-secondary theme-button" type="button" aria-haspopup="true" aria-expanded="false">Theme</button>
                    <div class="theme-menu">
                        <button type="button" data-theme="auto">Auto</button>
                        <button type="button" data-theme="light">Light</button>
                        <button type="button" data-theme="dark">Dark</button>
                    </div>
                </div>
                <?php if (isLoggedIn()): ?>
                    <a class="btn btn-secondary nav-button" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-secondary nav-button" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container section">
<?php endif; ?>
        <div class="section-header">
            <h1>Order Receipt</h1>
            <p>Thanks for your order. Here is your receipt and order summary.</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitize($error) ?></div>
        <?php else: ?>
            <div class="receipt-card card" data-last-order='<?= htmlspecialchars(json_encode($lastOrderPayload), ENT_QUOTES, 'UTF-8') ?>'>
                <div class="receipt-header">
                    <h2>Order #<?= $order['id'] ?></h2>
                    <span class="badge badge-status"><?= sanitize($order['status']) ?></span>
                </div>
            <div class="receipt-meta">
                <div>
                    <strong>Customer:</strong>
                    <p><?= sanitize($order['customer_name']) ?> <br> <?= sanitize($order['customer_email']) ?></p>
                </div>
                <div>
                    <strong>Slot Time:</strong>
                    <p><?= sanitize($order['slot_time'] ?? 'Anytime') ?></p>
                </div>
                <div>
                    <strong>Order Date:</strong>
                    <p><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></p>
                </div>
            </div>
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <?php $subtotal = $item['price'] * $item['quantity']; ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($item['name']) ?></strong>
                                <?php if (!empty($item['customization_summary'])): ?>
                                    <div class="customization-summary"><?= sanitize($item['customization_summary']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= intval($item['quantity']) ?></td>
                            <td><?= formatPeso($item['price']) ?></td>
                            <td><?= formatPeso($subtotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong><?= formatPeso($order['total_amount']) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <div class="receipt-actions">
                <a class="btn btn-secondary" href="products.php">Continue Shopping</a>
                <button class="btn btn-primary" onclick="window.print();">Print Receipt</button>
            </div>
        </div>
        <?php endif; ?>
    </main>
<?php if (!$isModal): ?>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Coffee System.</p>
        </div>
    </footer>
</body>
</html>
<?php endif; ?>
