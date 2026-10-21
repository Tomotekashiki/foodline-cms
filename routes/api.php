<?php

use App\Http\Middleware\SetLocale;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Combo;
use App\Models\ContactMessage;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(SetLocale::class)->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    $formatImageUrl = function ($image) {
        if (! $image) {
            return null;
        }
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            return preg_replace('/^https:\/\/store_([a-zA-Z0-9]+)\.public\.blob\.vercel-storage\.com\//', 'https://$1.public.blob.vercel-storage.com/', $image);
        }
        if (env('BLOB_READ_WRITE_TOKEN')) {
            return Storage::disk('vercel_blob')->url($image);
        }

        return rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/').'/static/image/'.ltrim($image, '/');
    };

    $mapTranslations = function ($items) use ($formatImageUrl) {
        $locale = app()->getLocale();

        return $items->map(function ($model) use ($formatImageUrl, $locale) {
            $arr = $model->toArray();
            if (isset($model->translatable)) {
                foreach ($model->translatable as $field) {
                    $val = $model->{$field};
                    if ((empty($val) || $val === '') && method_exists($model, 'getTranslation')) {
                        $val = $model->getTranslation($field, $locale, false)
                            ?: $model->getTranslation($field, 'ka', false)
                            ?: $model->getTranslation($field, 'en', false)
                            ?: $model->getTranslation($field, 'ru', false)
                            ?: '';
                    }
                    $arr[$field] = $val;
                }
            }
            if (isset($arr['image_url'])) {
                $arr['image_url'] = $formatImageUrl($arr['image_url']);
            }
            if (isset($arr['seo_image'])) {
                $arr['seo_image'] = $formatImageUrl($arr['seo_image']);
            }

            return $arr;
        });
    };

    Route::get('/translations', function () {
        $badge = Translation::where('key', 'menu_step2_badge')->first();
        if ($badge && $badge->getTranslation('text', 'ka') === 'ნაბიჯი 2 / 3') {
            try {
                $badge->text = [
                    'ka' => 'ნაბიჯი 2 / 4',
                    'en' => 'Step 2 of 4',
                    'ru' => 'Шаг 2 из 4',
                ];
                $badge->save();
            } catch (Throwable $e) {
            }
        }

        $newKeys = [
            'min_qty_badge' => [
                'group' => 'Menu & Services',
                'text' => [
                    'ka' => 'მინ. {count} ცალი',
                    'en' => 'Min. {count} pcs',
                    'ru' => 'Мин. {count} шт.',
                ],
            ],
            'min_order_warning' => [
                'group' => 'Booking Page',
                'text' => [
                    'ka' => 'მინიმალური შეკვეთა: ₾ {min} (აკლია ₾ {diff})',
                    'en' => 'Minimum order: ₾ {min} (₾ {diff} more needed)',
                    'ru' => 'Минимальный заказ: ₾ {min} (не хватает ₾ {diff})',
                ],
            ],
            'min_order_booking_error' => [
                'group' => 'Booking Page',
                'text' => [
                    'ka' => 'ჯავშნის გასაფორმებლად მინიმალური შეკვეთის თანხაა ₾ {min}. გთხოვთ დაამატოთ პროდუქტები კიდევ ₾ {diff}-ის ოდენობით.',
                    'en' => 'Minimum order amount is ₾ {min}. Please add ₾ {diff} more to complete your booking.',
                    'ru' => 'Минимальная сумма заказа — ₾ {min}. Пожалуйста, добавьте позиции еще на ₾ {diff} для оформления брони.',
                ],
            ],
        ];
        foreach ($newKeys as $k => $v) {
            try {
                Translation::firstOrCreate(['key' => $k], $v);
            } catch (Throwable $e) {
            }
        }

        $translations = Translation::whereNotIn('key', [
            \App\Services\SortOrderStore::STORAGE_KEY,
            \App\Services\PageSeoSync::SYNC_KEY,
        ])->get();
        $result = [
            'ka' => [],
            'en' => [],
            'ru' => [],
        ];
        foreach ($translations as $t) {
            $result['ka'][$t->key] = $t->getTranslation('text', 'ka');
            $result['en'][$t->key] = $t->getTranslation('text', 'en');
            $result['ru'][$t->key] = $t->getTranslation('text', 'ru');
        }

        if (isset($result['ka']['menu_step2_badge']) && str_contains($result['ka']['menu_step2_badge'], '2 / 3')) {
            $result['ka']['menu_step2_badge'] = 'ნაბიჯი 2 / 4';
            $result['en']['menu_step2_badge'] = 'Step 2 of 4';
            $result['ru']['menu_step2_badge'] = 'Шаг 2 из 4';
        }

        return response()->json($result)
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=600');
    });

    Route::get('/categories', function () use ($mapTranslations) {
        return response()->json($mapTranslations(Category::where('is_active', true)->orderBy('sort_order')->get()))
            ->header('Cache-Control', 'public, max-age=0, s-maxage=15, stale-while-revalidate=30');
    });

    Route::get('/menu-items', function (Request $request) use ($mapTranslations) {
        $allCategories = Category::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        $categoriesMap = [];
        $categoryOrderMap = [];
        foreach ($allCategories as $index => $c) {
            $name = $c->name;
            $slug = $c->slug;
            $enName = $c->getTranslation('name', 'en');
            $kaName = $c->getTranslation('name', 'ka');
            $sortVal = ((int) ($c->sort_order ?? 0)) * 1000 + $index;
            if ($slug) {
                $categoriesMap[$slug] = $name;
                $categoryOrderMap[$slug] = $sortVal;
            }
            if ($enName) {
                $categoriesMap[$enName] = $name;
                $categoryOrderMap[$enName] = $sortVal;
            }
            if ($kaName) {
                $categoriesMap[$kaName] = $name;
                $categoryOrderMap[$kaName] = $sortVal;
            }
            $categoriesMap[$c->id] = $name;
            $categoryOrderMap[$c->id] = $sortVal;
            if ($name) {
                $categoryOrderMap[$name] = $sortVal;
            }
        }

        $query = MenuItem::where('is_active', true)->orderBy('id', 'asc');
        if ($request->has('furshet')) {
            $query->where('is_furshet', filter_var($request->query('furshet'), FILTER_VALIDATE_BOOLEAN));
        }

        $items = $query->get()->map(function ($item) use ($categoriesMap) {
            if (isset($categoriesMap[$item->category])) {
                $item->category = $categoriesMap[$item->category];
            }

            return $item;
        })->sortBy(function ($item) use ($categoryOrderMap) {
            $catOrder = $categoryOrderMap[$item->category] ?? 999999;
            $itemOrder = (int) ($item->sort_order ?? 0);

            return sprintf('%08d_%08d_%08d', $catOrder, $itemOrder + 1000000, $item->id);
        })->values();

        return response()->json($mapTranslations($items))
            ->header('Cache-Control', 'public, max-age=0, s-maxage=15, stale-while-revalidate=30');
    });

    Route::get('/combos', function (Request $request) use ($formatImageUrl) {
        $locale = app()->getLocale();
        $allItems = MenuItem::select('id', 'name', 'price', 'image_url', 'category')
            ->orderBy('id', 'asc')
            ->get()
            ->keyBy('id');

        $query = Combo::where('is_active', true)->orderBy('id', 'asc');
        if ($request->has('furshet')) {
            $query->where('is_furshet', filter_var($request->query('furshet'), FILTER_VALIDATE_BOOLEAN));
        }

        $combos = $query->get()
            ->sortBy(fn ($combo) => sprintf('%08d_%08d', ((int) ($combo->sort_order ?? 0)) + 1000000, $combo->id))
            ->values()
            ->map(function ($combo) use ($formatImageUrl, $locale, $allItems) {
            $kaDesc = $combo->getTranslation('description', 'ka', false);
            $ruDesc = $combo->getTranslation('description', 'ru', false);
            if (empty($kaDesc) && ! empty($ruDesc) && preg_match('/[\x{10A0}-\x{10FF}]/u', $ruDesc)) {
                try {
                    $combo->setTranslation('description', 'ka', $ruDesc);
                    $combo->save();
                } catch (\Throwable $e) {
                }
            }

            $arr = $combo->toArray();
            $arr['name'] = $combo->getTranslation('name', $locale, false)
                ?: $combo->getTranslation('name', 'ka', false)
                ?: $combo->getTranslation('name', 'en', false)
                ?: $combo->getTranslation('name', 'ru', false)
                ?: '';
            $arr['description'] = $combo->getTranslation('description', $locale, false)
                ?: $combo->getTranslation('description', 'ka', false)
                ?: $combo->getTranslation('description', 'en', false)
                ?: $combo->getTranslation('description', 'ru', false)
                ?: '';
            $arr['image_url'] = $formatImageUrl($combo->image_url);
            $arr['is_furshet'] = (bool) $combo->is_furshet;

            // Dynamically resolve inclusions from selected Menu Items
            $itemIds = $combo->menu_item_ids ?? [];
            if (! empty($itemIds) && is_array($itemIds)) {
                $inclusions = [];
                $includedItems = [];
                foreach ($itemIds as $id) {
                    if ($item = ($allItems[$id] ?? null)) {
                        $name = $item->getTranslation('name', $locale) ?: $item->name;
                        $inclusions[] = $name;
                        $includedItems[] = [
                            'id' => $item->id,
                            'name' => $name,
                            'price' => $item->price,
                            'image' => $formatImageUrl($item->image_url),
                            'category' => $item->category,
                        ];
                    }
                }
                $arr['inclusions'] = $inclusions;
                $arr['items'] = $includedItems;
            } else {
                $arr['inclusions'] = $combo->inclusions ?: [];
            }

            return $arr;
        });

        return response()->json($combos)
            ->header('Cache-Control', 'public, max-age=0, s-maxage=15, stale-while-revalidate=30');
    });

    $verifyRecaptcha = function (?string $token, string $ip): bool {
        if (empty($token)) {
            return false;
        }
        $secretKey = config('services.recaptcha.secret') ?: env('RECAPTCHA_SECRET_KEY');
        if (empty($secretKey)) {
            Log::warning('RECAPTCHA_SECRET_KEY not configured.');

            return false;
        }
        try {
            $verifyRes = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
            ]);

            return (bool) $verifyRes->json('success');
        } catch (Throwable $e) {
            Log::warning('reCAPTCHA siteverify error: '.$e->getMessage());

            return false;
        }
    };

    Route::post('/bookings', function (Request $request) use ($verifyRecaptcha) {
        $setting = Setting::first();
        $minDays = max(0, (int) ($setting?->min_booking_days_ahead ?? 1));
        $disabledDates = $setting?->disabled_dates ?? [];
        $earliestDate = now('Asia/Tbilisi')->startOfDay()->addDays($minDays)->toDateString();

        $isProduction = app()->isProduction() || env('VERCEL') || env('APP_ENV') === 'production';
        $recaptchaRule = $isProduction ? 'required|string' : 'nullable|string';

        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:150',
            'event_date' => 'required|date|after_or_equal:'.$earliestDate,
            'address' => 'required|string|max:300',
            'notes' => 'nullable|string|max:2000',
            'order_details' => 'required|array',
            'order_details.custom_items' => 'nullable|array|max:200',
            'order_details.custom_items.*.id' => 'required|integer',
            'order_details.custom_items.*.qty' => 'required|integer|min:1|max:10000',
            'order_details.combos' => 'nullable|array|max:50',
            'order_details.combos.*.id' => 'required|integer',
            'order_details.combos.*.qty' => 'required|integer|min:1|max:10000',
            'recaptcha_token' => $recaptchaRule,
        ]);

        if ($isProduction || ! empty($validated['recaptcha_token'])) {
            if (! $verifyRecaptcha($validated['recaptcha_token'] ?? null, $request->ip())) {
                return response()->json(['message' => 'reCAPTCHA შემოწმება ვერ მოხერხდა. გთხოვთ სცადოთ თავიდან.'], 422);
            }
        }

        $dateOnly = substr($validated['event_date'], 0, 10);
        if (in_array($dateOnly, $disabledDates)) {
            return response()->json(['message' => 'The selected date is unavailable for booking.'], 422);
        }

        // Rebuild order lines from the database so names and prices cannot be forged by the client.
        $requestedItems = collect($validated['order_details']['custom_items'] ?? [])
            ->groupBy('id')->map(fn ($rows) => (int) $rows->sum('qty'));
        $requestedCombos = collect($validated['order_details']['combos'] ?? [])
            ->groupBy('id')->map(fn ($rows) => (int) $rows->sum('qty'));

        $menuItems = MenuItem::where('is_active', true)->whereIn('id', $requestedItems->keys())->get()->keyBy('id');
        $combos = Combo::where('is_active', true)->whereIn('id', $requestedCombos->keys())->get()->keyBy('id');

        if ($menuItems->count() !== $requestedItems->count() || $combos->count() !== $requestedCombos->count()) {
            return response()->json(['message' => 'One or more selected items are no longer available.'], 422);
        }
        if ($requestedItems->isEmpty() && $requestedCombos->isEmpty()) {
            return response()->json(['message' => 'No items selected.'], 422);
        }

        foreach ($requestedItems as $id => $qty) {
            $item = $menuItems[$id] ?? null;
            if ($item && $qty < $item->min_qty) {
                return response()->json([
                    'message' => "პროდუქტის ({$item->name}) მინიმალური რაოდენობაა {$item->min_qty}.",
                ], 422);
            }
        }

        $customLines = $requestedItems->map(function (int $qty, $id) use ($menuItems) {
            $item = $menuItems[$id];

            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'qty' => $qty,
                'category' => is_object($item->category) ? ($item->category->name ?? null) : $item->category,
            ];
        })->values()->all();

        $comboLines = $requestedCombos->map(function (int $qty, $id) use ($combos) {
            $combo = $combos[$id];

            return [
                'id' => $combo->id,
                'name' => $combo->name,
                'price' => (float) $combo->price_per_guest,
                'qty' => $qty,
                'for_guests' => (int) $combo->min_guests,
            ];
        })->values()->all();

        $total = collect($customLines)->sum(fn ($l) => $l['price'] * $l['qty'])
            + collect($comboLines)->sum(fn ($l) => $l['price'] * $l['qty']);

        $minOrderAmount = Setting::getMinOrderAmount();
        if ($minOrderAmount > 0 && round($total, 2) < $minOrderAmount) {
            return response()->json([
                'message' => "შეკვეთის მინიმალური თანხაა ₾ {$minOrderAmount}.",
            ], 422);
        }

        $booking = Booking::create([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'event_date' => $validated['event_date'],
            'address' => $validated['address'],
            'notes' => $validated['notes'] ?? null,
            'order_type' => $customLines && $comboLines ? 'mixed' : ($comboLines ? 'combo' : 'custom'),
            'order_details' => [
                'custom_items' => $customLines,
                'combos' => $comboLines,
                'combo' => $comboLines[0] ?? null,
            ],
            'total_estimate' => round($total, 2),
        ]);

        return response()->json([
            'success' => true,
            'id' => $booking->id,
            'total_estimate' => $booking->total_estimate,
        ], 201);
    })->middleware('throttle:10,1');

    Route::post('/contact', function (Request $request) use ($verifyRecaptcha) {
        $isProduction = app()->isProduction() || env('VERCEL') || env('APP_ENV') === 'production';
        $recaptchaRule = $isProduction ? 'required|string' : 'nullable|string';

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
            'recaptcha_token' => $recaptchaRule,
        ]);

        if ($isProduction || ! empty($validated['recaptcha_token'])) {
            if (! $verifyRecaptcha($validated['recaptcha_token'] ?? null, $request->ip())) {
                return response()->json(['message' => 'reCAPTCHA შემოწმება ვერ მოხერხდა. გთხოვთ სცადოთ თავიდან.'], 422);
            }
        }

        unset($validated['recaptcha_token']);
        $validated['status'] = 'new';
        $validated['ip_address'] = $request->ip();

        $message = ContactMessage::create($validated);

        Log::info('New contact form submission ID: '.$message->id);

        return response()->json([
            'success' => true,
            'message' => 'შეტყობინება წარმატებით გაიგზავნა',
        ], 200);
    })->middleware('throttle:10,1');

    Route::get('/pages', function () use ($mapTranslations) {
        \App\Services\PageSeoSync::ensureSynced();

        return response()->json($mapTranslations(Page::where('is_active', true)->orderBy('order')->get()))
            ->header('Cache-Control', 'public, max-age=0, s-maxage=15, stale-while-revalidate=30');
    });

    Route::get('/settings', function () {
        $setting = Setting::first();

        return response()->json([
            'min_booking_days_ahead' => (int) ($setting?->min_booking_days_ahead ?? 1),
            'min_order_amount' => (float) Setting::getMinOrderAmount(),
            'disabled_dates' => $setting?->disabled_dates ?? [],
        ])->header('Cache-Control', 'public, max-age=0, s-maxage=15, stale-while-revalidate=30');
    });

    Route::match(['get', 'post'], '/cron/clean-tmp', function (Request $request) {
        $cronSecret = env('CRON_SECRET');
        $authHeader = $request->header('Authorization');

        // Check Vercel Cron header or CRON_SECRET token if configured
        if (! empty($cronSecret)) {
            $isAuthorized = ($authHeader === "Bearer {$cronSecret}")
                || ($request->query('key') === $cronSecret);

            if (! $isAuthorized) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $hours = (int) $request->input('hours', 2);
        $cutoff = now()->subHours($hours)->timestamp;

        $token = env('BLOB_READ_WRITE_TOKEN');
        $deletedBlobs = [];
        if (! empty($token)) {
            $deletedBlobs = \App\Console\Commands\CleanStorageTemp::cleanVercelBlobWithDetails($token, $cutoff);
        }

        return response()->json([
            'success' => true,
            'message' => 'Temporary storage cleanup finished',
            'cutoff' => date('Y-m-d H:i:s', $cutoff),
            'deleted_vercel_blobs' => count($deletedBlobs),
            'urls' => $deletedBlobs,
        ]);
    });

    Route::match(['get', 'post'], '/clear-cache', function () {
        \App\Services\PageSeoSync::ensureSynced();
        try {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
        } catch (\Throwable $e) {
        }

        foreach (['/tmp/config.php', '/tmp/routes.php', '/tmp/events.php', '/tmp/services.php'] as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Application cache cleared successfully',
            'timestamp' => now('Asia/Tbilisi')->toDateTimeString(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    })->middleware('throttle:10,1');
});
