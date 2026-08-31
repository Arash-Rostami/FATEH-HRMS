<?php

namespace App\Models;

use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasUserHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Read extends Model
{
    use HasFactory;
    use HasMenuState;
    use HasUserHelpers;

    protected $table = 'reads';

    protected $fillable = [
        'document_id',
        'user_id',
        'read',
        'read_count',
        'combined_read_count',
    ];

    public function dms(): BelongsTo
    {
        return $this->belongsTo(DMS::class, 'document_id');
    }

    public static function getUnreadDocumentsCount(): int
    {
        return static::query()
            ->where('user_id', auth()->id())
            ->where('read', true)
            ->where('read_count', 0)
            ->count();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
            'read_count' => 'integer',
            'combined_read_count' => 'integer',
        ];
    }
}
