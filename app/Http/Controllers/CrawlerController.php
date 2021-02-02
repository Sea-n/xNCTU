<?php

namespace App\Http\Controllers;

use App\Models\Post;

class CrawlerController extends Controller
{
    public function sitemap()
    {
        header('Content-Type: text/xml');

        $posts = Post::where('status', '=', 5)->orderBy('id', 'desc')->get();

        echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>' . env('APP_URL') . '/</loc>
    <priority>1.00</priority>
  </url>
  <url>
	<loc>' . env('APP_URL') . '/posts</loc>
    <changefreq>hourly</changefreq>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>' . env('APP_URL') . '/submit</loc>
    <priority>1.00</priority>
  </url>
  <url>
    <loc>' . env('APP_URL') . '/review/DEMO</loc>
  </url>
  <url>
    <loc>' . env('APP_URL') . '/ranking</loc>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>' . env('APP_URL') . '/faq</loc>
  </url>
  <url>
    <loc>' . env('APP_URL') . '/deleted</loc>
  </url>
  <url>
    <loc>' . env('APP_URL') . '/policies</loc>
  </url>
  ';

        foreach ($posts as $post) {
            echo '<url><loc>' . env('APP_URL') . "/post/{$post->id}</loc></url>\n";
        }
        echo '</urlset>';
    }
}
