<?php
require_once 'db.php';
$cart_count = getCartCount();
$site_info = getSiteInfo();
$message = '';
$availableSlots = getSlotOptions();
$milkOptions = ['Regular', 'Soy', 'Almond', 'Oat'];
$sugarOptions = ['0%', '25%', '50%', '100%'];
$iceOptions = ['0%', '25%', '50%', '100%'];
$addonOptions = ['Pearls', 'Syrup', 'Cinnamon', 'Whipped Cream'];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['customizations'])) {
    $_SESSION['customizations'] = [];
}

function buildCustomizationSummary($customization) {
    if (!is_array($customization)) {
        return '';
    }
    $parts = [];
    if (!empty($customization['milk'])) {
        $parts[] = 'Milk: ' . $customization['milk'];
    }
    if (!empty($customization['sugar'])) {
        $parts[] = 'Sugar: ' . $customization['sugar'];
    }
    if (!empty($customization['ice'])) {
        $parts[] = 'Ice: ' . $customization['ice'];
    }
    if (!empty($customization['addons'])) {
        $addons = is_array($customization['addons']) ? $customization['addons'] : [];
        if (!empty($addons)) {
            $parts[] = 'Add-ons: ' . implode(', ', $addons);
        }
    }
    return implode(' | ', $parts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reorder_last'])) {
        if (!isLoggedIn()) {
            header('Location: login.php?redirect=cart.php');
            exit;
        }
        $stmt = $mysqli->prepare('SELECT id FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $lastOrder = $result->fetch_assoc();
        $stmt->close();

        if ($lastOrder) {
            $stmt = $mysqli->prepare('SELECT product_id, quantity, customizations FROM order_items WHERE order_id = ?');
            $stmt->bind_param('i', $lastOrder['id']);
            $stmt->execute();
            $itemsResult = $stmt->get_result();
            $_SESSION['cart'] = [];
            $_SESSION['customizations'] = [];
            while ($row = $itemsResult->fetch_assoc()) {
                $productId = intval($row['product_id']);
                $qty = max(1, intval($row['quantity']));
                $_SESSION['cart'][$productId] = $qty;
                if (!empty($row['customizations'])) {
                    $decoded = json_decode($row['customizations'], true);
                    if (is_array($decoded)) {
                        $_SESSION['customizations'][$productId] = $decoded;
                    }
                }
            }
            $stmt->close();
            $message = 'Last order loaded into your cart.';
        } else {
            $message = 'No previous orders found to reorder.';
        }
    }

    if (isset($_POST['update']) && isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = max(0, intval($quantity));
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
                unset($_SESSION['customizations'][$product_id]);
            }
        }
        if (isset($_POST['customizations']) && is_array($_POST['customizations'])) {
            foreach ($_POST['customizations'] as $product_id => $customization) {
                $product_id = intval($product_id);
                if (!isset($_SESSION['cart'][$product_id])) {
                    continue;
                }
                $milk = in_array($customization['milk'] ?? '', $milkOptions, true) ? $customization['milk'] : 'Regular';
                $sugar = in_array($customization['sugar'] ?? '', $sugarOptions, true) ? $customization['sugar'] : '50%';
                $ice = in_array($customization['ice'] ?? '', $iceOptions, true) ? $customization['ice'] : '50%';
                $addonsRaw = $customization['addons'] ?? [];
                $addons = [];
                if (is_array($addonsRaw)) {
                    foreach ($addonsRaw as $addon) {
                        if (in_array($addon, $addonOptions, true)) {
                            $addons[] = $addon;
                        }
                    }
                }
                $_SESSION['customizations'][$product_id] = [
                    'milk' => $milk,
                    'sugar' => $sugar,
                    'ice' => $ice,
                    'addons' => $addons
                ];
            }
        }
        $message = 'Cart updated successfully.';
    }

    if (isset($_POST['checkout'])) {
        if (!isLoggedIn()) {
            header('Location: login.php?redirect=cart.php');
            exit;
        }

        $slot_time = sanitize($_POST['slot_time'] ?? 'Anytime');
        if (!in_array($slot_time, $availableSlots, true)) {
            $slot_time = 'Anytime';
        }

        if (isset($_POST['customizations']) && is_array($_POST['customizations'])) {
            foreach ($_POST['customizations'] as $product_id => $customization) {
                $product_id = intval($product_id);
                if (!isset($_SESSION['cart'][$product_id])) {
                    continue;
                }
                $milk = in_array($customization['milk'] ?? '', $milkOptions, true) ? $customization['milk'] : 'Regular';
                $sugar = in_array($customization['sugar'] ?? '', $sugarOptions, true) ? $customization['sugar'] : '50%';
                $ice = in_array($customization['ice'] ?? '', $iceOptions, true) ? $customization['ice'] : '50%';
                $addonsRaw = $customization['addons'] ?? [];
                $addons = [];
                if (is_array($addonsRaw)) {
                    foreach ($addonsRaw as $addon) {
                        if (in_array($addon, $addonOptions, true)) {
                            $addons[] = $addon;
                        }
                    }
                }
                $_SESSION['customizations'][$product_id] = [
                    'milk' => $milk,
                    'sugar' => $sugar,
                    'ice' => $ice,
                    'addons' => $addons
                ];
            }
        }

        $cart_items = $_SESSION['cart'];
        if (empty($cart_items)) {
            $message = 'Your cart is empty.';
        } else {
            $product_ids = array_keys($cart_items);
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $stmt = $mysqli->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
            $types = str_repeat('i', count($product_ids));
            $stmt->bind_param($types, ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();

            $total = 0;
            $ordered_products = [];
            while ($row = $result->fetch_assoc()) {
                $pid = $row['id'];
                $price = $row['price'];
                $qty = $cart_items[$pid] ?? 0;
                if ($qty > 0) {
                    $ordered_products[] = ['id' => $pid, 'quantity' => $qty, 'price' => $price];
                    $total += $price * $qty;
                }
            }
            $stmt->close();

            if (!empty($ordered_products)) {
                $stmt = $mysqli->prepare('INSERT INTO orders (user_id, total_amount, status, slot_time) VALUES (?, ?, ?, ?)');
                $status = 'Pending';
                $stmt->bind_param('idss', $_SESSION['user_id'], $total, $status, $slot_time);
                $stmt->execute();
                $order_id = $mysqli->insert_id;
                $stmt->close();

                $stmt = $mysqli->prepare('INSERT INTO order_items (order_id, product_id, quantity, price, customizations) VALUES (?, ?, ?, ?, ?)');
                foreach ($ordered_products as $item) {
                    $customization = $_SESSION['customizations'][$item['id']] ?? null;
                    $customizationJson = $customization ? json_encode($customization) : null;
                    $stmt->bind_param('iiids', $order_id, $item['id'], $item['quantity'], $item['price'], $customizationJson);
                    $stmt->execute();
                }
                $stmt->close();

                $_SESSION['cart'] = [];
                $_SESSION['customizations'] = [];
                header('Location: receipt.php?order_id=' . $order_id);
                exit;
            }
        }
    }
}

$cart_products = [];
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $mysqli->prepare("SELECT id, name, price, image_url FROM products WHERE id IN ($placeholders)");
    $types = str_repeat('i', count($product_ids));
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $cart_products[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-row">
            <a class="brand" href="index.php"><?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?></a>
            <nav class="site-nav nav-center">
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="cart.php">Cart (<?= $cart_count ?>)</a>
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
        <div class="section-header">
            <h1>Your Cart</h1>
            <p>Review your cart items, update quantities, or place an order.</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <form method="post" action="cart.php" class="reorder-form">
                <button class="btn btn-secondary" type="submit" name="reorder_last">Reorder Last Purchase</button>
            </form>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if (empty($cart_products)): ?>
            <div class="alert alert-info">Your cart is empty. <a href="products.php">Browse products</a> to get started.</div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Customize</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total_amount = 0; ?>
                        <?php foreach ($cart_products as $product): ?>
                            <?php
                                $customization = $_SESSION['customizations'][$product['id']] ?? [
                                    'milk' => 'Regular',
                                    'sugar' => '50%',
                                    'ice' => '50%',
                                    'addons' => []
                                ];
                                $summary = buildCustomizationSummary($customization);
                            ?>
                            <?php $subtotal = $product['price'] * $product['quantity']; ?>
                            <?php $total_amount += $subtotal; ?>
                            <tr>
                                <td class="cart-product">
                                    <img src="<?= getProductImageUrl($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80'">
                                    <div>
                                        <strong><?= sanitize($product['name']) ?></strong>
                                        <?php if ($summary): ?>
                                            <div class="customization-summary"><?= sanitize($summary) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= formatPeso($product['price']) ?></td>
                                <td>
                                    <input type="number" name="quantities[<?= $product['id'] ?>]" value="<?= $product['quantity'] ?>" min="0">
                                </td>
                                <td>
                                    <div class="customize-grid">
                                        <label>Milk</label>
                                        <select name="customizations[<?= $product['id'] ?>][milk]">
                                            <?php foreach ($milkOptions as $milk): ?>
                                                <option value="<?= $milk ?>" <?= $customization['milk'] === $milk ? 'selected' : '' ?>><?= $milk ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label>Sugar</label>
                                        <select name="customizations[<?= $product['id'] ?>][sugar]">
                                            <?php foreach ($sugarOptions as $sugar): ?>
                                                <option value="<?= $sugar ?>" <?= $customization['sugar'] === $sugar ? 'selected' : '' ?>><?= $sugar ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label>Ice</label>
                                        <select name="customizations[<?= $product['id'] ?>][ice]">
                                            <?php foreach ($iceOptions as $ice): ?>
                                                <option value="<?= $ice ?>" <?= $customization['ice'] === $ice ? 'selected' : '' ?>><?= $ice ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label>Add-ons</label>
                                        <div class="addon-options">
                                            <?php foreach ($addonOptions as $addon): ?>
                                                <?php $isChecked = in_array($addon, $customization['addons'], true); ?>
                                                <label>
                                                    <input type="checkbox" name="customizations[<?= $product['id'] ?>][addons][]" value="<?= $addon ?>" <?= $isChecked ? 'checked' : '' ?>>
                                                    <span><?= $addon ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= formatPeso($subtotal) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-slot">
                    <label for="slot_time">Preferred Time Slot</label>
                    <select id="slot_time" name="slot_time" required>
                        <?php foreach ($availableSlots as $slot): ?>
                            <option value="<?= sanitize($slot) ?>" <?= isset($_POST['slot_time']) && $_POST['slot_time'] === $slot ? 'selected' : '' ?>><?= sanitize($slot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wallet-panel" data-wallet>
                    <div>
                        <h3>GCash-style Wallet</h3>
                        <p class="muted">Use the demo wallet to simulate instant payments and smooth checkout.</p>
                    </div>
                    <div class="wallet-controls">
                        <div class="wallet-balance">Balance: <span id="walletBalance">₱0.00</span></div>
                        <div class="wallet-buttons">
                            <button class="btn btn-secondary" type="button" id="walletTopUp">Top Up ₱200</button>
                            <label class="wallet-toggle">
                                <input type="checkbox" id="walletUse" checked>
                                <span>Use Wallet</span>
                            </label>
                        </div>
                        <div class="wallet-note" id="walletNote"></div>
                    </div>
                </div>
                <div class="cart-actions">
                    <span class="cart-total" data-total="<?= $total_amount ?>">Total: <?= formatPeso($total_amount) ?></span>
                    <div>
                        <button class="btn btn-secondary" type="submit" name="update">Update Cart</button>
                        <button class="btn btn-primary js-checkout" type="submit" name="checkout">Confirm Order</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?>.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
