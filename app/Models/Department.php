<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    public $incrementing = false;
    protected $fillable = ['code', 'name', 'description'];
    protected $primaryKey = 'code';
    protected $keyType = 'string';

    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'department', 'code');
    }
}
