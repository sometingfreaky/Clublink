<?php
$base = '';
require __DIR__ . '/includes/init.php';

// ---- RSVP / cancel RSVP ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_login();
    $uid = (int)$_SESSION['user_id'];
    $eid = (int)($_POST['event_id'] ?? 0);
    $ev = row($conn, "SELECT title FROM events WHERE event_id = ?", 'i', [$eid]);
    if ($ev) {
        if (($_POST['action'] ?? '') === 'rsvp') {
            q($conn, "INSERT IGNORE INTO event_rsvps (event_id, user_id) VALUES (?, ?)", 'ii', [$eid, $uid]);
            flash('success', 'You are going to “' . $ev['title'] . '”.');
        } else {
            q($conn, "DELETE FROM event_rsvps WHERE event_id = ? AND user_id = ?", 'ii', [$eid, $uid]);
            flash('info', 'RSVP cancelled.');
        }
    }
    redirect('events.php' . (isset($_GET['show']) && $_GET['show'] === 'past' ? '?show=past' : ''));
}

$show = (($_GET['show'] ?? '') === 'past') ? 'past' : 'upcoming';
$club_filter = (int)($_GET['club'] ?? 0);

$where = $show === 'past' ? 'e.event_date < NOW()' : 'e.event_date >= NOW()';
$types = ''; $params = [];
if ($club_filter) { $where .= ' AND e.club_id = ?'; $types .= 'i'; $params[] = $club_filter; }
$order = $show === 'past' ? 'DESC' : 'ASC';

$uid = (int)($_SESSION['user_id'] ?? 0);
$events = rows($conn,
    "SELECT e.*, c.club_name, c.category,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.event_id) AS going,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.event_id AND r.user_id = $uid) AS i_go
     FROM events e JOIN clubs c ON c.club_id = e.club_id
     WHERE $where ORDER BY e.event_date $order", $types, $params);
$clubs = rows($conn, "SELECT club_id, club_name FROM clubs ORDER BY club_name");

$page_title = 'Events';
include __DIR__ . '/includes/header.php';
?>
<header class="page-head">
    <h1><i class="fa-solid fa-calendar-days"></i> Campus Events</h1>
    <p class="lead">See what is happening and RSVP so organisers know you are coming.</p>
</header>

<form method="get" class="filter-bar">
    <div class="field">
        <label for="club">Club</label>
        <select id="club" name="club">
            <option value="0">All clubs</option>
            <?php foreach ($clubs as $c): ?>
                <option value="<?= (int)$c['club_id'] ?>" <?= $club_filter === (int)$c['club_id'] ? 'selected' : '' ?>><?= e($c['club_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="show">Show</label>
        <select id="show" name="show">
            <option value="upcoming" <?= $show === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
            <option value="past" <?= $show === 'past' ? 'selected' : '' ?>>Past</option>
        </select>
    </div>
    <div class="field field-actions"><button type="submit" class="btn"><i class="fa-solid fa-filter"></i> Apply</button></div>
</form>

<div class="stack">
    <?php foreach ($events as $ev): ?>
        <article class="card event-card event-wide">
            <time class="date-chip" datetime="<?= e($ev['event_date']) ?>"><strong><?= date('d', strtotime($ev['event_date'])) ?></strong><span><?= date('M', strtotime($ev['event_date'])) ?></span></time>
            <div class="event-info">
                <h2 class="card-title"><?= e($ev['title']) ?></h2>
                <p class="meta"><a href="club-details.php?id=<?= (int)$ev['club_id'] ?>"><?= e($ev['club_name']) ?></a> &middot; <span class="badge badge-<?= strtolower(e($ev['category'])) ?>"><?= e($ev['category']) ?></span></p>
                <p><?= e($ev['description']) ?></p>
                <p class="meta"><i class="fa-regular fa-clock"></i> <?= e(fmt_date($ev['event_date'])) ?> &middot; <i class="fa-solid fa-location-dot"></i> <?= e($ev['location']) ?> &middot; <i class="fa-solid fa-users"></i> <?= (int)$ev['going'] ?> going</p>
            </div>
            <?php if ($show === 'upcoming'): ?>
                <form method="post" class="event-action">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= (int)$ev['event_id'] ?>">
                    <?php if (!is_logged_in()): ?>
                        <a href="login.php" class="btn btn-small btn-outline">Log in to RSVP</a>
                    <?php elseif ($ev['i_go']): ?>
                        <input type="hidden" name="action" value="cancel">
                        <button class="btn btn-small btn-outline"><i class="fa-solid fa-check"></i> Going &ndash; cancel</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="rsvp">
                        <button class="btn btn-small"><i class="fa-solid fa-ticket"></i> RSVP</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!$events): ?>
    <div class="empty"><i class="fa-regular fa-calendar-xmark"></i><p>No <?= e($show) ?> events found.</p></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
