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
        return $items->map(function ($model) use ($formatImageUrl) {
            $arr = $model->toArray();
            if (isset($model->translatable)) {
                foreach ($model->translatable as $field) {
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

        $translations = Translation::all();
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
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=600');
    });

    Route::get('/menu-items', function (Request $request) use ($mapTranslations) {
        $allCategories = Category::all();
        $categoriesMap = [];
        foreach ($allCategories as $c) {
            $name = $c->name;
            $slug = $c->slug;
            $enName = $c->getTranslation('name', 'en');
            $kaName = $c->getTranslation('name', 'ka');
            if ($slug) {
                $categoriesMap[$slug] = $name;
            }
            if ($enName) {
                $categoriesMap[$enName] = $name;
            }
            if ($kaName) {
                $categoriesMap[$kaName] = $name;
            }
            $categoriesMap[$c->id] = $name;
        }

        $query = MenuItem::where('is_active', true);
        if ($request->has('furshet')) {
            $query->where('is_furshet', filter_var($request->query('furshet'), FILTER_VALIDATE_BOOLEAN));
        }

        $items = $query->get()->map(function ($item) use ($categoriesMap) {
            if (isset($categoriesMap[$item->category])) {
                $item->category = $categoriesMap[$item->category];
            }

            return $item;
        });

        return response()->json($mapTranslations($items))
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=600');
    });

    Route::get('/combos', function (Request $request) use ($formatImageUrl) {
        $locale = app()->getLocale();
        $allItems = MenuItem::all()->keyBy('id');

        $query = Combo::where('is_active', true);
        if ($request->has('furshet')) {
            $query->where('is_furshet', filter_var($request->query('furshet'), FILTER_VALIDATE_BOOLEAN));
        }

        $combos = $query->get()->map(function ($combo) use ($formatImageUrl, $locale, $allItems) {
            $arr = $combo->toArray();
            $arr['name'] = $combo->name;
            $arr['description'] = $combo->description;
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
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=600');
    });

    $verifyRecaptcha = function (?string $token, string $ip): bool {
        if (empty($token)) {
            return false;
        }
        $secretKey = config('services.recaptcha.secret', env('RECAPTCHA_SECRET_KEY'));
        if (empty($secretKey)) {
            Log::warning('RECAPTCHA_SECRET_KEY not configured.');

            return false;
        }
        try {
            $verifyRes = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $verifyRes->json('success');
        } catch (Throwable $e) {
            Log::warning('reCAPTCHA siteverify error: '.$e->getMessage());

            return false;
        }
    };

    Route::post('/bookings', function (Request $request) use ($verifyRecaptcha) {
        $setting = Setting::first();
        $minDays = (int) ($setting?->min_booking_days_ahead ?? 1);
        $disabledDates = $setting?->disabled_dates ?? [];

        $isProduction = app()->isProduction() || env('VERCEL') || env('APP_ENV') === 'production';
        $recaptchaRule = $isProduction ? 'required|string' : 'nullable|string';

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

        unset($validated['recaptcha_token']);
        $booking = Booking::create($validated);

        return response()->json($booking, 201);
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

        ContactMessage::create($validated);

        Log::info('New contact form submission: '.json_encode($validated));

        return response()->json([
            'success' => true,
            'message' => 'შეტყობინება წარმატებით გაიგზავნა',
        ], 200);
    })->middleware('throttle:10,1');

    Route::get('/pages', function () use ($mapTranslations) {
        return response()->json($mapTranslations(Page::where('is_active', true)->orderBy('order')->get()))
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=600');
    });

    Route::get('/settings', function () {
        $setting = Setting::first();

        return response()->json([
            'min_booking_days_ahead' => (int) ($setting?->min_booking_days_ahead ?? 1),
            'disabled_dates' => $setting?->disabled_dates ?? [],
        ])->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=600');
    });
});
