<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];
    protected $primaryKey = 'code';
    protected $keyType = 'string';

    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class, 'department_id', 'code');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'department_id', 'code');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'department_id', 'code');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'department_id', 'code');
    }
}
