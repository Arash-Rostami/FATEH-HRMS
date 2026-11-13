<?php

namespace App\Filament\Pages;

use App\Traits\Filament\ExplainsHiddenRelationTabs;
use App\Traits\Filament\GuardsModuleLock;
use App\Traits\Filament\HandlesActionExceptions;
use App\Traits\Filament\HandlesDroppedFiles;
use App\Traits\Filament\HandlesSaveExceptions;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditRecord extends BaseEditRecord
{
    use ExplainsHiddenRelationTabs;
    use GuardsModuleLock;
    use HandlesActionExceptions;
    use HandlesDroppedFiles;
    use HandlesSaveExceptions;

    protected function authorizeAccess(): void
    {
        $this->guardRecordLock();

        parent::authorizeAccess();
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $tracked = $this->trackedFileColumns();
        $before = $this->trackedFilePaths($this->getRecord(), $tracked);

        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException|Halt|Cancel|HttpResponseException $e) {
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