<?php
$base = '';
require __DIR__ . '/includes/init.php';

$errors = [];
$f = ['name' => $_SESSION['name'] ?? '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }

    // Honeypot: bots fill the hidden "website" field, people never see it
    if (!empty($_POST['website'])) { redirect('contact.php'); }

    if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) { $errors[] = 'Please enter your name.'; }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (mb_strlen($f['subject']) < 3 || mb_strlen($f['subject']) > 150) { $errors[] = 'Subject must be 3–150 characters.'; }
    if (mb_strlen($f['message']) < 10 || mb_strlen($f['message']) > 2000) { $errors[] = 'Message must be 10–2000 characters.'; }

    if (!$errors) {
        q($conn, "INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)", 'ssss',
          [$f['name'], $f['email'], $f['subject'], $f['message']]);
        flash('success', 'Thank you! Your message has been sent.');
        redirect('contact.php');
    }
}

$page_title = 'Contact';
include __DIR__ . '/includes/header.php';
?>
<header class="page-head">
    <h1><i class="fa-solid fa-envelope"></i> Contact Us</h1>
    <p class="lead">Questions, feedback, or want your club listed? Send us a message.</p>
</header>
<div class="grid grid-2 contact-grid">
    <div class="form-container flush">
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <span><?= e($err) ?></span></div>
        <?php endforeach; ?>
        <form method="post" id="contactForm" novalidate>
            <?= csrf_field() ?>
            <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="field"><label for="name">Your name</label><input id="name" name="name" value="<?= e($f['name']) ?>" required maxlength="100"></div>
            <div class="field"><label for="email">Your email</label><input type="email" id="email" name="email" value="<?= e($f['email']) ?>" required></div>
            <div class="field"><label for="subject">Subject</label><input id="subject" name="subject" value="<?= e($f['subject']) ?>" required maxlength="150"></div>
            <div class="field">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="6" required maxlength="2000"><?= e($f['message']) ?></textarea>
                <small class="hint"><span id="charCount">0</span> / 2000</small>
            </div>
            <button type="submit" class="btn btn-block"><i class="fa-solid fa-paper-plane"></i> Send message</button>
        </form>
    </div>
    <aside class="panel info-panel">
        <h2>Get in touch</h2>
        <ul class="contact-list">
            <li><i class="fa-solid fa-location-dot"></i><span>NUST, 13 Jackson Kaujeua Street, Windhoek, Namibia</span></li>
            <li><i class="fa-solid fa-envelope"></i><span>clublink@nust.na</span></li>
            <li><i class="fa-regular fa-clock"></i><span>Mon–Fri, 08:00–17:00</span></li>
        </ul>
        <p class="note">We usually reply within two working days.</p>
    </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
