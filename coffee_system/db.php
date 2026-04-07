<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'coffee_shop';

$mysqli = new mysqli($host, $user, $password, $database);
if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$columnCheck = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'slot_time'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE orders ADD COLUMN slot_time VARCHAR(100) NOT NULL DEFAULT 'Anytime' AFTER status");
}

$slotsTableCheck = $mysqli->query("SHOW TABLES LIKE 'slots'");
if ($slotsTableCheck && $slotsTableCheck->num_rows === 0) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS slots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $mysqli->query("INSERT IGNORE INTO slots (name) VALUES
        ('Anytime'),
        ('Morning 8:00 - 10:00'),
        ('Late Morning 11:00 - 13:00'),
        ('Afternoon 14:00 - 16:00'),
        ('Evening 17:00 - 19:00')");
}

$orderItemsTableCheck = $mysqli->query("SHOW TABLES LIKE 'order_items'");
if ($orderItemsTableCheck && $orderItemsTableCheck->num_rows === 0) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(8,2) NOT NULL,
        customizations TEXT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
}

$columnCheck = $mysqli->query("SHOW COLUMNS FROM order_items LIKE 'customizations'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE order_items ADD COLUMN customizations TEXT NULL AFTER price");
}

$siteInfoTableCheck = $mysqli->query("SHOW TABLES LIKE 'site_info'");
if ($siteInfoTableCheck && $siteInfoTableCheck->num_rows === 0) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS site_info (
        id INT PRIMARY KEY,
        owner_name VARCHAR(255) NOT NULL DEFAULT 'ELSE Coffee',
        system_name VARCHAR(255) NOT NULL DEFAULT 'ELSE Coffee',
        address VARCHAR(255) NOT NULL DEFAULT '123 Coffee Lane, Brewtown',
        email VARCHAR(255) NOT NULL DEFAULT 'info@elsecoffee.com',
        contact_number VARCHAR(50) NOT NULL DEFAULT '+63 912 345 6789',
        facebook_url VARCHAR(255) NOT NULL DEFAULT 'https://facebook.com/ELSECoffee',
        instagram_url VARCHAR(255) NOT NULL DEFAULT 'https://instagram.com/ELSECoffee',
        tiktok_url VARCHAR(255) NOT NULL DEFAULT 'https://tiktok.com/@elsecoffee',
        social_media VARCHAR(255) NOT NULL DEFAULT 'Facebook: @ELSECoffee | Instagram: @ELSECoffee',
        description TEXT NOT NULL DEFAULT 'Your trusted local coffee shop serving fresh brews daily.'
    )");
    $mysqli->query("INSERT INTO site_info (id, owner_name, system_name, address, email, contact_number, facebook_url, instagram_url, tiktok_url, social_media, description) VALUES
        (1, 'ELSE Coffee', 'ELSE Coffee', '123 Coffee Lane, Brewtown', 'info@elsecoffee.com', '+63 912 345 6789', 'https://facebook.com/ELSECoffee', 'https://instagram.com/ELSECoffee', 'https://tiktok.com/@elsecoffee', 'Facebook: @ELSECoffee | Instagram: @ELSECoffee', 'Your trusted local coffee shop serving fresh brews daily.')");
}

$columnCheck = $mysqli->query("SHOW COLUMNS FROM site_info LIKE 'owner_name'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE site_info ADD COLUMN owner_name VARCHAR(255) NOT NULL DEFAULT 'ELSE Coffee' AFTER id");
}
$columnCheck = $mysqli->query("SHOW COLUMNS FROM site_info LIKE 'email'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE site_info ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT 'info@elsecoffee.com' AFTER address");
}
$columnCheck = $mysqli->query("SHOW COLUMNS FROM site_info LIKE 'contact_number'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE site_info ADD COLUMN contact_number VARCHAR(50) NOT NULL DEFAULT '+63 912 345 6789' AFTER email");
}
$columnCheck = $mysqli->query("SHOW COLUMNS FROM site_info LIKE 'facebook_url'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE site_info ADD COLUMN facebook_url VARCHAR(255) NOT NULL DEFAULT 'https://facebook.com/ELSECoffee' AFTER contact_number");
}
$columnCheck = $mysqli->query("SHOW COLUMNS FROM site_info LIKE 'instagram_url'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE site_info ADD COLUMN instagram_url VARCHAR(255) NOT NULL DEFAULT 'https://instagram.com/ELSECoffee' AFTER facebook_url");
}
$columnCheck = $mysqli->query("SHOW COLUMNS FROM site_info LIKE 'tiktok_url'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE site_info ADD COLUMN tiktok_url VARCHAR(255) NOT NULL DEFAULT 'https://tiktok.com/@elsecoffee' AFTER instagram_url");
}
$columnCheck = $mysqli->query("SHOW COLUMNS FROM products LIKE 'sort_order'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $mysqli->query("ALTER TABLE products ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER category");
    $mysqli->query("UPDATE products SET sort_order = id");
}
$mysqli->query("UPDATE site_info SET owner_name = COALESCE(NULLIF(owner_name, ''), system_name) WHERE id = 1");

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

function getCartCount() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function getProductImageUrl($imageUrl) {
    $imageUrl = trim((string)$imageUrl);
    if ($imageUrl === '') {
        return 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80';
    }
    $imageUrl = html_entity_decode($imageUrl, ENT_QUOTES, 'UTF-8');
    return htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
}

function formatPeso($amount) {
    return '₱' . number_format($amount, 2);
}

function getSlotOptions() {
    global $mysqli;
    $slots = [];
    $result = $mysqli->query('SELECT name FROM slots ORDER BY id');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $slots[] = $row['name'];
        }
        $result->free();
    }

    if (empty($slots)) {
        $slots = [
            'Anytime',
            'Morning 8:00 - 10:00',
            'Late Morning 11:00 - 13:00',
            'Afternoon 14:00 - 16:00',
            'Evening 17:00 - 19:00'
        ];
    }

    return $slots;
}

function getSiteInfo() {
    global $mysqli;
    $result = $mysqli->query('SELECT * FROM site_info ORDER BY id LIMIT 1');
    if ($result && $siteInfo = $result->fetch_assoc()) {
        if (empty($siteInfo['owner_name'])) {
            $siteInfo['owner_name'] = $siteInfo['system_name'] ?? 'ELSE Coffee';
        }
        return $siteInfo;
    }
    return [
        'owner_name' => 'ELSE Coffee',
        'system_name' => 'ELSE Coffee',
        'address' => '123 Coffee Lane, Brewtown',
        'email' => 'info@elsecoffee.com',
        'contact_number' => '+63 912 345 6789',
        'facebook_url' => 'https://facebook.com/ELSECoffee',
        'instagram_url' => 'https://instagram.com/ELSECoffee',
        'tiktok_url' => 'https://tiktok.com/@elsecoffee',
        'social_media' => 'Facebook: @ELSECoffee | Instagram: @ELSECoffee',
        'description' => 'Your trusted local coffee shop serving fresh brews daily.'
    ];
}

function saveSiteInfo($owner_name, $address, $email, $contact_number, $facebook_url, $instagram_url, $tiktok_url, $description) {
    global $mysqli;
    $social_media = 'Facebook | Instagram | TikTok';
    $stmt = $mysqli->prepare("INSERT INTO site_info (id, owner_name, address, email, contact_number, facebook_url, instagram_url, tiktok_url, social_media, description) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE owner_name = VALUES(owner_name), address = VALUES(address), email = VALUES(email), contact_number = VALUES(contact_number), facebook_url = VALUES(facebook_url), instagram_url = VALUES(instagram_url), tiktok_url = VALUES(tiktok_url), social_media = VALUES(social_media), description = VALUES(description)");
    if ($stmt) {
        $stmt->bind_param('sssssssss', $owner_name, $address, $email, $contact_number, $facebook_url, $instagram_url, $tiktok_url, $social_media, $description);
        $stmt->execute();
        $stmt->close();
    }
}
?>
