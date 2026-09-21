<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HandlesActionExceptions;
use App\Filament\Traits\HandlesSaveExceptions;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditRecord extends BaseEditRecord
{
    use HandlesActionExceptions;
    use HandlesSaveExceptions;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (Halt $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->reportSaveException($e);
        }
    }
}