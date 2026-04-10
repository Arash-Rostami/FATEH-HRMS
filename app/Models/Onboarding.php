<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Onboarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'welcome',
        'videos',
        'mission',
        'vision',
        'guides',
        'schedule',
        'extras',
        'is_active',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    protected function casts(): array
    {
        return [
            'videos' => AsCollection::class,
            'guides' => AsCollection::class,
            'extras' => AsArrayObject::class,
            'is_active' => 'boolean',
        ];
    }
}
