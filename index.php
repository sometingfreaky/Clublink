<?php
$base = '';
require __DIR__ . '/includes/init.php';

$stats = [
    'clubs'   => (int)val($conn, "SELECT COUNT(*) FROM clubs"),
    'members' => (int)val($conn, "SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'events'  => (int)val($conn, "SELECT COUNT(*) FROM events WHERE event_date >= NOW()"),
];

$featured = rows($conn,
    "SELECT c.*, (SELECT COUNT(*) FROM memberships m WHERE m.club_id = c.club_id) AS members
     FROM clubs c ORDER BY members DESC, c.created_at DESC LIMIT 3");

$upcoming = rows($conn,
    "SELECT e.*, c.club_name FROM events e JOIN clubs c ON c.club_id = e.club_id
     WHERE e.event_date >= NOW() ORDER BY e.event_date ASC LIMIT 3");

$cat_counts = [];
foreach (rows($conn, "SELECT category, COUNT(*) AS total FROM clubs GROUP BY category") as $r) {
    $cat_counts[$r['category']] = (int)$r['total'];
}
$cat_icons = ['Social' => 'fa-people-group', 'Sports' => 'fa-futbol', 'Academic' => 'fa-graduation-cap', 'Culture' => 'fa-masks-theater'];

$page_title = 'Home';
include __DIR__ . '/includes/header.php';
?>
<section class="hero" aria-labelledby="hero-title">
    <div class="hero-text">
        <p class="eyebrow">NUST Student Life</p>
        <h1 id="hero-title">Connect. Engage. <span class="accent">Belong.</span></h1>
        <p class="lead">Club Link is the official platform to discover student organisations, join in one click and keep up with every event on campus.</p>
        <div class="btn-row">
            <a href="clubs.php" class="btn"><i class="fa-solid fa-users"></i> Explore Clubs</a>
            <?php if (!is_logged_in()): ?>
                <a href="register.php" class="btn btn-dark"><i class="fa-solid fa-user-plus"></i> Create Account</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-dark"><i class="fa-solid fa-table-columns"></i> My Dashboard</a>
            <?php endif; ?>
        </div>
        <form action="clubs.php" method="get" class="hero-search" role="search">
            <label for="hero-q" class="sr-only">Search clubs</label>
            <input type="search" id="hero-q" name="search" placeholder="Search for a club, e.g. chess, football…">
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>
    <ul class="stats" aria-label="Platform statistics">
        <li><strong data-count="<?= $stats['clubs'] ?>"><?= $stats['clubs'] ?></strong><span>Active clubs</span></li>
        <li><strong data-count="<?= $stats['members'] ?>"><?= $stats['members'] ?></strong><span>Students</span></li>
        <li><strong data-count="<?= $stats['events'] ?>"><?= $stats['events'] ?></strong><span>Upcoming events</span></li>
    </ul>
</section>

<section class="section" aria-labelledby="cat-title">
    <h2 id="cat-title" class="section-title">Browse by category</h2>
    <div class="grid grid-4">
        <?php foreach (CATEGORIES as $cat): ?>
            <a class="category-tile" href="clubs.php?category=<?= urlencode($cat) ?>">
                <i class="fa-solid <?= $cat_icons[$cat] ?>"></i>
                <h3><?= e($cat) ?></h3>
                <p><?= $cat_counts[$cat] ?? 0 ?> club<?= ($cat_counts[$cat] ?? 0) === 1 ? '' : 's' ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" aria-labelledby="feat-title">
    <div class="section-head">
        <h2 id="feat-title" class="section-title">Popular clubs</h2>
        <a href="clubs.php" class="link-more">See all clubs <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($featured as $club): ?>
            <article class="card club-card">
                <img src="<?= e(club_image($club['image'])) ?>" alt="<?= e($club['club_name']) ?>" class="card-img" loading="lazy">
                <div class="card-body">
                    <span class="badge badge-<?= strtolower(e($club['category'])) ?>"><?= e($club['category']) ?></span>
                    <h3><?= e($club['club_name']) ?></h3>
                    <p><?= e(excerpt($club['description'], 95)) ?></p>
                    <p class="meta"><i class="fa-solid fa-user-group"></i> <?= (int)$club['members'] ?> member<?= (int)$club['members'] === 1 ? '' : 's' ?></p>
                    <a href="club-details.php?id=<?= (int)$club['club_id'] ?>" class="btn btn-small push-bottom">View details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" aria-labelledby="ev-title">
    <div class="section-head">
        <h2 id="ev-title" class="section-title">Upcoming events</h2>
        <a href="events.php" class="link-more">All events <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($upcoming as $ev): ?>
            <article class="card event-card">
                <time class="date-chip" datetime="<?= e($ev['event_date']) ?>">
                    <strong><?= date('d', strtotime($ev['event_date'])) ?></strong>
                    <span><?= date('M', strtotime($ev['event_date'])) ?></span>
                </time>
                <div>
                    <h3><?= e($ev['title']) ?></h3>
                    <p class="meta"><?= e($ev['club_name']) ?></p>
                    <p class="meta"><i class="fa-regular fa-clock"></i> <?= e(fmt_date($ev['event_date'], 'H:i')) ?> &middot; <i class="fa-solid fa-location-dot"></i> <?= e($ev['location']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$upcoming): ?><p>No upcoming events yet. Check back soon!</p><?php endif; ?>
    </div>
</section>

<section class="section" aria-labelledby="how-title">
    <h2 id="how-title" class="section-title">How it works</h2>
    <ol class="steps">
        <li><span class="step-num">1</span><h3>Create an account</h3><p>Sign up in under a minute with your email.</p></li>
        <li><span class="step-num">2</span><h3>Find your club</h3><p>Search or filter by category, then read the details.</p></li>
        <li><span class="step-num">3</span><h3>Join &amp; RSVP</h3><p>Join with one click and RSVP to events from your dashboard.</p></li>
    </ol>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
