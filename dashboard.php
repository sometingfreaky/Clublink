<?php
$base = '';
require __DIR__ . '/includes/init.php';
require_login();
$uid = (int)$_SESSION['user_id'];

$errors = [];
// ---- POST actions: leave club, cancel RSVP, update profile, change password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'leave') {
        q($conn, "DELETE FROM memberships WHERE user_id = ? AND club_id = ?", 'ii', [$uid, (int)($_POST['club_id'] ?? 0)]);
        flash('info', 'You left the club.');
        redirect('dashboard.php');
    }
    if ($action === 'cancel_rsvp') {
        q($conn, "DELETE FROM event_rsvps WHERE user_id = ? AND event_id = ?", 'ii', [$uid, (int)($_POST['event_id'] ?? 0)]);
        flash('info', 'RSVP cancelled.');
        redirect('dashboard.php');
    }
    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Full name must be 2–100 characters.';
        } else {
            q($conn, "UPDATE users SET full_name = ? WHERE user_id = ?", 'si', [$name, $uid]);
            $_SESSION['name'] = $name;
            flash('success', 'Profile updated.');
            redirect('dashboard.php');
        }
    }
    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $hash    = val($conn, "SELECT password FROM users WHERE user_id = ?", 'i', [$uid]);
        if (!password_verify($current, $hash)) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors[] = 'New password must be at least 8 characters with a letter and a number.';
        } else {
            q($conn, "UPDATE users SET password = ? WHERE user_id = ?", 'si', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
            redirect('dashboard.php');
        }
    }
}

$user = row($conn, "SELECT full_name, email, created_at FROM users WHERE user_id = ?", 'i', [$uid]);
$my_clubs = rows($conn,
    "SELECT c.club_id, c.club_name, c.category, c.meeting_time, m.joined_at
     FROM memberships m JOIN clubs c ON c.club_id = m.club_id
     WHERE m.user_id = ? ORDER BY m.joined_at DESC", 'i', [$uid]);
$my_events = rows($conn,
    "SELECT e.event_id, e.title, e.event_date, e.location, c.club_name
     FROM event_rsvps r JOIN events e ON e.event_id = r.event_id JOIN clubs c ON c.club_id = e.club_id
     WHERE r.user_id = ? AND e.event_date >= NOW() ORDER BY e.event_date", 'i', [$uid]);
$suggested = rows($conn,
    "SELECT club_id, club_name, category FROM clubs
     WHERE club_id NOT IN (SELECT club_id FROM memberships WHERE user_id = ?) ORDER BY RAND() LIMIT 3", 'i', [$uid]);

$page_title = 'My Dashboard';
include __DIR__ . '/includes/header.php';
?>
<header class="page-head">
    <h1><i class="fa-solid fa-table-columns"></i> Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?>!</h1>
    <p class="lead">Your clubs, events and account in one place.</p>
</header>

<ul class="stats stats-inline">
    <li><strong><?= count($my_clubs) ?></strong><span>Clubs joined</span></li>
    <li><strong><?= count($my_events) ?></strong><span>Events RSVP'd</span></li>
    <li><strong><?= e(date('M Y', strtotime($user['created_at']))) ?></strong><span>Member since</span></li>
</ul>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <span><?= e($err) ?></span></div>
<?php endforeach; ?>

<section class="section" aria-labelledby="mc">
    <h2 id="mc" class="section-title">My clubs</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Club</th><th scope="col">Category</th><th scope="col">Meets</th><th scope="col">Joined</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            <?php foreach ($my_clubs as $c): ?>
                <tr>
                    <td data-label="Club"><a href="club-details.php?id=<?= (int)$c['club_id'] ?>"><strong><?= e($c['club_name']) ?></strong></a></td>
                    <td data-label="Category"><span class="badge badge-<?= strtolower(e($c['category'])) ?>"><?= e($c['category']) ?></span></td>
                    <td data-label="Meets"><?= e($c['meeting_time']) ?></td>
                    <td data-label="Joined"><?= e(fmt_date($c['joined_at'], 'j M Y')) ?></td>
                    <td data-label="">
                        <form method="post" class="inline">
                            <?= csrf_field() ?><input type="hidden" name="action" value="leave"><input type="hidden" name="club_id" value="<?= (int)$c['club_id'] ?>">
                            <button class="link-danger confirm" data-confirm="Leave <?= e($c['club_name']) ?>?"><i class="fa-solid fa-right-from-bracket"></i> Leave</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$my_clubs): ?>
                <tr><td colspan="5" class="center empty-cell">You haven't joined any clubs yet. <a href="clubs.php" class="btn btn-small">Browse clubs</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section" aria-labelledby="me">
    <h2 id="me" class="section-title">My upcoming events</h2>
    <?php if ($my_events): ?>
        <div class="stack">
        <?php foreach ($my_events as $ev): ?>
            <article class="card event-card event-wide">
                <time class="date-chip" datetime="<?= e($ev['event_date']) ?>"><strong><?= date('d', strtotime($ev['event_date'])) ?></strong><span><?= date('M', strtotime($ev['event_date'])) ?></span></time>
                <div class="event-info">
                    <h3><?= e($ev['title']) ?></h3>
                    <p class="meta"><?= e($ev['club_name']) ?> &middot; <i class="fa-regular fa-clock"></i> <?= e(fmt_date($ev['event_date'], 'H:i')) ?> &middot; <i class="fa-solid fa-location-dot"></i> <?= e($ev['location']) ?></p>
                </div>
                <form method="post" class="event-action">
                    <?= csrf_field() ?><input type="hidden" name="action" value="cancel_rsvp"><input type="hidden" name="event_id" value="<?= (int)$ev['event_id'] ?>">
                    <button class="btn btn-small btn-outline">Cancel RSVP</button>
                </form>
            </article>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No upcoming RSVPs. <a href="events.php">Find an event</a>.</p>
    <?php endif; ?>
</section>

<?php if ($suggested): ?>
<section class="section" aria-labelledby="sg">
    <h2 id="sg" class="section-title">You might like</h2>
    <ul class="related">
        <?php foreach ($suggested as $s): ?>
            <li><a href="club-details.php?id=<?= (int)$s['club_id'] ?>"><span><?= e($s['club_name']) ?> <small>(<?= e($s['category']) ?>)</small></span></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="section grid grid-2" aria-labelledby="acc">
    <div class="panel">
        <h2 id="acc">Edit profile</h2>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <div class="field"><label for="pname">Full name</label><input id="pname" name="name" value="<?= e($user['full_name']) ?>" required minlength="2" maxlength="100"></div>
            <div class="field"><label for="pemail">Email</label><input id="pemail" value="<?= e($user['email']) ?>" disabled></div>
            <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
        </form>
    </div>
    <div class="panel">
        <h2>Change password</h2>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <div class="field"><label for="cp">Current password</label><input type="password" id="cp" name="current_password" autocomplete="current-password" required></div>
            <div class="field"><label for="np">New password</label><input type="password" id="np" name="new_password" autocomplete="new-password" minlength="8" required></div>
            <button class="btn btn-dark"><i class="fa-solid fa-key"></i> Update password</button>
        </form>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
