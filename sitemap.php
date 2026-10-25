<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');

$pdo = get_db();
$products = $pdo->query("SELECT id, updated_at FROM products ORDER BY id ASC")->fetchAll();
$staticPages = ['index.php', 'products.php', 'about.php', 'contact.php'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($staticPages as $page): ?>
    <url>
        <loc><?= clean(abs_url($page)) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($products as $p): ?>
    <url>
        <loc><?= clean(abs_url('product.php?id=' . (int)$p['id'])) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime((string)$p['updated_at']) ?: time()) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
</urlset>
