<?php
$base = '';
require __DIR__ . '/includes/init.php';
if (is_logged_in()) { redirect('dashboard.php'); }

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) { $errors[] = 'Full name must be 2–100 characters.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) { $errors[] = 'Enter a valid email address.'; }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must be at least 8 characters and contain a letter and a number.';
    }
    if ($password !== $confirm) { $errors[] = 'Passwords do not match.'; }

    if (!$errors && val($conn, "SELECT COUNT(*) FROM users WHERE email = ?", 's', [$email]) > 0) {
        $errors[] = 'That email is already registered. Try logging in.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        q($conn, "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)", 'sss', [$name, $email, $hash]);
        flash('success', 'Account created! You can log in now.');
        redirect('login.php');
    }
}

$page_title = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="form-container">
    <h1 class="center"><i class="fa-solid fa-user-plus"></i> Create Account</h1>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <span><?= e($err) ?></span></div>
    <?php endforeach; ?>
    <div id="js-error" class="alert alert-danger" role="alert" hidden></div>
    <form method="post" id="registerForm" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="<?= e($name) ?>" autocomplete="name" required minlength="2" maxlength="100">
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" placeholder="you@students.nust.na" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8">
            <div class="strength" aria-hidden="true"><span id="strengthBar"></span></div>
            <small id="strengthText" class="hint">At least 8 characters, with a letter and a number.</small>
        </div>
        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-block"><i class="fa-solid fa-check"></i> Create account</button>
    </form>
    <p class="center note">Already have an account? <a href="login.php">Log in</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
