<?php
$base = '';
require __DIR__ . '/includes/init.php';
if (is_logged_in()) { redirect('dashboard.php'); }

$error = '';
$email = '';
const MAX_ATTEMPTS = 5;
const LOCK_SECONDS = 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Simple brute-force protection: lock the session after repeated failures
    $locked_until = $_SESSION['lock_until'] ?? 0;
    if ($locked_until > time()) {
        $error = 'Too many failed attempts. Try again in ' . ($locked_until - time()) . ' seconds.';
    } else {
        $user = row($conn, "SELECT * FROM users WHERE email = ?", 's', [$email]);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);               // prevents session fixation
            unset($_SESSION['attempts'], $_SESSION['lock_until']);
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['full_name'];
            flash('success', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '!');
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
        }
        // Same message for unknown email and wrong password (no user enumeration)
        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] >= MAX_ATTEMPTS) {
            $_SESSION['lock_until'] = time() + LOCK_SECONDS;
            $_SESSION['attempts'] = 0;
        }
        $error = 'Incorrect email or password.';
    }
}

$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="form-container">
    <h1 class="center"><i class="fa-solid fa-right-to-bracket"></i> Login</h1>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <span><?= e($error) ?></span></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" data-target="password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
            </div>
        </div>
        <button type="submit" class="btn btn-block"><i class="fa-solid fa-right-to-bracket"></i> Log in</button>
    </form>
    <p class="center note">New here? <a href="register.php">Create an account</a></p>
    <details class="demo-box">
        <summary>Demo accounts</summary>
        <p>Student: <code>student@nust.na</code> / <code>Student@123</code></p>
        <p>Admin: <code>admin@nust.na</code> / <code>Admin@123</code></p>
    </details>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
