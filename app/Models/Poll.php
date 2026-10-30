<?php

namespace App\Models;

use App\Models\Concerns\HasUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poll extends Model
{
    use HasUser,
        HasFactory;

    protected $fillable = [
        'user_id',
        'feed_id',
        'option_index',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    protected function casts(): array
    {
        return [
            'option_index' => 'integer',
        ];
    }
}
