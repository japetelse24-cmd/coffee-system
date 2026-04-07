<?php
require_once 'db.php';
$site_info = getSiteInfo();
$cart_count = getCartCount();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name && $email && $password && $confirm_password) {
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
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'An account with that email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insert = $mysqli->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $insert->bind_param('sss', $name, $email, $hashedPassword);
                if ($insert->execute()) {
                    header('Location: login.php?registered=1');
                    exit;
                }
                $error = 'Unable to create your account. Please try again.';
                $insert->close();
            }
            $stmt->close();
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
    <title>Register - Coffee Shop</title>
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
            <h1>Create Account</h1>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" action="register.php">
                <label>Name</label>
                <input type="text" name="name" value="<?= isset($name) ? $name : '' ?>" required>
                <label>Email</label>
                <input type="email" name="email" value="<?= isset($email) ? $email : '' ?>" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            <div class="auth-links" aria-label="Register help links">
                <a class="auth-link" href="login.php">Back to Login</a>
            </div>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
