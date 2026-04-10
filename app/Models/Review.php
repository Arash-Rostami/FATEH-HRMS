<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Review extends Model
{
    use HasFactory;

    public const FEEDBACKS = [
        'agree' => 'موافق',
        'neutral' => 'نیمه موافق',
        'disagree' => 'مخالف',
        'incomplete' => 'ناقص',
        'unknown' => 'نامشخص',
    ];

    public const FEEDBACK_ICONS = [
        'agree' => '👍',
        'neutral' => '🖖',
        'disagree' => '👎',
        'incomplete' => '⚠️',
        'unknown' => '❓',
    ];

    protected $fillable = [
        'comments',
        'actions',
        'feedback',
        'department_id',
        'complete',
        'referral',
        'user_id',
        'suggestion_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    protected function casts(): array
    {
        return [
            'complete' => 'boolean',
            'referral' => 'array',
        ];
    }

    public function referralDepartments(): Collection
    {
        return Department::whereIn('code', $this->referral ?? [])->get();
    }

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(Suggestion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        return (bool)$this->complete;
    }
}
