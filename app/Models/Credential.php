<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory;

    protected  = 'app_credentials';

    protected  = [
        'user_id',
        'app_name',
        'username',
        'password',
        'link',
        'note',
    ];

    public function user(): BelongsTo
    {
        return ->belongsTo(User::class);
    }
}
