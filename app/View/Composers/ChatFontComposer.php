<?php

namespace App\View\Composers;

use App\Services\Correspondence\CorrespondenceFonts;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ChatFontComposer
{
    public const PATTERNS = ['mesh' => 'پیش‌فرض', 'doodle' => 'آیکون‌دار', 'twill' => 'لوزی', 'lattice' => 'شبکه', 'motif' => 'نگاره'];

    private const EXCLUDED = ['nazanin', 'iranyekan', 'btitr'];

    public function compose(View $view): void
    {
        $fonts = Arr::except(CorrespondenceFonts::options('fa'), self::EXCLUDED);

        $view->with([
            'chatPatterns' => self::PATTERNS,
            'chatFonts' => $fonts,
            'chatFontFace' => CorrespondenceFonts::fontFace(array_keys($fonts)),
        ]);
    }
}
