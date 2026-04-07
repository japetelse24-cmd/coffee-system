<?php
require_once 'db.php';
$site_info = getSiteInfo();
$cart_count = getCartCount();

$error = '';
$success = '';
$redirect = 'index.php';
if (isset($_GET['redirect'])) {
    $requestedRedirect = basename($_GET['redirect']);
    if (preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $requestedRedirect)) {
        $redirect = $requestedRedirect;
    }
}
if (isset($_GET['registered'])) {
    $success = 'Registration successful. Please log in.';
}
if (isset($_GET['reset'])) {
    $success = 'Password reset successful. Please log in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . $redirect);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop Login</title>
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
            <h1>Login</h1>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" id="loginForm">
                <label>Email</label>
                <input type="email" name="email" id="loginEmail" value="<?= isset($email) ? $email : '' ?>" required>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="loginPassword" required>
                    <button type="button" id="togglePassword" class="password-toggle" aria-label="Show or hide password">👁️</button>
                </div>
                <button type="button" class="btn btn-primary" id="openLoginModal">Confirm Login</button>
            </form>
            <div class="modal-overlay" id="loginConfirmModal" aria-hidden="true">
                <div class="modal-dialog">
                    <button type="button" class="modal-close" id="closeLoginModal">×</button>
                    <div class="modal-body">
                        <h2>Confirm Login</h2>
                        <p>Please confirm you want to log in now with your email.</p>
                        <p class="modal-text"><strong>Email:</strong> <span id="confirmEmailText"></span></p>
                    </div>
                    <div class="receipt-actions">
                        <button type="button" class="btn btn-secondary" id="cancelLoginModal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmLoginSubmit">Continue</button>
                    </div>
                </div>
            </div>
            <div class="auth-links" aria-label="Login help links">
                <a class="auth-link auth-link-primary" href="register.php">Register</a>
                <a class="auth-link" href="reset_password.php">Forgot password?</a>
            </div>
            <p class="muted">Use admin@coffee.com / admin123 for the admin panel.</p>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
