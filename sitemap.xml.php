<?php
require_once('database.php');
$db = new MyDB();

header('Content-Type: text/xml');

$posts = $db->getPosts(0);
?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://<?= DOMAIN ?>/</loc>
    <priority>1.00</priority>
  </url>
  <url>
	<loc>https://<?= DOMAIN ?>/posts</loc>
    <changefreq>hourly</changefreq>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>https://<?= DOMAIN ?>/submit</loc>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>https://<?= DOMAIN ?>/review/DEMO</loc>
  </url>
  <url>
    <loc>https://<?= DOMAIN ?>/ranking</loc>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>https://<?= DOMAIN ?>/faq</loc>
  </url>
  <url>
    <loc>https://<?= DOMAIN ?>/deleted</loc>
  </url>
  <url>
    <loc>https://<?= DOMAIN ?>/policies</loc>
  </url>

<?php foreach ($posts as $post) { ?>
  <url><loc>https://<?= DOMAIN ?>/post/<?= $post['id'] ?></loc></url>
<?php } ?>
</urlset>
