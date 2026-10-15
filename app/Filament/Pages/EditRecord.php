<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HandlesActionExceptions;
use App\Filament\Traits\HandlesDroppedFiles;
use App\Filament\Traits\HandlesSaveExceptions;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditRecord extends BaseEditRecord
{
    use HandlesActionExceptions;
    use HandlesDroppedFiles;
    use HandlesSaveExceptions;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $tracked = $this->trackedFileColumns();
        $before = $this->trackedFilePaths($this->getRecord(), $tracked);

        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException|Halt|Cancel $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->notifySaveException($e);
        }

        rescue(fn () => $this->deleteDroppedFiles($this->getRecord(), $before, $tracked));
    }

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