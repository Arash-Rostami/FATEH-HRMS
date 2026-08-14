<?php

namespace App\Models;

use App\Models\Traits\HasMenuState;
use App\Models\Traits\HasStageHelpers;
use App\Models\Traits\HasSuggestionAlert;
use App\Services\ContentSanitizerService;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Suggestion extends Model
{
    use HasMenuState,
        HasFactory,
        HasStageHelpers,
        HasSuggestionAlert,
        CleansAttachedFiles;


    public const PURPOSES = [
        'cost' => 'کاهش هزینه',
        'time' => 'کاهش زمان',
        'workload' => 'کاهش بار کاری',
        'sales' => 'افزایش فروش',
        'profit' => 'افزایش سود',
    ];

    public const PRIORITIES = [
        'low' => 'کم',
        'medium' => 'متوسط',
        'high' => 'زیاد',
    ];
    public const RULES = [
        'simplify' => 'سادگی فرآیند',
        'prioritize' => 'اولویت‌بندی',
        'collaborate' => 'همکاری تیمی',
        'boost' => 'افزایش انگیزه',
    ];
    public const STAGES = [
        'pending' => 'ارسال شده',
        'team_remarks' => 'منتظر هم‌تیمی‌ها',
        'dept_remarks' => 'منتظر ذینفعان',
        'awaiting_decision' => 'منتظر تصمیم نهایی',
        'accepted' => 'پذیرفته شده',
        'rejected' => 'پذیرفته نشده',
        'under_review' => 'نیازمند تکمیل',
        'closed' => 'پایان‌یافته',
    ];
    public const STAGE_ICONS = [
        'pending' => '🕒',
        'team_remarks' => '💬',
        'dept_remarks' => '📝',
        'awaiting_decision' => '🤔',
        'accepted' => '😊',
        'rejected' => '😔',
        'under_review' => '🔍',
        'closed' => '🔒',
    ];

    protected $fillable = [
        'title',
        'description',
        'departments',
        'purpose',
        'rule',
        'attachment',
        'stage',
        'self_fill',
        'abort',
        'priority',
        'comments',
        'user_id'
    ];

    public static function countAborted(): int
    {
        return self::where('abort', true)->count();
    }

    public static function countAttachment()
    {
        return self::whereNotNull('attachment')->count();
    }

    public static function countClosed()
    {
        return self::where('stage', 'closed')->count();
    }

    public static function countPending()
    {
        return self::whereNotIn('stage', ['accepted', 'rejected', 'closed'])->count();
    }

    public static function countSelfFilled()
    {
        return self::where('self_fill', true)->count();
    }

    public static function countStage($stage)
    {
        return self::where('stage', $stage)->count();
    }

    public function inProcessReviews()
    {
        return $this->hasMany(Review::class)
            ->where('department_id', 'MA')
            ->whereNotNull('referral');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'suggestion_id', 'id');
    }

    public function scopeForDepartment(Builder $query, string $code): Builder
    {
        return $query->whereJsonContains('departments', $code);
    }

    public function scopeWithReviewCounts(Builder $query): Builder
    {
        return $query->withCount([
            'reviews as agree_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['agree']),
            'reviews as neutral_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['neutral']),
            'reviews as disagree_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['disagree']),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'self_fill' => 'boolean',
            'abort' => 'boolean',
            'departments' => 'array',
            'purpose' => 'array',
            'rule' => 'array',
        ];
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected static function booted(): void
    {
        static::deleting(fn(self $suggestion) => static::deleteStoredFiles($suggestion->attachment));
    }
}
