<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Enums\LinkIcon;
use App\Models\Link;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;

class LinkPresenter
{
    public function cardData(Link $link, string $kind, string $ip = ''): array
    {
        $isInternal = $kind === 'internal';
        $resolved = $isInternal ? $link->resolvedUrl($ip) : $link->url;
        $internal = $isInternal ? $link->resolvedIsInternal($ip) : false;

        return [
            'resolved' => $resolved,
            'internal' => $internal,
            'pickedIcon' => LinkIcon::tryFrom((string) $link->icon_description),
            'clickPayload' => Js::from([
                'id' => $link->id,
                'title' => $link->url_title,
                'url' => $resolved,
                'icon' => $link->icon_description,
                'internal' => $internal,
            ]),
        ];
    }

    public function launchData(Collection $internalLinks, Collection $externalLinks): array
    {
        return [
            'sections' => [
                ['icon' => 'dataset_linked', 'label' => 'مسیرهای داخلی', 'kind' => 'internal', 'links' => $internalLinks],
                ['icon' => 'public', 'label' => 'پیوندهای بیرونی', 'kind' => 'external', 'links' => $externalLinks],
            ],
            'hasLinks' => $internalLinks->isNotEmpty() || $externalLinks->isNotEmpty(),
        ];
    }

    public function launchLinkData(Link $link, string $kind, string $ip, int &$hk): array
    {
        $card = $this->cardData($link, $kind, $ip);
        $hk++;

        return [
            'resolved' => $card['resolved'],
            'internal' => $card['internal'],
            'hotkey' => $hk <= 9 ? $hk : null,
            'target' => $card['internal'] ? '_self' : '_blank',
            'rel' => $card['internal'] ? '' : 'noopener noreferrer',
            'initial' => mb_substr(trim((string) ($link->url_title ?? '')), 0, 1),
            'pickedIcon' => $card['pickedIcon'],
            'clickPayload' => $card['clickPayload'],
        ];
    }
}