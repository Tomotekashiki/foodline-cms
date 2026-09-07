<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Page;

$menuPage = Page::where("slug", "/menu")->first();
if ($menuPage && Page::where("slug", "/menu/custom")->count() == 0) {
    Page::create([
        "title" => "Build Custom Menu", 
        "slug" => "/menu/custom", 
        "parent_id" => $menuPage->id,
        "order" => 1, 
        "seo_title" => "Build Custom Menu - Buffet.ge",
        "is_active" => true
    ]);
    Page::create([
        "title" => "Ready-Made Combos", 
        "slug" => "/menu/combos", 
        "parent_id" => $menuPage->id,
        "order" => 2, 
        "seo_title" => "Catering Packages - Buffet.ge",
        "is_active" => true
    ]);
    echo "Subpages seeded.\n";
} else {
    echo "Already seeded.\n";
}
