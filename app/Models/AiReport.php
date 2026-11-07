<?php

namespace App\Models;

use App\Models\Concerns\HasActive;
use App\Models\Concerns\HasDailyQuota;
use App\Models\Concerns\HasGenerationLifecycle;
use App\Models\Concerns\HasSharedRecipients;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\HasVersionHistory;
use App\Models\Concerns\Hooks\HasAiReportHooks;
use Illuminate\Database\Eloquent\Model;

class AiReport extends Model
{
    use HasActive,
        HasDailyQuota,
        HasGenerationLifecycle,
        HasSharedRecipients,
        HasUser,
        HasVersionHistory,
        HasAiReportHooks;

    public const STATUS_GENERATING = 'generating', STATUS_READY = 'ready', STATUS_FAILED = 'failed';

    public const STATUS_COLORS = [self::STATUS_READY => 'success', self::STATUS_FAILED => 'danger'];

    public const STATUS_ICONS = [self::STATUS_READY => 'heroicon-o-check-circle', self::STATUS_FAILED => 'heroicon-o-x-circle'];

    public const LIST_COLUMNS = ['id', 'user_id', 'title', 'status', 'fail_reason', 'saved_at', 'recipients', 'updated_at', 'forked_from'];

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'fail_reason',
        'preset_type',
        'preset_length',
        'preset_audience',
        'saved_at',
        'spec',
        'versions',
        'recipients',
        'seen_by',
        'model',
        'tokens_total',
        'forked_from',
    ];

    protected function casts(): array
    {
        return [
            'saved_at' => 'datetime',
            'spec' => 'array',
            'versions' => 'array',
            'recipients' => 'array',
            'seen_by' => 'array',
            'tokens_total' => 'integer',
        ];
    }
}
