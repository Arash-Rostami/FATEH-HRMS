<?php

namespace App\Models;

use App\Livewire\Dashboard\Project\Actions\ForceDeleteProjectAction;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasProjectFollowUps;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Support\ProjectCharter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Morilog\Jalali\CalendarUtils;
use App\Models\Concerns\HasNotifyEvents;
use App\Models\Concerns\Hooks\HasProjectHooks;

class Project extends Model
{
    use HasFactory,
        HasMenuState,
        HasReplies,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        HasReminders,
        HasNotifyEvents,
        HasProjectFollowUps,
        HasProjectHooks;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'member_ids',
        'departments',
        'channel_id',
        'settings',
        'archived_at',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public static function generateSlug(string $name): string
    {
        $base = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim($name)));
        $base = trim(preg_replace('/-{2,}/u', '-', (string)$base), '-');

        return ($base !== '' ? $base : 'project') . '-' . Str::random(6);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->owner_id === $user->id || in_array($user->id, $this->member_ids ?? [], true)) {
            return true;
        }

        $departmentCode = $user->profile?->department_id;

        return filled($departmentCode) && in_array($departmentCode, $this->departments ?? [], true);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune(): void
    {
        app(ForceDeleteProjectAction::class)->execute($this);
    }

    public static function idForChannel(int $channelId): ?int
    {
        return static::withTrashed()->where('channel_id', $channelId)->value('id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereJsonContains('member_ids', $user->id);

            $departmentCode = $user->profile?->department_id;
            if (filled($departmentCode)) {
                $q->orWhere(fn(Builder $x) => $x->whereNotNull('departments')
                    ->whereRaw('JSON_LENGTH(departments) > 0')
                    ->whereJsonContains('departments', $departmentCode));
            }
        });
    }

    public const KNOWN_SETTING_KEYS = ['requires_approval', 'sla', 'deadline', 'custom_schema', 'labels', 'auto_archive_days'];

    public const SETTING_LABELS = [
        'escalation_days' => ['ارجاع تأخیر به جوابگو', 'روز'],
        'due_soon_hours' => ['هشدار نزدیک سررسید', 'ساعت'],
    ];

    private ?ProjectCharter $charter = null;

    public function charter(): ProjectCharter
    {
        return $this->charter ??= new ProjectCharter($this);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    public function deadlineCapExceeded(?Carbon $deadline): bool
    {
        $cap = $this->charter()->cap();

        return $cap !== null && $deadline !== null && $deadline->gt($cap);
    }

    public function settingsSummary(): string
    {
        $parts = [];

        if ($this->setting('requires_approval') === true) {
            $parts[] = 'تأیید مدیر فعال';
        }

        $sla = $this->setting('sla');
        if ($sla) {
            $parts[] = 'SLA: ' . $sla . ' ساعت';
        }

        $deadline = $this->setting('deadline');
        if ($deadline && ($timestamp = strtotime($deadline))) {
            $parts[] = 'مهلت: ' . CalendarUtils::strftime('Y/m/d', $timestamp);
        }

        $schema = $this->setting('custom_schema');
        if (is_array($schema) && $schema !== []) {
            $parts[] = count($schema) . ' فیلد سفارشی';
        }

        return $parts !== [] ? implode('، ', $parts) : '—';
    }

    public function otherSettings(): array
    {
        $out = [];

        foreach ($this->settings ?? [] as $key => $value) {
            if (in_array($key, self::KNOWN_SETTING_KEYS, true) || $value === null || $value === '') {
                continue;
            }

            $out[$key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $out;
    }

    public function otherSettingChips(): array
    {
        $chips = [];

        foreach ($this->otherSettings() as $key => $value) {
            [$label, $unit] = self::SETTING_LABELS[$key] ?? [$key, null];
            $chips[$key] = ['label' => $label, 'text' => trim($value . ' ' . $unit)];
        }

        return $chips;
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    public static function notifyFields(): array
    {
        return [
            'settings.requires_approval' => 'bool',
            'settings.sla' => 'number',
            'settings.deadline' => 'date',
            'settings.auto_archive_days' => 'number',
            'settings.labels' => 'list',
            'owner_id' => 'user',
        ];
    }

    public static function pruneSettings(array $settings): array
    {
        return array_filter($settings, fn($v) => $v !== null && $v !== false && $v !== '' && $v !== []);
    }

    protected function casts(): array
    {
        return [
            'member_ids' => 'array',
            'departments' => 'array',
            'settings' => 'array',
            'archived_at' => 'datetime',
        ];
    }
}
