<?php
require_once 'db.php';
requireAdmin();
$message = '';
$activeTab = 'dashboard';
$validTabs = ['dashboard', 'product-form', 'inventory', 'menu-order', 'slots', 'about-us', 'recent-orders'];
if (isset($_GET['tab']) && in_array($_GET['tab'], $validTabs, true)) {
    $activeTab = $_GET['tab'];
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] === 'orders_count') {
        $result = $mysqli->query("SELECT COUNT(*) AS total, MAX(id) AS last_id FROM orders");
        $row = $result ? $result->fetch_assoc() : ['total' => 0, 'last_id' => 0];
        echo json_encode([
            'total' => intval($row['total'] ?? 0),
            'last_id' => intval($row['last_id'] ?? 0)
        ]);
        exit;
    }
    if ($_GET['ajax'] === 'save_menu_order') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $order = isset($payload['order']) && is_array($payload['order']) ? $payload['order'] : [];
        $position = 1;
        $stmt = $mysqli->prepare('UPDATE products SET sort_order = ? WHERE id = ?');
        if ($stmt) {
            foreach ($order as $productId) {
                $productId = intval($productId);
                $stmt->bind_param('ii', $position, $productId);
                $stmt->execute();
                $position++;
            }
            $stmt->close();
        }
        echo json_encode(['ok' => true]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);
        $category = sanitize($_POST['category'] ?? 'Coffee');

        if ($name && $description && $price > 0) {
            $stmt = $mysqli->prepare('INSERT INTO products (name, description, price, image_url, stock, category) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssdiss', $name, $description, $price, $image_url, $stock, $category);
            $stmt->execute();
            $stmt->close();
            $message = 'Product added successfully.';
        } else {
            $message = 'Please fill in all required product information.';
        }
        $activeTab = 'product-form';
    }
    if (isset($_POST['update_product']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);
        $category = sanitize($_POST['category'] ?? 'Coffee');

        if ($name && $description && $price > 0) {
            $stmt = $mysqli->prepare('UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, stock = ?, category = ? WHERE id = ?');
            $stmt->bind_param('ssdissi', $name, $description, $price, $image_url, $stock, $category, $product_id);
            $stmt->execute();
            $stmt->close();
            $message = 'Product updated successfully.';
            $activeTab = 'inventory';
        } else {
            $message = 'Please fill in all required product information.';
        }
    }
    if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $stmt = $mysqli->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Product removed successfully.';
        $activeTab = 'inventory';
    }
    if (isset($_POST['add_slot']) || (isset($_POST['update_slot']) && isset($_POST['slot_id']))) {
        $slot_name = sanitize($_POST['slot_name'] ?? '');
        if ($slot_name) {
            if (isset($_POST['update_slot'])) {
                $slot_id = intval($_POST['slot_id']);
                $stmt = $mysqli->prepare('UPDATE slots SET name = ? WHERE id = ?');
                $stmt->bind_param('si', $slot_name, $slot_id);
                $stmt->execute();
                $stmt->close();
                $message = 'Slot updated successfully.';
                $activeTab = 'slots';
            } else {
                $stmt = $mysqli->prepare('INSERT IGNORE INTO slots (name) VALUES (?)');
                $stmt->bind_param('s', $slot_name);
                $stmt->execute();
                $stmt->close();
                $message = 'Slot added successfully.';
                $activeTab = 'slots';
            }
        } else {
            $message = 'Please enter a slot name.';
        }
    }
    if (isset($_POST['delete_slot']) && isset($_POST['slot_id'])) {
        $slot_id = intval($_POST['slot_id']);
        $stmt = $mysqli->prepare('DELETE FROM slots WHERE id = ?');
        $stmt->bind_param('i', $slot_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Slot deleted successfully.';
        $activeTab = 'slots';
    }
    if (isset($_POST['save_about_us'])) {
        $owner_name = sanitize($_POST['owner_name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $contact_number = sanitize($_POST['contact_number'] ?? '');
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $instagram_url = trim($_POST['instagram_url'] ?? '');
        $tiktok_url = trim($_POST['tiktok_url'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if ($owner_name && $address) {
            saveSiteInfo($owner_name, $address, $email, $contact_number, $facebook_url, $instagram_url, $tiktok_url, $description);
            $message = 'About Us information saved successfully.';
            $activeTab = 'about-us';
        } else {
            $message = 'Please provide an owner name and address.';
        }
    }
    if (isset($_POST['complete_order']) && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $mysqli->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $status = 'Completed';
        $stmt->bind_param('si', $status, $order_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Order marked as completed.';
        $activeTab = 'recent-orders';
    }
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $stmt = $mysqli->prepare('SELECT status FROM orders WHERE id = ?');
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();

        if ($order && $order['status'] === 'Completed') {
            $stmt = $mysqli->prepare('DELETE FROM order_items WHERE order_id = ?');
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM orders WHERE id = ?');
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $stmt->close();

            $message = 'Completed order deleted successfully.';
        } else {
            $message = 'Only completed orders can be deleted.';
        }
    }
}

$editProduct = null;
$isEditing = false;
if (isset($_GET['edit_product_id'])) {
    $edit_id = intval($_GET['edit_product_id']);
    $stmt = $mysqli->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editProduct = $result->fetch_assoc();
    $stmt->close();
    $isEditing = $editProduct !== null;
}

$editSlot = null;
$isSlotEditing = false;
if (isset($_GET['edit_slot_id'])) {
    $edit_slot_id = intval($_GET['edit_slot_id']);
    $stmt = $mysqli->prepare('SELECT * FROM slots WHERE id = ?');
    $stmt->bind_param('i', $edit_slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editSlot = $result->fetch_assoc();
    $stmt->close();
    $isSlotEditing = $editSlot !== null;
}

$products = $mysqli->query('SELECT * FROM products ORDER BY sort_order ASC, id ASC');
$menu_products = $mysqli->query('SELECT id, name, price, category, image_url FROM products ORDER BY sort_order ASC, id ASC');
$slots = $mysqli->query('SELECT * FROM slots ORDER BY id DESC');
$orders = $mysqli->query('SELECT o.id, o.total_amount, o.status, o.slot_time, o.created_at, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 20');
$site_info = getSiteInfo();

$ordersToday = 0;
$salesToday = 0;
$pendingOrders = 0;
$topProductName = 'No orders yet';

$result = $mysqli->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = CURDATE()");
if ($result && $row = $result->fetch_assoc()) {
    $ordersToday = intval($row['total']);
}
$result = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE DATE(created_at) = CURDATE()");
if ($result && $row = $result->fetch_assoc()) {
    $salesToday = floatval($row['total']);
}
$result = $mysqli->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'Pending'");
if ($result && $row = $result->fetch_assoc()) {
    $pendingOrders = intval($row['total']);
}
$result = $mysqli->query("SELECT p.name, SUM(oi.quantity) AS qty FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY qty DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $topProductName = $row['name'];
}

$chartLabels = [];
$chartValues = [];
$chartData = [];
$result = $mysqli->query("SELECT DATE(created_at) AS order_date, SUM(total_amount) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY order_date ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $chartData[$row['order_date']] = floatval($row['total']);
    }
}
for ($i = 6; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = $dateKey;
    $chartValues[] = $chartData[$dateKey] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                <a class="btn btn-secondary nav-button" href="logout.php">Logout</a>
            </div>
        </div>
    </header>
    <main class="container section admin-page">
        <div class="section-header">
            <h1>Admin Panel</h1>
            <p>Manage coffee products, review recent orders, and monitor slot schedules.</p>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <div class="admin-tabs">
            <button class="admin-tab-button <?= $activeTab === 'dashboard' ? 'active' : '' ?>" data-tab="dashboard">Dashboard</button>
            <button class="admin-tab-button <?= $activeTab === 'product-form' ? 'active' : '' ?>" data-tab="product-form">Add New Product</button>
            <button class="admin-tab-button <?= $activeTab === 'inventory' ? 'active' : '' ?>" data-tab="inventory">Product Inventory</button>
            <button class="admin-tab-button <?= $activeTab === 'menu-order' ? 'active' : '' ?>" data-tab="menu-order">Menu Order</button>
            <button class="admin-tab-button <?= $activeTab === 'slots' ? 'active' : '' ?>" data-tab="slots">Manage Slots</button>
            <button class="admin-tab-button <?= $activeTab === 'about-us' ? 'active' : '' ?>" data-tab="about-us">About Us</button>
            <button class="admin-tab-button <?= $activeTab === 'recent-orders' ? 'active' : '' ?>" data-tab="recent-orders">Recent Orders</button>
        </div>

        <section class="admin-panel-section <?= $activeTab === 'dashboard' ? 'active' : '' ?>" data-section="dashboard">
            <div class="admin-card admin-card-section">
                <div class="dashboard-header">
                    <div>
                        <h2>Daily Dashboard</h2>
                        <p class="muted">Live snapshot of sales, popular drinks, and order flow.</p>
                    </div>
                    <div class="dashboard-pulse">
                        <span class="pulse-dot"></span>
                        Live orders
                    </div>
                </div>
                <div class="dashboard-toggle">
                    <label class="wallet-toggle">
                        <input type="checkbox" id="soundToggle" checked>
                        <span>Sound effects</span>
                    </label>
                </div>
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <h3>Orders Today</h3>
                        <p class="dashboard-number"><?= $ordersToday ?></p>
                    </div>
                    <div class="dashboard-card">
                        <h3>Sales Today</h3>
                        <p class="dashboard-number"><?= formatPeso($salesToday) ?></p>
                    </div>
                    <div class="dashboard-card">
                        <h3>Pending Queue</h3>
                        <p class="dashboard-number"><?= $pendingOrders ?></p>
                    </div>
                    <div class="dashboard-card">
                        <h3>Top Drink</h3>
                        <p class="dashboard-number"><?= sanitize($topProductName) ?></p>
                    </div>
                </div>
                <div class="dashboard-chart">
                    <canvas id="salesChart" height="140"
                        data-labels='<?= json_encode($chartLabels) ?>'
                        data-values='<?= json_encode($chartValues) ?>'></canvas>
                </div>
                <div class="dashboard-grid">
                    <div class="dashboard-panel">
                        <h3>QR Ordering</h3>
                        <p class="muted">Generate a table QR code for quick ordering.</p>
                        <div class="qr-controls">
                            <input type="text" id="qrTableId" placeholder="Table 5">
                            <button class="btn btn-secondary" type="button" id="generateQr">Generate QR</button>
                        </div>
                        <div class="qr-preview">
                            <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=Coffee%20Ordering" alt="QR preview">
                        </div>
                    </div>
                    <div class="dashboard-panel">
                        <h3>Queue Monitor</h3>
                        <p class="muted">Share queue numbers with customers in-store.</p>
                        <div class="queue-list" id="queueList">
                            <div class="queue-item">Next in line: Pending orders appear here.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="admin-panel-section <?= $activeTab === 'product-form' ? 'active' : '' ?>" data-section="product-form">
            <div class="admin-card admin-card-section">
                <h2><?= $isEditing ? 'Edit Product' : 'Add New Product' ?></h2>
                <form method="post" action="admin.php">
                    <?php if ($isEditing): ?>
                        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                    <?php endif; ?>
                    <label>Name</label>
                    <input type="text" name="name" value="<?= $isEditing ? sanitize($editProduct['name']) : '' ?>" required>
                    <label>Description</label>
                    <textarea name="description" rows="3" required><?= $isEditing ? sanitize($editProduct['description']) : '' ?></textarea>
                    <label>Price (₱)</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= $isEditing ? sanitize($editProduct['price']) : '' ?>" required>
                    <label>Product Image URL</label>
                    <input type="url" id="productImageLink" name="image_url" placeholder="https://example.com/image.jpg" value="<?= $isEditing ? sanitize(html_entity_decode($editProduct['image_url'])) : '' ?>">
                    <small class="muted">Paste the direct image URL here. When you save the product, this URL becomes the image shown in the product catalog.</small>
                    <div class="product-image-preview">
                        <img id="productImagePreview" src="<?= $isEditing ? getProductImageUrl($editProduct['image_url']) : 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80' ?>" alt="Product image preview" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80'">
                    </div>
                    <label>Stock</label>
                    <input type="number" name="stock" min="0" value="<?= $isEditing ? sanitize($editProduct['stock']) : 10 ?>">
                    <label>Category</label>
                    <input type="text" name="category" value="<?= $isEditing ? sanitize($editProduct['category']) : 'Coffee' ?>">
                    <div class="admin-form-actions">
                        <button class="btn btn-primary" type="submit" name="<?= $isEditing ? 'update_product' : 'add_product' ?>"><?= $isEditing ? 'Save Changes' : 'Save Product' ?></button>
                        <?php if ($isEditing): ?><a class="btn btn-secondary" href="admin.php">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="admin-panel-section <?= $activeTab === 'inventory' ? 'active' : '' ?>" data-section="inventory">
            <div class="admin-card admin-card-section">
                <h2>Product Inventory</h2>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= sanitize($product['name']) ?></td>
                                <td><?= formatPeso($product['price']) ?></td>
                                <td><?= $product['stock'] ?></td>
                                <td>
                                    <a class="btn btn-secondary" href="admin.php?edit_product_id=<?= $product['id'] ?>">Update</a>
                                    <form method="post" action="admin.php" onsubmit="return confirm('Remove product?');" style="display:inline-block; margin:0 0 0 8px;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button class="btn btn-secondary" type="submit" name="delete_product">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel-section <?= $activeTab === 'menu-order' ? 'active' : '' ?>" data-section="menu-order">
            <div class="admin-card admin-card-section">
                <h2>Drag & Drop Menu Editor</h2>
                <p class="muted">Rearrange the products to change how the menu appears for customers.</p>
                <ul class="menu-order-list" id="menuOrderList">
                    <?php while ($product = $menu_products->fetch_assoc()): ?>
                        <li class="menu-order-item" draggable="true" data-product-id="<?= $product['id'] ?>">
                            <span class="menu-handle">::</span>
                            <img src="<?= getProductImageUrl($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>">
                            <div>
                                <strong><?= sanitize($product['name']) ?></strong>
                                <div class="muted"><?= sanitize($product['category']) ?> · <?= formatPeso($product['price']) ?></div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <div class="admin-form-actions">
                    <button class="btn btn-primary" type="button" id="saveMenuOrder">Save Menu Order</button>
                </div>
                <div class="alert alert-info" id="menuOrderStatus">Drag items then click save to update the menu order.</div>
            </div>
        </section>

        <section class="admin-panel-section <?= $activeTab === 'slots' ? 'active' : '' ?>" data-section="slots">
            <div class="admin-card admin-card-section">
                <h2><?= $isSlotEditing ? 'Edit Slot' : 'Manage Slots' ?></h2>
                <form method="post" action="admin.php">
                    <?php if ($isSlotEditing): ?>
                        <input type="hidden" name="slot_id" value="<?= $editSlot['id'] ?>">
                    <?php endif; ?>
                    <label>Slot Name</label>
                    <input type="text" name="slot_name" value="<?= $isSlotEditing ? sanitize($editSlot['name']) : '' ?>" placeholder="Morning 8:00 - 10:00" required>
                    <div class="admin-form-actions">
                        <button class="btn btn-primary" type="submit" name="<?= $isSlotEditing ? 'update_slot' : 'add_slot' ?>"><?= $isSlotEditing ? 'Save Slot' : 'Add Slot' ?></button>
                        <?php if ($isSlotEditing): ?><a class="btn btn-secondary" href="admin.php#slots">Cancel</a><?php endif; ?>
                    </div>
                </form>
                <table class="admin-table full-width" style="margin-top:1.5rem;">
                    <thead>
                        <tr><th>ID</th><th>Slot Name</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($slot = $slots->fetch_assoc()): ?>
                            <tr>
                                <td><?= $slot['id'] ?></td>
                                <td><?= sanitize($slot['name']) ?></td>
                                <td><?= date('Y-m-d', strtotime($slot['created_at'])) ?></td>
                                <td>
                                    <a class="btn btn-secondary" href="admin.php?edit_slot_id=<?= $slot['id'] ?>#slots">Update</a>
                                    <form method="post" action="admin.php" onsubmit="return confirm('Delete this slot?');" style="display:inline-block; margin:0 0 0 8px;">
                                        <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                                        <button class="btn btn-secondary" type="submit" name="delete_slot">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel-section <?= $activeTab === 'about-us' ? 'active' : '' ?>" data-section="about-us">
            <div class="admin-card admin-card-section">
                <h2>About Us</h2>
                <form method="post" action="admin.php">
                    <label>Owner Name</label>
                    <input type="text" name="owner_name" value="<?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?>" required>
                    <label>Address</label>
                    <input type="text" name="address" value="<?= sanitize($site_info['address']) ?>" required>
                    <label>Email</label>
                    <input type="email" name="email" value="<?= sanitize($site_info['email'] ?? '') ?>" placeholder="owner@example.com">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= sanitize($site_info['contact_number'] ?? '') ?>" placeholder="+63 912 345 6789">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" value="<?= sanitize($site_info['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage">
                    <label>Instagram URL</label>
                    <input type="url" name="instagram_url" value="<?= sanitize($site_info['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/yourpage">
                    <label>TikTok URL</label>
                    <input type="url" name="tiktok_url" value="<?= sanitize($site_info['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@yourpage">
                    <label>About Description</label>
                    <textarea name="description" rows="4"><?= sanitize($site_info['description']) ?></textarea>
                    <div class="admin-form-actions">
                        <button class="btn btn-primary" type="submit" name="save_about_us">Save About Us</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="admin-panel-section <?= $activeTab === 'recent-orders' ? 'active' : '' ?>" data-section="recent-orders">
            <div class="admin-card admin-card-section">
                <h2>Recent Orders</h2>
                <p class="muted">Review recent orders and their assigned slot times.</p>
                <table class="admin-table full-width">
                    <thead>
                        <tr><th>Order</th><th>Customer</th><th>Total</th><th>Slot</th><th>Status</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= sanitize($order['customer_name']) ?></td>
                                <td><?= formatPeso($order['total_amount']) ?></td>
                                <td><?= sanitize($order['slot_time']) ?></td>
                                <td><?= sanitize($order['status']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <?php if ($order['status'] !== 'Completed'): ?>
                                        <form method="post" action="admin.php" style="display:inline-block; margin:0 8px 0 0;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button class="btn btn-primary" type="submit" name="complete_order">Mark Completed</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="admin.php" style="display:inline-block; margin:0 8px 0 0;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button class="btn btn-danger" type="submit" name="delete_order" onclick="return confirm('Delete this completed order and receipt?');">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                    <button class="btn btn-secondary btn-view-receipt" type="button" data-order-id="<?= $order['id'] ?>">View Receipt</button>
                                    <noscript>
                                        <a class="btn btn-secondary" href="receipt.php?order_id=<?= $order['id'] ?>">View Receipt</a>
                                    </noscript>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Coffee System Admin.</p>
        </div>
    </footer>
    <div id="receiptModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-dialog">
            <button class="modal-close" type="button" aria-label="Close receipt">&times;</button>
            <div class="modal-body">
                <div class="alert alert-info">Open a receipt from recent orders to review it here.</div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js"></script>
</body>
</html>
