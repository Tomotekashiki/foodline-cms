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
            'full_name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:150',
            'event_date' => 'required|date',
            'address' => 'required|string|max:300',
            'notes' => 'nullable|string|max:2000',
            'order_type' => 'nullable|string|max:50',
            'order_details' => 'nullable|array',
            'total_estimate' => 'nullable|numeric',
        ]);

        $dateOnly = substr($validated['event_date'], 0, 10);
        if (in_array($dateOnly, $disabledDates)) {
            return response()->json(['message' => 'The selected date is unavailable for booking.'], 422);
        }

        $booking = Booking::create($validated);
        return response()->json($booking, 201);
    })->middleware('throttle:10,1');

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

    Route::get('/internal/optimize-images', function (\Illuminate\Http\Request $request) {
        if ($request->query('key') !== 'foodline_secret_optimize_2026') {
            abort(403, 'Unauthorized');
        }

        $type = $request->query('type', 'menu_items');
        $limit = min(max((int) $request->query('limit', 10), 1), 20);
        $offset = max((int) $request->query('offset', 0), 0);

        $targetWidth = ($type === 'combos') ? 1000 : 800;
        $targetHeight = ($type === 'combos') ? 562 : 450;
        $targetRatio = 16 / 9;

        if ($type === 'combos') {
            $query = \App\Models\Combo::whereNotNull('image_url')->where('image_url', '!=', '');
        } else {
            $query = \App\Models\MenuItem::whereNotNull('image_url')->where('image_url', '!=', '');
        }

        $total = $query->count();
        $items = $query->orderBy('id')->skip($offset)->take($limit)->get();

        $results = [];
        $storage = \Illuminate\Support\Facades\Storage::disk(env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images');

        foreach ($items as $item) {
            $path = $item->image_url;
            if (empty($path)) continue;

            $cleanPath = preg_replace('#^https?://[^/]+/#', '', $path);
            $cleanPath = ltrim($cleanPath, '/');

            try {
                $url = $storage->url($cleanPath);
                $resp = \Illuminate\Support\Facades\Http::timeout(25)->get($url);
                if (!$resp->successful()) {
                    $results[] = [
                        'id' => $item->id,
                        'path' => $cleanPath,
                        'status' => 'download_failed',
                        'http_status' => $resp->status(),
                    ];
                    continue;
                }

                $rawContents = $resp->body();
                $originalSize = strlen($rawContents);

                $im = @imagecreatefromstring($rawContents);
                if (!$im) {
                    $results[] = [
                        'id' => $item->id,
                        'path' => $cleanPath,
                        'status' => 'image_create_failed',
                    ];
                    continue;
                }

                imagepalettetotruecolor($im);
                imagealphablending($im, true);
                imagesavealpha($im, true);

                $w = imagesx($im);
                $h = imagesy($im);
                $currentRatio = $w / $h;

                if ($w <= $targetWidth && abs($currentRatio - $targetRatio) < 0.05 && $originalSize < 75000 && str_ends_with(strtolower($cleanPath), '.webp')) {
                    imagedestroy($im);
                    $results[] = [
                        'id' => $item->id,
                        'path' => $cleanPath,
                        'status' => 'already_optimized',
                        'dimensions' => "{$w}x{$h}",
                        'size' => round($originalSize / 1024, 1) . 'KB',
                    ];
                    continue;
                }

                if ($currentRatio > $targetRatio) {
                    $cropH = $h;
                    $cropW = (int) round($h * $targetRatio);
                    $cropX = (int) round(($w - $cropW) / 2);
                    $cropY = 0;
                } else {
                    $cropW = $w;
                    $cropH = (int) round($w / $targetRatio);
                    $cropX = 0;
                    $cropY = (int) round(($h - $cropH) / 2);
                }

                $newW = min($targetWidth, $cropW);
                $newH = (int) round($newW / $targetRatio);

                $dest = imagecreatetruecolor($newW, $newH);
                imagealphablending($dest, false);
                imagesavealpha($dest, true);

                imagecopyresampled(
                    $dest, $im,
                    0, 0,
                    $cropX, $cropY,
                    $newW, $newH,
                    $cropW, $cropH
                );
                imagedestroy($im);

                ob_start();
                imagewebp($dest, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($dest);

                $newSize = strlen($webpData);

                $destPath = preg_replace('/\.[^.]+$/', '.webp', $cleanPath);

                $storage->put($destPath, $webpData, [
                    'visibility' => 'public',
                    'ContentType' => 'image/webp',
                ]);

                if ($destPath !== $item->image_url) {
                    $item->update(['image_url' => $destPath]);
                }

                $results[] = [
                    'id' => $item->id,
                    'path' => $destPath,
                    'status' => 'optimized',
                    'old_dimensions' => "{$w}x{$h}",
                    'new_dimensions' => "{$newW}x{$newH}",
                    'old_size' => round($originalSize / 1024, 1) . 'KB',
                    'new_size' => round($newSize / 1024, 1) . 'KB',
                    'savings' => round((1 - $newSize / $originalSize) * 100) . '%',
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'id' => $item->id,
                    'path' => $cleanPath,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $nextOffset = ($offset + $limit < $total) ? ($offset + $limit) : null;

        return response()->json([
            'type' => $type,
            'total' => $total,
            'processed_count' => count($results),
            'offset' => $offset,
            'next_offset' => $nextOffset,
            'results' => $results,
        ]);
    });
});