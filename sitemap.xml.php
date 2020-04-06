<?php
require_once('database.php');
$db = new MyDB();

header('Content-Type: text/xml');

$posts = $db->getPosts(0);
?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://x.nctu.app/</loc>
    <priority>1.00</priority>
  </url>
  <url>
	<loc>https://x.nctu.app/posts</loc>
    <changefreq>hourly</changefreq>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>https://x.nctu.app/submit</loc>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>https://x.nctu.app/review/DEMO</loc>
  </url>
  <url>
    <loc>https://x.nctu.app/ranking</loc>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>https://x.nctu.app/faq</loc>
  </url>
  <url>
    <loc>https://x.nctu.app/deleted</loc>
  </url>
  <url>
    <loc>https://x.nctu.app/policies</loc>
  </url>

<?php foreach ($posts as $post) { ?>
  <url><loc>https://x.nctu.app/post/<?= $post['id'] ?></loc></url>
<?php } ?>
</urlset>
