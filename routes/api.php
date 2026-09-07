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
            return $image;
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

    Route::get('/menu-items', function () use ($mapTranslations) {
        $categories = \App\Models\Category::all()->keyBy(function($c) {
            return $c->getTranslation('name', 'en') ?: $c->slug;
        });

        $items = MenuItem::where('is_active', true)->get()->map(function($item) use ($categories) {
            if ($cat = ($categories[$item->category] ?? \App\Models\Category::where('slug', $item->category)->first())) {
                $item->category = $cat->name;
            }
            return $item;
        });

        return $mapTranslations($items);
    });

    Route::get('/combos', function () use ($formatImageUrl) {
        $locale = app()->getLocale();
        $allItems = MenuItem::all()->keyBy('id');

        $combos = Combo::where('is_active', true)->get()->map(function($combo) use ($formatImageUrl, $locale, $allItems) {
            $arr = $combo->toArray();
            $arr['name'] = $combo->name;
            $arr['description'] = $combo->description;
            $arr['image_url'] = $formatImageUrl($combo->image_url);

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
});