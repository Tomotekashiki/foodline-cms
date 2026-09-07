<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Page;

Page::truncate();

$pages = [
    ["title" => "Home", "slug" => "/", "order" => 1, "seo_title" => "Home - Buffet.ge", "seo_description" => "Culinary Excellence for Your Special Moments", "is_active" => true],
    ["title" => "Menu", "slug" => "/menu", "order" => 2, "seo_title" => "Our Menu - Buffet.ge", "seo_description" => "Explore our curated menus and ready-made combos.", "is_active" => true],
    ["title" => "About", "slug" => "/about", "order" => 3, "seo_title" => "About Us - Buffet.ge", "seo_description" => "Learn more about our culinary journey and passion.", "is_active" => true],
    ["title" => "Contact", "slug" => "/contact", "order" => 4, "seo_title" => "Contact Us - Buffet.ge", "seo_description" => "Get in touch for your catering needs.", "is_active" => true],
];

foreach ($pages as $p) {
    Page::create($p);
}
echo "Pages seeded.\n";
