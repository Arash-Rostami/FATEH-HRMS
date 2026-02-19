<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'url_title',
        'url',
        'internal_url',
        'image_description',
        'icon_description',
        'link',
        'sequence'
    ];

    /**
     * Scope a query to only include external links.
     */
    public function scopeExternal($query)
    {
        return $query->where('link', 'external');
    }

    /**
     * Scope a query to only include internal links.
     */
    public function scopeInternal($query)
    {
        return $query->where('link', 'internal');
    }
}
