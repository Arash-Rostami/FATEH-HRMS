<?php

namespace App\Models;

use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FAQ extends Model
{
    use HasFactory;

    protected $table = 'faqs';

    protected $fillable = [
        'department_id',
        'user_id',
        'category',
        'question',
        'answer'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function answer(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }

    protected function question(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value): ?string => ContentSanitizerService::clean($value),
        );
    }
}
