<?php

namespace App\Models;

use App\Models\Concerns\HasUser;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    use HasUser,
        HasFactory;

    protected $table = 'credentials';

    protected $fillable = [
        'user_id',
        'app_name',
        'username',
        'password',
        'link',
        'note',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            get: $this->decryptOrPlain(...),
            set: $this->encryptOrNull(...),
        );
    }

    private function decryptOrPlain(?string $value): ?string
    {
        if ($value === null) return null;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    private function encryptOrNull(?string $value): ?string
    {
        return $value === null
            ? null
            : Crypt::encryptString($value);
    }
}
