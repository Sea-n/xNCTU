<?php

namespace App\Http\Controllers;

use App\Models\Post;

class CrawlerController extends Controller
{
    public function sitemap()
    {
        header('Content-Type: text/xml');

        $posts = Post::where('status', 5)->orderByDesc('id')->get();

        echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>' . url('/') . '</loc>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>' . url('/posts') . '</loc>
    <changefreq>hourly</changefreq>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>' . url('/submit') . '</loc>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>' . url('/review/DEMO') . '</loc>
  </url>
  <url>
    <loc>' . url('/ranking') . '</loc>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>' . url('/faq') . '</loc>
  </url>
  <url>
    <loc>' . url('/deleted') . '</loc>
  </url>
  <url>
    <loc>' . url('/policies') . '</loc>
  </url>
  ';

        foreach ($posts as $post) {
            echo '<url><loc>' . url("/post/{$post->id}</loc>") . "</url>\n";
        }
        echo '</urlset>';
    }
}
