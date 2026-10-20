<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Translation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PageSeoSync
{
    public const SYNC_KEY = '_seo_pages_synced_v1';

    public static function ensureSynced(bool $force = false): void
    {
        try {
            if (! $force && Translation::where('key', self::SYNC_KEY)->exists()) {
                return;
            }

            $imagesToUpload = [
                'pages/hero-home.webp' => 'https://foodline.ge/images/hero-home.webp',
                'pages/about-hero.webp' => 'https://foodline.ge/images/about-hero.webp',
                'pages/contact-map.webp' => 'https://foodline.ge/images/contact-map.webp',
            ];

            $diskName = env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images';
            $disk = Storage::disk($diskName);

            foreach ($imagesToUpload as $targetPath => $sourceUrl) {
                try {
                    $response = Http::timeout(8)->get($sourceUrl);
                    if ($response->successful() && ! empty($response->body())) {
                        $disk->put($targetPath, $response->body(), [
                            'visibility' => 'public',
                            'ContentType' => 'image/webp',
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Best effort upload
                }
            }

            $menuParent = Page::where('slug', '/menu')->first();
            $menuParentId = $menuParent ? $menuParent->id : 2;

            $pagesConfig = [
                '/' => [
                    'order' => 1,
                    'parent_id' => null,
                    'configs' => [],
                    'title' => [
                        'ka' => 'მთავარი',
                        'en' => 'Home',
                        'ru' => 'Главная',
                    ],
                    'seo_title' => [
                        'ka' => 'Foodline.ge — პრემიუმ ქეითერინგ სერვისი',
                        'en' => 'Foodline.ge — Premium Catering Service',
                        'ru' => 'Foodline.ge — Премиум кейтеринг сервис',
                    ],
                    'seo_description' => [
                        'ka' => 'პროფესიონალური ქეითერინგის მომსახურება საქართველოში — ავთენტური გემოები და უნაკლო სერვისი თქვენი ყველაზე გამორჩეული სტუმრებისთვის.',
                        'en' => 'Professional catering services in Georgia — authentic flavors and impeccable service for your most distinguished guests.',
                        'ru' => 'Профессиональные кейтеринговые услуги в Грузии — аутентичные вкусы и безупречный сервис для ваших самых важных гостей.',
                    ],
                    'seo_image' => 'pages/hero-home.webp',
                ],
                '/menu' => [
                    'order' => 2,
                    'parent_id' => null,
                    'configs' => ['header-menu', 'footer-menu'],
                    'title' => [
                        'ka' => 'მენიუ',
                        'en' => 'Menu',
                        'ru' => 'Меню',
                    ],
                    'seo_title' => [
                        'ka' => 'აირჩიეთ მენიუს ტიპი - Foodline.ge',
                        'en' => 'Choose Your Menu Style - Foodline.ge',
                        'ru' => 'Выберите тип меню - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'შექმენით ინდივიდუალური მენიუ თქვენი გემოვნებით ან აირჩიეთ ჩვენი შეფ-მზარეულის მიერ შედგენილი მზა კომბო პაკეტები.',
                        'en' => 'Choose how you would like to build your catering experience. Select individual dishes or pick a curated package.',
                        'ru' => 'Создайте индивидуальное меню по своему вкусу или выберите готовые комбо-пакеты от нашего шеф-повара.',
                    ],
                    'seo_image' => 'pages/hero-home.webp',
                ],
                '/menu/georgian' => [
                    'order' => 1,
                    'parent_id' => $menuParentId,
                    'configs' => null,
                    'title' => [
                        'ka' => 'ქართული მენიუ',
                        'en' => 'Georgian Menu',
                        'ru' => 'Грузинское меню',
                    ],
                    'seo_title' => [
                        'ka' => 'ქართული მენიუ - Foodline.ge',
                        'en' => 'Georgian Menu - Foodline.ge',
                        'ru' => 'Грузинское меню - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'ტრადიციული ქართული სუფრის კერძები, მომზადებული უმაღლესი ხარისხის ადგილობრივი ინგრედიენტებითა და თანამედროვე პრეზენტაციით.',
                        'en' => 'Traditional Georgian feast dishes prepared with the finest local ingredients and modern presentation.',
                        'ru' => 'Традиционные блюда грузинского застолья, приготовленные из лучших местных ингредиентов с современной подачей.',
                    ],
                    'seo_image' => 'pages/hero-home.webp',
                ],
                '/menu/furshet' => [
                    'order' => 2,
                    'parent_id' => $menuParentId,
                    'configs' => null,
                    'title' => [
                        'ka' => 'ფურშეტის მენიუ',
                        'en' => 'Buffet Menu',
                        'ru' => 'Фуршетное меню',
                    ],
                    'seo_title' => [
                        'ka' => 'ბუფეტი (ფურშეტი) - Foodline.ge',
                        'en' => 'Buffet (Furshet) - Foodline.ge',
                        'ru' => 'Буфет (Фуршет) - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'ელეგანტური კანაპეები, მინი-სენდვიჩები და დახვეწილი წასახემსებლები კორპორატიული საღამოებისა და წვეულებებისთვის.',
                        'en' => 'Elegant canapés, mini-sandwiches, and refined finger foods for corporate evenings and receptions.',
                        'ru' => 'Элегантные канапе, мини-сэндвичи и изысканные закуски для корпоративных вечеров и приемов.',
                    ],
                    'seo_image' => 'pages/hero-home.webp',
                ],
                '/about' => [
                    'order' => 3,
                    'parent_id' => null,
                    'configs' => ['header-menu', 'footer-menu'],
                    'title' => [
                        'ka' => 'ჩვენს შესახებ',
                        'en' => 'About',
                        'ru' => 'О нас',
                    ],
                    'seo_title' => [
                        'ka' => 'ჩვენ შესახებ - Foodline.ge',
                        'en' => 'About Us - Foodline.ge',
                        'ru' => 'О нас - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'გაიცანით Foodline.ge — ტრადიციული ქართული სტუმართმოყვარეობისა და თანამედროვე კულინარიული ხელოვნების სინთეზი.',
                        'en' => 'Discover Foodline.ge — a synthesis of traditional Georgian hospitality and modern culinary artistry.',
                        'ru' => 'Познакомьтесь с Foodline.ge — синтезом традиционного грузинского гостеприимства и современного кулинарного искусства.',
                    ],
                    'seo_image' => 'pages/about-hero.webp',
                ],
                '/contact' => [
                    'order' => 4,
                    'parent_id' => null,
                    'configs' => ['header-menu', 'footer-menu'],
                    'title' => [
                        'ka' => 'კონტაქტი',
                        'en' => 'Contact',
                        'ru' => 'Контакты',
                    ],
                    'seo_title' => [
                        'ka' => 'კონტაქტი - Foodline.ge',
                        'en' => 'Contact Us - Foodline.ge',
                        'ru' => 'Контакты - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'დაგვიკავშირდით თქვენი შემდეგი ღონისძიების დასაგეგმად. ჩვენი გუნდი მზად არის შექმნას თქვენზე მორგებული მენიუ.',
                        'en' => 'Get in touch to plan your next event. Our team is ready to craft a bespoke menu tailored to your occasion.',
                        'ru' => 'Свяжитесь с нами, чтобы спланировать ваше следующее мероприятие. Наша команда готова создать меню специально для вас.',
                    ],
                    'seo_image' => 'pages/contact-map.webp',
                ],
                '/menu/custom' => [
                    'order' => 5,
                    'parent_id' => null,
                    'configs' => [],
                    'title' => [
                        'ka' => 'ინდივიდუალური მენიუ',
                        'en' => 'Build Custom Menu',
                        'ru' => 'Индивидуальное меню',
                    ],
                    'seo_title' => [
                        'ka' => 'ინდივიდუალური მენიუს შედგენა - Foodline.ge',
                        'en' => 'Build Custom Menu - Foodline.ge',
                        'ru' => 'Составить индивидуальное меню - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'თავად აარჩიეთ სასურველი კერძები და რაოდენობა ჩვენი მრავალფეროვანი კატალოგიდან.',
                        'en' => 'Handpick your preferred dishes and quantities from our extensive culinary catalog.',
                        'ru' => 'Самостоятельно выберите желаемые блюда и их количество из нашего разнообразного каталога.',
                    ],
                    'seo_image' => 'pages/hero-home.webp',
                ],
                '/menu/combos' => [
                    'order' => 6,
                    'parent_id' => null,
                    'configs' => [],
                    'title' => [
                        'ka' => 'მზა კომბოები',
                        'en' => 'Ready-Made Combos',
                        'ru' => 'Готовые комбо',
                    ],
                    'seo_title' => [
                        'ka' => 'მზა კომბო მენიუები - Foodline.ge',
                        'en' => 'Ready-Made Combo Menus - Foodline.ge',
                        'ru' => 'Готовые комбо-меню - Foodline.ge',
                    ],
                    'seo_description' => [
                        'ka' => 'ჩვენი შეფ-მზარეულის მიერ იდეალურად დაბალანსებული მზა მენიუები სტუმრების სხვადასხვა რაოდენობისთვის.',
                        'en' => 'Thoughtfully balanced catering packages designed by our executive chef for effortless event planning.',
                        'ru' => 'Идеально сбалансированные готовые меню от нашего шеф-повара для разного количества гостей.',
                    ],
                    'seo_image' => 'pages/hero-home.webp',
                ],
            ];

            foreach ($pagesConfig as $slug => $cfg) {
                $page = Page::where('slug', $slug)->first();
                if (! $page) {
                    Page::create([
                        'slug' => $slug,
                        'order' => $cfg['order'],
                        'parent_id' => $cfg['parent_id'],
                        'configs' => $cfg['configs'],
                        'is_active' => true,
                        'title' => $cfg['title'],
                        'seo_title' => $cfg['seo_title'],
                        'seo_description' => $cfg['seo_description'],
                        'seo_image' => $cfg['seo_image'],
                    ]);
                } else {
                    $page->setTranslations('seo_title', $cfg['seo_title']);
                    $page->setTranslations('seo_description', $cfg['seo_description']);
                    if (empty($page->seo_image) || str_contains((string) $page->seo_image, '01M20GJBEMRE9QHMC5FERK6DAJ')) {
                        $page->seo_image = $cfg['seo_image'];
                    }
                    $page->save();
                }
            }

            Translation::updateOrCreate(
                ['key' => self::SYNC_KEY],
                [
                    'group' => 'System',
                    'text' => [
                        'ka' => '1',
                        'en' => '1',
                        'ru' => '1',
                    ],
                ]
            );
        } catch (\Throwable $e) {
            // Fail gracefully without breaking request
        }
    }
}
