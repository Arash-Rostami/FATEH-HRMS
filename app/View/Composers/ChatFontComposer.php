<?php

namespace App\View\Composers;

use App\Services\Correspondence\CorrespondenceFonts;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ChatFontComposer
{
    private const EXCLUDED = ['nazanin', 'iranyekan', 'btitr'];

    public function compose(View $view): void
    {
        $fonts = Arr::except(CorrespondenceFonts::options('fa'), self::EXCLUDED);

        $view->with([
            'chatFonts' => $fonts,
            'chatFontFace' => CorrespondenceFonts::fontFace(array_keys($fonts)),
        ]);
    }
}
