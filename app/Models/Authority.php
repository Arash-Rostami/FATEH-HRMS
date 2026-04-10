<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Authority extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'user_id',
        'sub_duty',
        'details',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function scopeSearch($query, ?string $search)
    {
        $keys = ['duty', 'execution_procedure', 'repeat_frequency', 'impact_score', 'proposed_delegation', 'approved_delegation', 'co_delegate'];

        return $query->when($search, fn($q) => $q->where(fn($q) => $q
            ->whereRaw(
                implode(' OR ', array_map(fn($k) => "JSON_UNQUOTE(JSON_EXTRACT(details, '$.{$k}')) LIKE ?", $keys)),
                array_fill(0, count($keys), "%{$search}%")
            )->orWhereHas('department', fn($d) => $d->whereAny(['code', 'name', 'description'], 'like', "%{$search}%"))
        ));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'sub_duty' => 'boolean',
            'details' => 'array',
        ];
    }
}
