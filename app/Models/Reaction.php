<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'feed_id',
        'emoji',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function emoji(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ($value && ctype_xdigit($value)) ? hex2bin($value) : $value,
            set: fn (?string $value) => $value ? bin2hex($value) : null,
        );
    }
}
