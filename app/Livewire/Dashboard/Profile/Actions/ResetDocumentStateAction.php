<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DocumentForm;

class ResetDocumentStateAction
{

    public function getFile(DocumentForm $form, string $key): mixed
    {
        return $form->files[$key] ?? null;
    }

    public function hasFile(DocumentForm $form, string $key): bool
    {
        return isset($form->files[$key]);
    }

    public function removeFile(DocumentForm $form, string $key): void
    {
        unset($form->files[$key]);
        $form->files = array_values($form->files);
    }

    public function resetCustom(DocumentForm $form): void
    {
        $form->customType = '';
        $form->customFile = null;
    }
}
