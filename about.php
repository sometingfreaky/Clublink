<?php
$base = '';
require __DIR__ . '/includes/init.php';
$page_title = 'About';
$page_desc = 'Why Club Link was built and how it helps NUST students find their community.';
include __DIR__ . '/includes/header.php';
?>
<article class="panel">
    <header class="page-head">
        <h1><i class="fa-solid fa-circle-info"></i> About Club Link</h1>
        <p class="lead">Club information at NUST used to live in posters, WhatsApp groups and word of mouth. Club Link brings it all into one place.</p>
    </header>

    <section>
        <h2>The problem we solve</h2>
        <p>New and returning students often struggle to find out which clubs exist, when they meet and how to join. Club leaders, in turn, have no simple way to keep track of members or announce events. Club Link centralises social, sports, academic and cultural communities into one easy-to-use platform.</p>
    </section>

    <section>
        <h2>Our mission</h2>
        <ul class="check-list">
            <li><i class="fa-solid fa-circle-check"></i> Help new and returning students find their community.</li>
            <li><i class="fa-solid fa-circle-check"></i> Give administrators a simple way to manage clubs, events and members.</li>
            <li><i class="fa-solid fa-circle-check"></i> Foster a vibrant and engaged campus culture.</li>
        </ul>
    </section>

    <section>
        <h2>What you can do</h2>
        <div class="grid grid-3">
            <div class="feature"><i class="fa-solid fa-magnifying-glass"></i><h3>Discover</h3><p>Search and filter clubs by name or category, and sort by popularity.</p></div>
            <div class="feature"><i class="fa-solid fa-handshake"></i><h3>Join</h3><p>Join or leave any club with one click and track memberships on your dashboard.</p></div>
            <div class="feature"><i class="fa-solid fa-calendar-check"></i><h3>RSVP</h3><p>See upcoming events and RSVP so organisers know how many to expect.</p></div>
        </div>
    </section>

    <section>
        <h2>Technology</h2>
        <table class="table">
            <caption class="sr-only">Technologies used in Club Link</caption>
            <thead><tr><th scope="col">Layer</th><th scope="col">Technology</th></tr></thead>
            <tbody>
                <tr><th scope="row">Front-end</th><td>HTML5, CSS3 (Flexbox, Grid, media queries), JavaScript</td></tr>
                <tr><th scope="row">Back-end</th><td>PHP 8 with sessions and prepared statements</td></tr>
                <tr><th scope="row">Database</th><td>MySQL / MariaDB (relational, foreign keys)</td></tr>
                <tr><th scope="row">Security</th><td>Password hashing, CSRF tokens, output escaping, validated uploads</td></tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Frequently asked questions</h2>
        <details><summary>Is Club Link free?</summary><p>Yes, it is free for all NUST students.</p></details>
        <details><summary>How do I start a new club?</summary><p>Send us a message through the <a href="contact.php">contact page</a> and an administrator will add your club.</p></details>
        <details><summary>Can I leave a club later?</summary><p>Yes. Open the club page or your dashboard and choose <em>Leave club</em>.</p></details>
    </section>
</article>
<?php include __DIR__ . '/includes/footer.php'; ?>
