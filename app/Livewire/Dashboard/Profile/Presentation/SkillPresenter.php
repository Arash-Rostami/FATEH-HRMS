<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Enums\SkillIcon;
use App\Models\Skill;

class SkillPresenter
{
    public function categoryLabel(Skill $skill): string
    {
        return $skill->category ?: 'دسته‌بندی‌نشده';
    }

    public function displayLabel(Skill $skill): string
    {
        return app()->getLocale() === 'en' && filled($skill->name_en) ? $skill->name_en : $skill->name;
    }

    public function icon(Skill $skill): string
    {
        return $skill->icon ?: SkillIcon::default()->value;
    }
}
