<?php
require_once 'db.php';
$featured_products = $mysqli->query('SELECT * FROM products ORDER BY sort_order ASC, id ASC LIMIT 4');
$cart_count = getCartCount();
$site_info = getSiteInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?> Home</title>
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
    <main class="container home-page">
        <section class="hero">
            <div>
                <span class="eyebrow">Fresh coffee every day</span>
                <h1>Welcome to <?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?></h1>
                <p><?= sanitize($site_info['description']) ?></p>
                <a class="btn btn-primary" href="products.php">Browse Products</a>
            </div>
            <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93" alt="Coffee Cup">
        </section>

        <section class="section">
            <div class="section-header">
                <h2>Recommended For You</h2>
                <p>We remember your last order and suggest what to try next.</p>
            </div>
            <div class="grid info-grid" id="lastOrderSection">
                <div class="info-card">
                    <h3>No recent order yet</h3>
                    <p class="muted">Place an order and we will show your favorites here.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header">
                <h2>Featured Products</h2>
                <p>Our top picks for a fresh coffee experience.</p>
            </div>
            <div class="grid cards-grid">
                <?php while ($product = $featured_products->fetch_assoc()): ?>
                    <article class="card product-card">
                        <img src="<?= getProductImageUrl($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80'">
                        <div class="card-body">
                            <h3><?= sanitize($product['name']) ?></h3>
                            <p><?= sanitize($product['description']) ?></p>
                            <div class="card-footer">
                                <span class="price"><?= formatPeso($product['price']) ?></span>
                                <a class="btn btn-secondary" href="products.php">View</a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="section stats-section">
            <div class="stats-card">
                <h3>Fast Ordering</h3>
                <p>Add favorite coffee products to your cart and checkout quickly.</p>
            </div>
            <div class="stats-card">
                <h3>Admin Ready</h3>
                <p>Manage products, stock, and order history from the admin dashboard.</p>
            </div>
            <div class="stats-card">
                <h3>Responsive UI</h3>
                <p>Works well on desktop and mobile with clean modern styling.</p>
            </div>
        </section>
    </main>

    <section class="section about-us-section">
        <div class="container">
            <div class="section-header">
                <h2>About Us</h2>
                <p>Learn more about our coffee shop, where we are located, and how to connect with us online.</p>
            </div>
            <div class="about-us-copy">
                <p><strong>Owner Name:</strong> <?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?></p>
                <p><strong>Address:</strong> <?= sanitize($site_info['address']) ?></p>
                <p><strong>Email:</strong> <a href="mailto:<?= sanitize($site_info['email']) ?>"><?= sanitize($site_info['email']) ?></a></p>
                <p><strong>Contact Number:</strong> <a href="tel:<?= urlencode($site_info['contact_number']) ?>"><?= sanitize($site_info['contact_number']) ?></a></p>
                <div class="social-links">
                    <?php if (!empty($site_info['facebook_url'])): ?>
                        <a class="social-link facebook" href="<?= sanitize($site_info['facebook_url']) ?>" target="_blank" rel="noreferrer noopener">
                            <span class="social-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.2V12h2.2V9.4c0-2.2 1.3-3.5 3.4-3.5.98 0 2 .18 2 .18v2.2h-1.13c-1.12 0-1.47.7-1.47 1.42V12h2.5l-.4 2.9h-2.1v7A10 10 0 0 0 22 12Z"/></svg></span>
                            Facebook
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['instagram_url'])): ?>
                        <a class="social-link instagram" href="<?= sanitize($site_info['instagram_url']) ?>" target="_blank" rel="noreferrer noopener">
                            <span class="social-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/></svg></span>
                            Instagram
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['tiktok_url'])): ?>
                        <a class="social-link tiktok" href="<?= sanitize($site_info['tiktok_url']) ?>" target="_blank" rel="noreferrer noopener">
                            <span class="social-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M9.5 4.25h2.75v6.92a3.75 3.75 0 0 0 3.38 3.7v2.62a6.38 6.38 0 0 1-5.88-6.32V4.25Zm7.5 0h2.75v2.2a3.75 3.75 0 0 1-3.37-1.78V4.25Z"/></svg></span>
                            TikTok
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= sanitize($site_info['owner_name'] ?? $site_info['system_name']) ?>.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
