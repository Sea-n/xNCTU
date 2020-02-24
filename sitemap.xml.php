<?php
require_once('database.php');
$db = new MyDB();

header('Content-Type: text/xml');

$posts = $db->getPosts(50);
?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<url>
  <loc>https://x.nctu.app/</loc>
  <priority>1.00</priority>
</url>
<url>
  <loc>https://x.nctu.app/posts</loc>
  <priority>1.00</priority>
</url>
<url>
  <loc>https://x.nctu.app/submit</loc>
  <priority>1.00</priority>
</url>
<?php foreach ($posts as $post) { ?>
<url>
  <loc>https://x.nctu.app/post/<?= $post['id'] ?></loc>
  <priority>0.87</priority>
</url>
<?php } ?>
<url>
  <loc>https://x.nctu.app/faq</loc>
  <priority>0.69</priority>
</url>
<url>
  <loc>https://x.nctu.app/policies</loc>
  <priority>0.42</priority>
</url>
</urlset>
