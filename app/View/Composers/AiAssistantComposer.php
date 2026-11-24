<?php

namespace App\View\Composers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class AiAssistantComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'aiAccess' => config('app.ai_assistant_access'),
            'aiAssistantBaseSrc' => aiAssistantOn() ? $this->baseSrc() : null,
            'aiViews' => [
                'ths' => thsAiOn(),
                'channel' => channelAiOn(),
                'correspondence' => correspondenceAiOn(),
                'contact' => contactAiOn(),
            ],
        ]);
    }

    private function baseSrc(): string
    {
        $user = auth()->user();

        return rtrim(config('services.ai_assistant.url'), '/')
            . '/?user=' . Str::slug(config('app.name_en')) . '_' . $user->getAuthIdentifier()
            . '&lang=' . config('app.ai_assistant_lang')
            . '&role=' . ($user->hasElevatedRole() ? 'admin' : 'user');
    }
}
