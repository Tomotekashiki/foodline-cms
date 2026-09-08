<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Combo extends Model {
    use HasTranslations;
    public array $translatable = ['name', 'description', 'inclusions'];
    protected $guarded = [];
    protected $casts = [
        'inclusions' => 'array',
        'menu_item_ids' => 'array',
        'is_furshet' => 'boolean',
    ];

    public function menuItems() {
        $ids = $this->menu_item_ids ?? [];
        if (empty($ids)) return collect();
        return MenuItem::whereIn('id', $ids)->get();
    }
}