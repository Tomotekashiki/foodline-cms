<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'text' => 'array',
    ];

    public function getTranslation(string $attribute = 'text', string $locale = 'ka'): ?string
    {
        $data = $this->text;
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        return $data[$locale] ?? null;
    }

    public function getTranslations(string $attribute = 'text'): array
    {
        $data = $this->text;
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        return is_array($data) ? $data : [];
    }

    public function setTranslations(string $attribute, array $translations): self
    {
        $this->text = $translations;
        return $this;
    }
}