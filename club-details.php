<?php
$base = '';
require __DIR__ . '/includes/init.php';

$club_id = (int)($_GET['id'] ?? 0);
$club = $club_id ? row($conn,
    "SELECT c.*, (SELECT COUNT(*) FROM memberships m WHERE m.club_id = c.club_id) AS members
     FROM clubs c WHERE c.club_id = ?", 'i', [$club_id]) : null;

if (!$club) {
    http_response_code(404);
    flash('danger', 'That club could not be found.');
    redirect('clubs.php');
}

// ---- Join / leave (POST + CSRF) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_login();
    $uid = (int)$_SESSION['user_id'];
    $action = $_POST['action'] ?? '';
    if ($action === 'join') {
        q($conn, "INSERT IGNORE INTO memberships (user_id, club_id) VALUES (?, ?)", 'ii', [$uid, $club_id]);
        flash('success', 'You joined ' . $club['club_name'] . '!');
    } elseif ($action === 'leave') {
        q($conn, "DELETE FROM memberships WHERE user_id = ? AND club_id = ?", 'ii', [$uid, $club_id]);
        flash('info', 'You left ' . $club['club_name'] . '.');
    }
    redirect('club-details.php?id=' . $club_id);
}

$is_member = is_logged_in() && val($conn, "SELECT COUNT(*) FROM memberships WHERE user_id = ? AND club_id = ?", 'ii', [$_SESSION['user_id'], $club_id]) > 0;
$events = rows($conn, "SELECT * FROM events WHERE club_id = ? AND event_date >= NOW() ORDER BY event_date ASC", 'i', [$club_id]);
$related = rows($conn, "SELECT club_id, club_name, image FROM clubs WHERE category = ? AND club_id <> ? LIMIT 3", 'si', [$club['category'], $club_id]);

$page_title = $club['club_name'];
$page_desc = excerpt($club['description'], 150);
include __DIR__ . '/includes/header.php';
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="index.php">Home</a> / <a href="clubs.php">Clubs</a> / <span><?= e($club['club_name']) ?></span>
</nav>

<article class="panel detail">
    <img src="<?= e(club_image($club['image'])) ?>" alt="<?= e($club['club_name']) ?>" class="detail-img">
    <div class="detail-body">
        <span class="badge badge-<?= strtolower(e($club['category'])) ?>"><?= e($club['category']) ?></span>
        <h1><?= e($club['club_name']) ?></h1>
        <ul class="meta-list">
            <li><i class="fa-regular fa-clock"></i> <?= e($club['meeting_time']) ?></li>
            <li><i class="fa-solid fa-location-dot"></i> <?= e($club['location']) ?></li>
            <li><i class="fa-solid fa-user-group"></i> <?= (int)$club['members'] ?> member<?= (int)$club['members'] === 1 ? '' : 's' ?></li>
        </ul>
        <p class="description"><?= nl2br(e($club['description'])) ?></p>

        <?php if (is_logged_in()): ?>
            <form method="post">
                <?= csrf_field() ?>
                <?php if ($is_member): ?>
                    <input type="hidden" name="action" value="leave">
                    <p class="member-note"><i class="fa-solid fa-circle-check"></i> You are a member of this club.</p>
                    <button type="submit" class="btn btn-outline confirm" data-confirm="Leave <?= e($club['club_name']) ?>?"><i class="fa-solid fa-right-from-bracket"></i> Leave club</button>
                <?php else: ?>
                    <input type="hidden" name="action" value="join">
                    <button type="submit" class="btn"><i class="fa-solid fa-plus"></i> Join club</button>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <p class="note">Want to join? <a href="login.php">Log in</a> or <a href="register.php">create an account</a>.</p>
        <?php endif; ?>
    </div>
</article>

<section class="section" aria-labelledby="ev-title">
    <h2 id="ev-title" class="section-title">Upcoming events</h2>
    <?php if ($events): ?>
        <div class="grid grid-2">
            <?php foreach ($events as $ev): ?>
                <article class="card event-card">
                    <time class="date-chip" datetime="<?= e($ev['event_date']) ?>"><strong><?= date('d', strtotime($ev['event_date'])) ?></strong><span><?= date('M', strtotime($ev['event_date'])) ?></span></time>
                    <div>
                        <h3><?= e($ev['title']) ?></h3>
                        <p><?= e(excerpt($ev['description'], 110)) ?></p>
                        <p class="meta"><i class="fa-regular fa-clock"></i> <?= e(fmt_date($ev['event_date'], 'H:i')) ?> &middot; <i class="fa-solid fa-location-dot"></i> <?= e($ev['location']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p><a href="events.php" class="link-more">RSVP on the events page <i class="fa-solid fa-arrow-right"></i></a></p>
    <?php else: ?>
        <p>No upcoming events for this club.</p>
    <?php endif; ?>
</section>

<?php if ($related): ?>
<section class="section" aria-labelledby="rel-title">
    <h2 id="rel-title" class="section-title">Similar clubs</h2>
    <ul class="related">
        <?php foreach ($related as $r): ?>
            <li><a href="club-details.php?id=<?= (int)$r['club_id'] ?>"><img src="<?= e(club_image($r['image'])) ?>" alt="" loading="lazy"><span><?= e($r['club_name']) ?></span></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
