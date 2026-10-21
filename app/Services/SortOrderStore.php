<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;

class SortOrderStore
{
    public const STORAGE_KEY = '__entity_sort_orders';

    protected static ?array $cache = null;

    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            $record = Translation::where('key', static::STORAGE_KEY)->first();
            if (! $record) {
                return static::$cache = [
                    'menu_item' => [],
                    'combo' => [],
                    'min_qty' => [],
                    'min_order_amount' => 0,
                ];
            }

            $raw = $record->getTranslation('text', 'en')
                ?: $record->getTranslation('text', 'ka');

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return static::$cache = [
                        'menu_item' => is_array($decoded['menu_item'] ?? null) ? $decoded['menu_item'] : [],
                        'combo' => is_array($decoded['combo'] ?? null) ? $decoded['combo'] : [],
                        'min_qty' => is_array($decoded['min_qty'] ?? null) ? $decoded['min_qty'] : [],
                        'min_order_amount' => isset($decoded['min_order_amount']) ? max(0, (float) $decoded['min_order_amount']) : 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        return static::$cache = [
            'menu_item' => [],
            'combo' => [],
            'min_qty' => [],
            'min_order_amount' => 0,
        ];
    }

    public static function getMap(string $type): array
    {
        $all = static::all();

        return $all[$type] ?? [];
    }

    public static function get(string $type, int|string|null $id): int
    {
        if ($id === null) {
            return 0;
        }
        $map = static::getMap($type);

        return (int) ($map[(string) $id] ?? 0);
    }

    public static function set(string $type, int|string $id, int $sortOrder): void
    {
        $all = static::all();
        $all[$type][(string) $id] = $sortOrder;
        static::$cache = $all;
        static::persist($all);
    }

    public static function getMinQty(int|string|null $id): int
    {
        if ($id === null) {
            return 1;
        }
        $all = static::all();
        $val = (int) ($all['min_qty'][(string) $id] ?? 1);

        return max(1, $val);
    }

    public static function setMinQty(int|string $id, int $minQty): void
    {
        $all = static::all();
        if (! isset($all['min_qty']) || ! is_array($all['min_qty'])) {
            $all['min_qty'] = [];
        }
        $all['min_qty'][(string) $id] = max(1, $minQty);
        static::$cache = $all;
        static::persist($all);
    }

    public static function getMinOrderAmount(): float
    {
        $all = static::all();

        return max(0, (float) ($all['min_order_amount'] ?? 0));
    }

    public static function setMinOrderAmount(float|int $amount): void
    {
        $all = static::all();
        $all['min_order_amount'] = max(0, round((float) $amount, 2));
        static::$cache = $all;
        static::persist($all);
    }

    public static function reorder(string $type, array $orderedIds): void
    {
        $all = static::all();
        if (! isset($all[$type]) || ! is_array($all[$type])) {
            $all[$type] = [];
        }

        foreach (array_values($orderedIds) as $index => $id) {
            $all[$type][(string) $id] = $index + 1;
        }

        static::$cache = $all;
        static::persist($all);
    }

    protected static function persist(array $all): void
    {
        try {
            $json = json_encode($all, JSON_UNESCAPED_UNICODE);
            $record = Translation::firstOrNew(['key' => static::STORAGE_KEY]);
            $record->group = '_system';
            $record->text = [
                'ka' => $json,
                'en' => $json,
                'ru' => $json,
            ];
            $record->save();
        } catch (\Throwable $e) {
        }
    }

    public static function applyOrder(Builder $query, string $type, string $direction = 'asc'): Builder
    {
        $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $map = static::getMap($type);

        if (empty($map)) {
            return $query->orderBy('id', $dir);
        }

        $cases = [];
        foreach ($map as $id => $order) {
            $safeId = (int) $id;
            $safeOrder = (int) $order;
            if ($safeId > 0) {
                $cases[] = "WHEN {$safeId} THEN {$safeOrder}";
            }
        }

        if (empty($cases)) {
            return $query->orderBy('id', $dir);
        }

        $caseSql = 'CASE id '.implode(' ', $cases).' ELSE 0 END';

        return $query->orderByRaw("{$caseSql} {$dir}")->orderBy('id', 'asc');
    }
}
