<?php
$base = '';
require __DIR__ . '/includes/init.php';

// ---- Read & validate filters ----
$search   = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$sort     = $_GET['sort'] ?? 'name';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 6;

if (!in_array($category, CATEGORIES, true)) { $category = ''; }
$sort_map = [
    'name'    => 'c.club_name ASC',
    'newest'  => 'c.created_at DESC, c.club_id DESC',
    'popular' => 'members DESC, c.club_name ASC',
];
if (!isset($sort_map[$sort])) { $sort = 'name'; }

// ---- Build a safe, parameterised WHERE clause ----
$where = ['1=1']; $types = ''; $params = [];
if ($search !== '') {
    $where[] = '(c.club_name LIKE ? OR c.description LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss'; $params[] = $like; $params[] = $like;
}
if ($category !== '') {
    $where[] = 'c.category = ?';
    $types .= 's'; $params[] = $category;
}
$where_sql = implode(' AND ', $where);

$total = (int)val($conn, "SELECT COUNT(*) FROM clubs c WHERE $where_sql", $types, $params);
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

$clubs = rows($conn,
    "SELECT c.*, (SELECT COUNT(*) FROM memberships m WHERE m.club_id = c.club_id) AS members
     FROM clubs c WHERE $where_sql ORDER BY {$sort_map[$sort]} LIMIT ? OFFSET ?",
    $types . 'ii', array_merge($params, [$per_page, $offset]));

function page_url(int $p): string {
    $q = $_GET; $q['page'] = $p;
    return 'clubs.php?' . http_build_query($q);
}

$page_title = 'Find a Club';
include __DIR__ . '/includes/header.php';
?>
<header class="page-head">
    <h1><i class="fa-solid fa-users"></i> Explore Student Clubs</h1>
    <p class="lead">Find the community that fits your interests.</p>
</header>

<form method="get" action="clubs.php" class="filter-bar" role="search" id="filterForm">
    <div class="field">
        <label for="search">Search</label>
        <input type="search" id="search" name="search" placeholder="Name or keyword…" value="<?= e($search) ?>">
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach (CATEGORIES as $c): ?>
                <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A–Z)</option>
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most popular</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        </select>
    </div>
    <div class="field field-actions">
        <button type="submit" class="btn"><i class="fa-solid fa-filter"></i> Apply</button>
        <a href="clubs.php" class="btn btn-outline">Reset</a>
    </div>
</form>

<p class="result-count" aria-live="polite"><?= $total ?> club<?= $total === 1 ? '' : 's' ?> found<?= $search !== '' ? ' for “' . e($search) . '”' : '' ?></p>

<div class="grid grid-3" id="clubGrid">
    <?php foreach ($clubs as $club): ?>
        <article class="card club-card" data-name="<?= e(strtolower($club['club_name'])) ?>">
            <img src="<?= e(club_image($club['image'])) ?>" alt="<?= e($club['club_name']) ?>" class="card-img" loading="lazy">
            <div class="card-body">
                <span class="badge badge-<?= strtolower(e($club['category'])) ?>"><?= e($club['category']) ?></span>
                <h2 class="card-title"><?= e($club['club_name']) ?></h2>
                <p><?= e(excerpt($club['description'], 90)) ?></p>
                <p class="meta"><i class="fa-regular fa-clock"></i> <?= e($club['meeting_time']) ?></p>
                <p class="meta"><i class="fa-solid fa-user-group"></i> <?= (int)$club['members'] ?> member<?= (int)$club['members'] === 1 ? '' : 's' ?></p>
                <a href="club-details.php?id=<?= (int)$club['club_id'] ?>" class="btn btn-small push-bottom"><i class="fa-solid fa-eye"></i> View details</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!$clubs): ?>
    <div class="empty"><i class="fa-regular fa-face-frown"></i><p>No clubs match your search.</p><a href="clubs.php" class="btn">Clear filters</a></div>
<?php endif; ?>

<?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php if ($page > 1): ?><a href="<?= e(page_url($page - 1)) ?>" rel="prev">&laquo; Prev</a><?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="<?= e(page_url($i)) ?>" class="<?= $i === $page ? 'current' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= e(page_url($page + 1)) ?>" rel="next">Next &raquo;</a><?php endif; ?>
    </nav>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
