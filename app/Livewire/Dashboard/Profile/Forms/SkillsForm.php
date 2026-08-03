<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class SkillsForm extends Form
{
    #[Validate('nullable|integer')]
    public ?int $skillId = null;

    #[Validate('nullable|string|max:255')]
    public ?string $proposedName = null;
}
