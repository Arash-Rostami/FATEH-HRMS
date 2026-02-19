<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FAQ extends Model
{
    use HasFactory;

    protected  = 'faqs';

    protected  = [
        'department_id',
        'user_id',
        'category',
        'question',
        'answer',
    ];

    public function department(): BelongsTo
    {
        return ->belongsTo(Department::class, 'department_id', 'id');
    }

    public function user(): BelongsTo
    {
        return ->belongsTo(User::class);
    }
}
