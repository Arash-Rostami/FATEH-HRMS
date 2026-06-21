<?php

namespace App\Models;

use App\Services\ProfileDetailCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileDetail extends Model
{
    protected $fillable = [
        'profile_id',
        'section',
        'key',
        'value'
    ];

    public function getDisplayValueAttribute(): ?string
    {
        $def = ProfileDetailCatalog::definition($this->key);
        $type = $def['type'] ?? null;

        if ($type === 'select') {
            return $def['options'][$this->value] ?? $this->value;
        }

        if ($type === 'date' && filled($this->value)) {
            return toJalali($this->value, 'Y/m/d');
        }

        return $this->value;
    }

    public function getLabelAttribute(): string
    {
        return ProfileDetailCatalog::definition($this->key)['label'] ?? $this->key;
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
