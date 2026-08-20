<?php

namespace App\Livewire\Dashboard\ReleaseRequest\Presentation;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;

class ReleaseRequestPresenter
{
    public function typeMeta(string $type): array
    {
        $t = ReleaseRequestType::from($type);
        return ['icon' => $t->getMaterialIcon(), 'color' => $t->getMaterialColor(), 'label' => $t->getLabel()];
    }

    public function statusMeta(string $status): array
    {
        $s = ReleaseRequestStatus::from($status);
        $rejected = $s === ReleaseRequestStatus::Rejected;
        return [
            'icon' => $s->getMaterialIcon(),
            'color' => $s->getMaterialColor(),
            'label' => $s->getLabel(),
            'isRejected' => $rejected,
            'responseColor' => $rejected ? 'var(--md-sys-color-error)' : 'var(--md-sys-color-primary)',
            'responseBg' => $rejected ? 'var(--md-sys-color-error-container)' : 'var(--md-sys-color-primary-container)',
            'responseIcon' => $rejected ? 'cancel' : 'forum',
        ];
    }
}