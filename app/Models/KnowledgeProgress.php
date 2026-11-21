<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeProgress extends Model
{
    protected $table = 'knowledge_progress';

    protected $fillable = [
        'user_id',
        'media_id',
        'position',
        'last_position',
        'completed_at',
        'last_seen_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'last_position' => 'integer',
            'completed_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
