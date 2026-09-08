<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
class MenuItem extends Model {
    use HasTranslations;
    public array $translatable = ['name', 'description'];
    protected $guarded = [];

    protected $casts = [
        'is_furshet' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];
}