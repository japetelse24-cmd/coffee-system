<?php
require_once 'db.php';
$site_info = getSiteInfo();
$cart_count = getCartCount();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($email && $password && $confirm_password) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $update = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update->bind_param('si', $hashedPassword, $user['id']);
                if ($update->execute()) {
                    header('Location: login.php?reset=1');
                    exit;
                }
                $error = 'Unable to update your password. Please try again.';
                $update->close();
            } else {
                $error = 'Email address not found.';
            }
        }
    } else {
        $error = 'Please complete all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Coffee Shop</title>
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
                <a class="btn btn-secondary nav-button" href="login.php">Login</a>
            </div>
        </div>
    </header>
    <main class="container auth-page">
        <section class="card auth-card">
            <h1>Reset Password</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" action="reset_password.php">
                <label>Email</label>
                <input type="email" name="email" value="<?= isset($email) ? $email : '' ?>" required>
                <label>New Password</label>
                <input type="password" name="password" required>
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
            <p class="muted">Remembered your password? <a href="login.php">Login here</a>.</p>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
