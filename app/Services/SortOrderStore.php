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
                return static::$cache = ['menu_item' => [], 'combo' => []];
            }

            $raw = $record->getTranslation('text', 'en')
                ?: $record->getTranslation('text', 'ka');

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return static::$cache = [
                        'menu_item' => is_array($decoded['menu_item'] ?? null) ? $decoded['menu_item'] : [],
                        'combo' => is_array($decoded['combo'] ?? null) ? $decoded['combo'] : [],
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        return static::$cache = ['menu_item' => [], 'combo' => []];
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
