<?php

namespace App\Models;

use App\Models\Concerns\HasActive;
use App\Models\Concerns\HasLetterheadNumbering;
use App\Models\Concerns\Hooks\HasLetterheadHooks;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Letterhead extends Model
{
    use HasActive,
        CleansAttachedFiles,
        HasLetterheadNumbering,
        HasLetterheadHooks;

    public const KIND_HEAD = 'head', KIND_REGISTRY = 'registry';

    public const SIZE_A4 = 'a4', SIZE_A5 = 'a5', SIZE_FIT = 'fit';

    protected $attributes = [
        'kind' => self::KIND_HEAD,
        'size' => self::SIZE_A4,
        'lang' => 'fa',
        'page_numbers' => false,
        'sign_layout' => 2,
        'active' => true,
        'start_at' => 1,
    ];

    protected $fillable = [
        'kind',
        'name',
        'size',
        'lang',
        'header_image',
        'footer_image',
        'stamp_image',
        'watermark',
        'margins',
        'sender_block',
        'page_numbers',
        'style',
        'fields_box',
        'config',
        'sign_layout',
        'parent_id',
        'pattern',
        'start_at',
    ];

    protected function casts(): array
    {
        return [
            'margins' => 'array',
            'sender_block' => 'array',
            'counters' => 'array',
            'page_numbers' => 'boolean',
            'style' => 'array',
            'fields_box' => 'array',
            'config' => 'array',
            'sign_layout' => 'integer',
            'start_at' => 'integer',
        ];
    }

    public static function kindLabels(): array
    {
        return [
            self::KIND_HEAD => __('resources/correspondence/strings.letterhead.kind.head'),
            self::KIND_REGISTRY => __('resources/correspondence/strings.letterhead.kind.registry'),
        ];
    }

    public static function defaultStyle(): array
    {
        return [
            'font' => 'bnazanin',
            'size' => 13,
            'subject_size' => 13,
            'copy_size' => 11,
            'latin_font' => 'tinos',
            'latin_sizes' => [13, 12, 11],
            'line_height' => 1.5,
            'indent' => 0,
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'prefix' => '',
            'header_from' => null,
            'footer_from' => null,
            'header_height_mm' => 40,
            'footer_height_mm' => 20,
            'repeat_header' => true,
            'repeat_footer' => true,
            'signature_position' => 'end',
            'stamp_position' => 'beside_signature',
            'cc_position' => 'bottom',
            'table' => ['border' => 'solid', 'repeat_head' => true],
            'page_number_format' => 'page_x_of_y',
            'watermark_opacity' => 0.08,
        ];
    }

    public static function defaultMargins(string $size): array
    {
        return [
            'top' => match ($size) {
                self::SIZE_A4 => 6.0,
                self::SIZE_A5 => 4.5,
                default => 2.54,
            },
            'right' => 2.54,
            'bottom' => 2.54,
            'left' => 2.54,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function correspondences(): HasMany
    {
        return $this->hasMany(Correspondence::class);
    }
}
