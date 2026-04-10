<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];
    protected $primaryKey = 'code';
    protected $keyType = 'string';


    public function authorities(): HasMany
    {
        return $this->hasMany(Authority::class, 'department_id', 'code');
    }
    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class, 'department_id', 'code');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'department_id', 'code');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'department_id', 'code');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'department_id', 'code');
    }
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Profile::class, 'department_id', 'id', 'code');
    }
}
