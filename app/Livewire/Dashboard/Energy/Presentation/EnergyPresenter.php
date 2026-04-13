<?php

namespace App\Livewire\Dashboard\Energy\Presentation;

class EnergyPresenter extends BaseEnergyPresenter
{
    public function getContainerColorVar(string $category): string
    {
        return match ($category) {
            'physique', 'soul' => '--md-sys-color-primary-container',
            'emotion'          => '--md-sys-color-secondary-container',
            'mind'             => '--md-sys-color-tertiary-container',
            default            => '--md-sys-color-surface',
        };
    }

    public function getOnContainerColorVar(string $category): string
    {
        return match ($category) {
            'physique', 'soul' => '--md-sys-color-on-primary-container',
            'emotion'          => '--md-sys-color-on-secondary-container',
            'mind'             => '--md-sys-color-on-tertiary-container',
            default            => '--md-sys-color-on-surface',
        };
    }
}
