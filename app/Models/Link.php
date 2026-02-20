<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'url_title',
        'url_description',
        'internal_url',
        'image',
        'image_description',
        'icon',
        'icon_description',
        'link',
        'sequence'
    ];

    public static function countDoubleLinks()
    {
        return self::whereNotNull('internal_url')->count();
    }

    public static function countExternals()
    {
        return self::where('link', 'external')->count();
    }

    public static function countInternals()
    {
        return self::where('link', 'internal')->count();
    }

    public function scopeExternal($query)
    {
        return $query->where('link', 'external');
    }

    public function scopeInternal($query)
    {
        return $query->where('link', 'internal');
    }


    public function scopeSorted($query)
    {
        return $query->orderBy('sequence');
    }
}


