<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;
use App\Models\MenuItem;
use App\Models\Combo;
use App\Models\Booking;
use App\Models\Page;

Route::middleware(\App\Http\Middleware\SetLocale::class)->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    $formatImageUrl = function($image) {
        if (!$image) return null;
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            return preg_replace('/^https:\/\/store_([a-zA-Z0-9]+)\.public\.blob\.vercel-storage\.com\//', 'https://$1.public.blob.vercel-storage.com/', $image);
        }
        if (env('BLOB_READ_WRITE_TOKEN')) {
            return \Illuminate\Support\Facades\Storage::disk('vercel_blob')->url($image);
        }
        return rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/') . '/static/image/' . ltrim($image, '/');
    };

    $mapTranslations = function($items) use ($formatImageUrl) {
        return $items->map(function($model) use ($formatImageUrl) {
            $arr = $model->toArray();
            if (isset($model->translatable)) {
                foreach($model->translatable as $field) {
                    $arr[$field] = $model->{$field};
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
        $translations = \App\Models\Translation::all();
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
        return $result;
    });

    Route::get('/categories', function () use ($mapTranslations) {
        return $mapTranslations(\App\Models\Category::where('is_active', true)->orderBy('sort_order')->get());
    });

    Route::get('/menu-items', function (Request $request) use ($mapTranslations) {
        $categories = \App\Models\Category::all()->keyBy(function($c) {
            return $c->getTranslation('name', 'en') ?: $c->slug;
        });

        $query = MenuItem::where('is_active', true);
        if ($request->has('furshet')) {
            $query->where('is_furshet', filter_var($request->query('furshet'), FILTER_VALIDATE_BOOLEAN));
        }

        $items = $query->get()->map(function($item) use ($categories) {
            if ($cat = ($categories[$item->category] ?? \App\Models\Category::where('slug', $item->category)->first())) {
                $item->category = $cat->name;
            }
            return $item;
        });

        return $mapTranslations($items);
    });

    Route::get('/combos', function (Request $request) use ($formatImageUrl) {
        $locale = app()->getLocale();
        $allItems = MenuItem::all()->keyBy('id');

        $query = Combo::where('is_active', true);
        if ($request->has('furshet')) {
            $query->where('is_furshet', filter_var($request->query('furshet'), FILTER_VALIDATE_BOOLEAN));
        }

        $combos = $query->get()->map(function($combo) use ($formatImageUrl, $locale, $allItems) {
            $arr = $combo->toArray();
            $arr['name'] = $combo->name;
            $arr['description'] = $combo->description;
            $arr['image_url'] = $formatImageUrl($combo->image_url);
            $arr['is_furshet'] = (bool) $combo->is_furshet;

            // Dynamically resolve inclusions from selected Menu Items
            $itemIds = $combo->menu_item_ids ?? [];
            if (!empty($itemIds) && is_array($itemIds)) {
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

        return response()->json($combos);
    });

    Route::post('/bookings', function (Request $request) {
        $setting = \App\Models\Setting::first();
        $minDays = (int) ($setting?->min_booking_days_ahead ?? 1);
        $disabledDates = $setting?->disabled_dates ?? [];

        $validated = $request->validate([
            'full_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'event_date' => 'required|date',
            'address' => 'required|string',
            'notes' => 'nullable|string',
            'order_type' => 'nullable|string',
            'order_details' => 'nullable|array',
            'total_estimate' => 'nullable|numeric',
        ]);

        $dateOnly = substr($validated['event_date'], 0, 10);
        if (in_array($dateOnly, $disabledDates)) {
            return response()->json(['message' => 'The selected date is unavailable for booking.'], 422);
        }

        $booking = Booking::create($validated);
        return response()->json($booking, 201);
    });

    Route::get('/pages', function () use ($mapTranslations) {
        return $mapTranslations(Page::where('is_active', true)->orderBy('order')->get());
    });

    Route::get('/settings', function () {
        $setting = \App\Models\Setting::first();
        return response()->json([
            'min_booking_days_ahead' => (int) ($setting?->min_booking_days_ahead ?? 1),
            'disabled_dates' => $setting?->disabled_dates ?? [],
        ]);
    });

    Route::get('/internal/migrate-images', function (Request $request) {
        if ($request->query('key') !== 'foodline_secret_migrate_2026') {
            abort(403);
        }

        $results = [];

        // 1. Menu Items
        $menuItems = MenuItem::whereNotNull('image_url')->get();
        foreach ($menuItems as $item) {
            $url = $item->image_url;
            if (empty($url) || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
                continue;
            }
            if (str_contains($url, 'vercel-storage.com')) {
                continue;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($url);

                if (!$response->successful()) {
                    $results[] = "Failed to download MenuItem {$item->id} ({$response->status()}): {$url}";
                    continue;
                }

                $content = $response->body();
                $img = @imagecreatefromstring($content);
                if (!$img) {
                    $results[] = "Failed to create image resource for MenuItem {$item->id}";
                    continue;
                }

                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);

                ob_start();
                imagewebp($img, null, 85);
                $webpData = ob_get_clean();
                imagedestroy($img);

                $ulid = (string) \Illuminate\Support\Str::ulid();
                $filename = "menu-items/{$ulid}.webp";

                \Illuminate\Support\Facades\Storage::disk('vercel_blob')->put($filename, $webpData);

                $item->image_url = $filename;
                $item->save();

                $results[] = "Migrated MenuItem {$item->id} -> {$filename}";
            } catch (\Throwable $e) {
                $results[] = "Error MenuItem {$item->id}: " . $e->getMessage();
            }
        }

        // 2. Combos
        $combos = Combo::whereNotNull('image_url')->get();
        foreach ($combos as $combo) {
            $url = $combo->image_url;
            if (empty($url) || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
                continue;
            }
            if (str_contains($url, 'vercel-storage.com')) {
                continue;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($url);

                if (!$response->successful()) {
                    $results[] = "Failed to download Combo {$combo->id} ({$response->status()}): {$url}";
                    continue;
                }

                $content = $response->body();
                $img = @imagecreatefromstring($content);
                if (!$img) {
                    $results[] = "Failed to create image resource for Combo {$combo->id}";
                    continue;
                }

                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);

                ob_start();
                imagewebp($img, null, 85);
                $webpData = ob_get_clean();
                imagedestroy($img);

                $ulid = (string) \Illuminate\Support\Str::ulid();
                $filename = "combos/{$ulid}.webp";

                \Illuminate\Support\Facades\Storage::disk('vercel_blob')->put($filename, $webpData);

                $combo->image_url = $filename;
                $combo->save();

                $results[] = "Migrated Combo {$combo->id} -> {$filename}";
            } catch (\Throwable $e) {
                $results[] = "Error Combo {$combo->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'status' => 'success',
            'results' => $results
        ]);
    });
});