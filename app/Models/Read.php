<?php

namespace App\Models;

use App\Models\Traits\HasUserHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Read extends Model
{
    use HasFactory;
    use HasUserHelpers;

    protected $table = 'reads';
    protected $fillable = [
        'document_id',
        'user_id',
        'read',
        'read_count',
        'combined_read_count'
    ];

    public function dms(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DMS::class, 'document_id');
    }

    public static function getUnreadDocumentsCount()
    {
        return self::where('user_id', auth()->id())
            ->where('read', true)
            ->where('read_count', 0)
            ->count();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
