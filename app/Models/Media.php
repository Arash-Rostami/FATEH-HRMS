<?php

namespace App\Models;

use App\Models\Concerns\HasAudience;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\Hooks\HasMediaHooks;
use App\Services\ContentSanitizerService;
use App\Traits\CleansAttachedFiles;
use getID3;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

class Media extends Model
{
    use HasFactory,
        HasPublicAssetUrl,
        CleansAttachedFiles,
        HasAudience,
        HasDepartment,
        HasUser,
        HasMediaHooks;

    public const MAX_SECONDS = 172800;

    public const KIND_SERIES = 'series';

    public const KIND_AUDIO = 'audio';

    public const KIND_VIDEO = 'video';

    protected $table = 'media';

    protected $hidden = [
        'file_path',
        'cover_image',
        'transcript_path',
        'chapters_path',
    ];

    protected $fillable = [
        'user_id',
        'parent_id',
        'kind',
        'title',
        'topic',
        'description',
        'cover_image',
        'file_path',
        'transcript_path',
        'chapters_path',
        'icon',
        'duration_seconds',
        'position',
        'active',
        'pinned',
        'department_id',
        'departments',
        'report_date',
        'expires_at',
        'report_ids',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(KnowledgeProgress::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MediaNote::class);
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where(fn (Builder $inner) => $inner->whereNull('report_date')->orWhere('report_date', '<=', now()->toDateString()));
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeLibrary(Builder $query, ?User $user = null): Builder
    {
        return $query->forViewer($user)->notExpired()->released()->whereNull('parent_id');
    }

    public static function playableFor(User $user, array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return new Collection();
        }

        $parents = fn () => static::library($user)->select('media.id');

        return static::query()
            ->whereIn('id', $ids)
            ->active()
            ->notExpired()
            ->released()
            ->where(function (Builder $query) use ($parents) {
                $query->where(fn (Builder $single) => $single->whereNull('parent_id')->whereIn('id', $parents())->whereIn('kind', [self::KIND_AUDIO, self::KIND_VIDEO]))
                    ->orWhereIn('parent_id', $parents());
            })
            ->get(['id', 'duration_seconds']);
    }

    public static function probeDuration(string $absolutePath): ?int
    {
        try {
            if ($absolutePath === '' || !is_file($absolutePath)) {
                return null;
            }

            $seconds = (new getID3())->analyze($absolutePath)['playtime_seconds'] ?? null;

            if (!is_numeric($seconds) || (float) $seconds <= 0) {
                return null;
            }

            return (int) round((float) $seconds);
        } catch (Throwable) {
            return null;
        }
    }

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'report_date' => 'date',
            'expires_at' => 'date',
            'report_ids' => 'array',
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value,
            set: fn (?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->publicUrl($this->file_path),
        );
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->publicUrl($this->cover_image),
        );
    }

    protected function kindLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->kind) {
                self::KIND_SERIES => 'سری',
                self::KIND_AUDIO => 'صوتی',
                self::KIND_VIDEO => 'ویدیو',
                default => '',
            },
        );
    }

    protected function isLocked(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->report_date !== null && $this->report_date->isFuture(),
        );
    }

    private function publicUrl(?string $path): ?string
    {
        if (!filled($path)) {
            return null;
        }

        $url = self::resolvePublicAssetUrl($path);

        return $url === '' ? null : $url;
    }
}
