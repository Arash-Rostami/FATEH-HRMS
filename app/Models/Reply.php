<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\Hooks\HasReplyHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reply extends Model
{
    use HasFactory,
        HasPublicAssetUrl,
        CleansAttachedFiles,
        HasUser,
        HasReplyHooks;

    protected $fillable = [
        'repliable_type',
        'repliable_id',
        'user_id',
        'body',
        'files',
        'type',
        'payload',
    ];

    public function repliable(): MorphTo
    {
        return $this->morphTo();
    }

    public function projectId(): ?int
    {
        return match ($this->repliable_type) {
            Project::class => $this->repliable_id,
            Task::class => $this->repliable?->project_id,
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'files' => 'array',
            'payload' => 'array',
            'reactions' => 'array',
            'type' => TaskActivityType::class,
        ];
    }
}
