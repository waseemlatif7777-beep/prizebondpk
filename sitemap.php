<?php
/**
 * Prize Bond PK — Dynamic XML Sitemap
 * Access via: yourdomain.com/sitemap.php
 * Submit this URL to Google Search Console.
 */

// Same DB config as index.php — keep in sync
define('DB_HOST', 'localhost');
define('DB_NAME', 'prizebond_db');
define('DB_USER', 'db_username');
define('DB_PASS', 'db_password');

$site = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http')
      . '://' . $_SERVER['HTTP_HOST'];

$urls = [];

// ── Static pages ─────────────────────────────────────────────
$static = [
    ['', '1.0', 'daily'],
    ['?page=search', '0.9', 'weekly'],
    ['?page=schedule', '0.9', 'weekly'],
    ['?page=about', '0.5', 'monthly'],
    ['?page=contact', '0.5', 'monthly'],
    ['?page=privacy', '0.3', 'monthly'],
    ['?page=terms', '0.3', 'monthly'],
];
foreach ($static as [$path, $pri, $freq]) {
    $urls[] = ['loc' => $site . '/index.php' . $path, 'priority' => $pri, 'changefreq' => $freq, 'lastmod' => date('Y-m-d')];
}

// ── Bond category pages ───────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_OBJ]);

    $bond_types = $pdo->query("SELECT slug FROM pb_bond_types WHERE is_active=1")->fetchAll();
    foreach ($bond_types as $bt) {
        $urls[] = ['loc' => $site . '/index.php?page=bond&type=' . urlencode($bt->slug), 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')];
    }

    // ── Draw result pages ─────────────────────────────────────
    $draws = $pdo->query("SELECT id, draw_date FROM pb_draws WHERE status='published' ORDER BY draw_date DESC LIMIT 500")->fetchAll();
    foreach ($draws as $d) {
        $urls[] = ['loc' => $site . '/index.php?page=draw&id=' . $d->id, 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $d->draw_date];
    }
} catch (PDOException $e) {
    // DB not available — output static URLs only
}

// ── Output XML ────────────────────────────────────────────────
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
foreach ($urls as $url) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
    echo "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
    echo "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $url['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
