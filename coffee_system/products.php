<?php
require_once 'db.php';
$cart_count = getCartCount();
$site_info = getSiteInfo();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;
    $message = 'Product added to cart successfully.';
    $cart_count = getCartCount();
}

$products = $mysqli->query('SELECT * FROM products ORDER BY sort_order ASC, name ASC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Products</title>
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
            <h1>Product Catalog</h1>
            <p>Choose from premium coffee products and add them to your cart.</p>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <div class="grid info-grid smart-grid">
            <div class="info-card smart-card">
                <h3>AI Coffee Recommendation</h3>
                <p class="muted">Pick your vibe and we will suggest something you will love.</p>
                <div class="smart-form" id="recommendationForm">
                    <label>Flavor</label>
                    <div class="pill-group">
                        <button type="button" class="pill active" data-value="sweet">Sweet</button>
                        <button type="button" class="pill" data-value="bitter">Bitter</button>
                    </div>
                    <label>Temperature</label>
                    <div class="pill-group">
                        <button type="button" class="pill active" data-value="iced">Iced</button>
                        <button type="button" class="pill" data-value="hot">Hot</button>
                    </div>
                    <button class="btn btn-primary" type="button" id="runRecommendation">Get Recommendation</button>
                    <div class="recommendation-result" id="recommendationResult">Tap to see your match.</div>
                </div>
            </div>
            <div class="info-card smart-card">
                <h3>Build Your Own Coffee</h3>
                <p class="muted">Customize milk, sugar, ice, and add-ons like a real cafe app.</p>
                <div class="custom-builder" id="customBuilder">
                    <label>Milk</label>
                    <select>
                        <option>Regular</option>
                        <option>Soy</option>
                        <option>Almond</option>
                        <option>Oat</option>
                    </select>
                    <label>Sugar</label>
                    <select>
                        <option>0%</option>
                        <option>25%</option>
                        <option selected>50%</option>
                        <option>100%</option>
                    </select>
                    <label>Ice</label>
                    <select>
                        <option>0%</option>
                        <option>25%</option>
                        <option selected>50%</option>
                        <option>100%</option>
                    </select>
                    <label>Add-ons</label>
                    <div class="addon-options">
                        <label><input type="checkbox" value="Pearls"> <span>Pearls</span></label>
                        <label><input type="checkbox" value="Syrup"> <span>Syrup</span></label>
                        <label><input type="checkbox" value="Cinnamon"> <span>Cinnamon</span></label>
                        <label><input type="checkbox" value="Whipped Cream"> <span>Whipped Cream</span></label>
                    </div>
                    <div class="builder-summary" id="builderSummary">Your custom mix will appear here.</div>
                </div>
            </div>
        </div>
        <div class="grid cards-grid">
            <?php while ($product = $products->fetch_assoc()): ?>
                <article class="card product-card" data-product-name="<?= sanitize($product['name']) ?>" data-product-category="<?= sanitize($product['category']) ?>">
                    <img src="<?= getProductImageUrl($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80'">
                    <div class="card-body">
                        <h3><?= sanitize($product['name']) ?></h3>
                        <p><?= sanitize($product['description']) ?></p>
                        <span class="price"><?= formatPeso($product['price']) ?></span>
                        <form class="product-action" method="post" action="products.php">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <label>Qty</label>
                            <input class="input-sm" type="number" name="quantity" value="1" min="1">
                            <button class="btn btn-primary" type="submit">Add to Cart</button>
                        </form>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?>.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
