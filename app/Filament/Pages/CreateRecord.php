<?php

namespace App\Filament\Pages;

use App\Traits\Filament\GuardsModuleLock;
use App\Traits\Filament\HandlesActionExceptions;
use App\Traits\Filament\HandlesSaveExceptions;
use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateRecord extends BaseCreateRecord
{
    use GuardsModuleLock;
    use HandlesActionExceptions;
    use HandlesSaveExceptions;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException|Halt|Cancel $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->notifySaveException($e);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (Halt $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->reportSaveException($e);
        }
    }
}