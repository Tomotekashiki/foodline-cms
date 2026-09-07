<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

use SolutionForest\FilamentTree\Concern\ModelTree;

class Page extends Model {
    use HasTranslations;
    public array $translatable = ['title', 'seo_title', 'seo_description'];
    use ModelTree;
    public static function defaultParentKey() { return null; }
    protected $guarded = [];
    protected $casts = [
        'configs' => 'array',
        'is_active' => 'boolean',
    ];
    public function parent() { return $this->belongsTo(Page::class, 'parent_id'); }
    public function children() { return $this->hasMany(Page::class, 'parent_id'); }
}