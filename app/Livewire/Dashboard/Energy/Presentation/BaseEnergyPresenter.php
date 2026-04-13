<?php

namespace App\Livewire\Dashboard\Energy\Presentation;

abstract class BaseEnergyPresenter
{

    public function formatSectionTitle(string $rawTitle): string
    {
        return trim(preg_replace('/[^\p{Arabic}\s]/u', '', $rawTitle));
    }


    public function getIcons(): array
    {
        return [
            'physique' => 'fitness_center',
            'emotion'  => 'favorite',
            'mind'     => 'psychology',
            'soul'     => 'auto_awesome',
        ];
    }

    public function getIcon(string $key): string
    {
        return $this->getIcons()[$key] ?? 'help';
    }
}
