<?php

namespace App\Filament\RelationManagers;

use App\Filament\Traits\HandlesActionExceptions;
use App\Filament\Traits\HandlesDroppedFiles;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager as BaseRelationManager;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class RelationManager extends BaseRelationManager
{
    use HandlesActionExceptions;
    use HandlesDroppedFiles;

    private const RESOURCE_ABILITIES = [
        'viewAny', 'view', 'create', 'replicate', 'update', 'reorder',
        'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny',
    ];

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($resource = static::relatedModelResource($ownerRecord)) {
            return $resource::canAccess();
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public static function deniedByPermission(Model $ownerRecord): ?string
    {
        $resource = static::relatedModelResource($ownerRecord);

        if (!$resource || $resource::canAccess()) {
            return null;
        }

        $module = $resource::moduleKey();

        return $module === 'permission' ? null : $module;
    }

    private static function relatedModelResource(Model $ownerRecord): ?string
    {
        if (static::shouldSkipAuthorization() || static::getRelatedResource()) {
            return null;
        }

        return Filament::getModelResource($ownerRecord->{static::getRelationshipName()}()->getQuery()->getModel());
    }

    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (!static::shouldSkipAuthorization() && in_array($action, self::RESOURCE_ABILITIES, true)) {
            $resource = static::getRelatedResource() ?? Filament::getModelResource($this->getTable()->getModel());

            if ($resource && (blank($record) || $record::class === $resource::getModel())) {
                return $resource::getAuthorizationResponse($action, $record);
            }
        }

        return parent::getAuthorizationResponse($action, $record);
    }

    public function getDefaultActionAuthorizationResponse(Action $action): ?Response
    {
        $response = parent::getDefaultActionAuthorizationResponse($action);

        if ($response !== null || static::shouldSkipAuthorization() || !$this->isOwnerBoundAction($action)) {
            return $response;
        }

        $owner = $this->getOwnerRecord();
        $resource = Filament::getModelResource($owner);

        return $resource ? $resource::getAuthorizationResponse('update', $owner) : null;
    }

    private function isOwnerBoundAction(Action $action): bool
    {
        return $action instanceof AttachAction
            || $action instanceof DetachAction
            || $action instanceof DetachBulkAction
            || $action instanceof AssociateAction
            || $action instanceof DissociateAction
            || $action instanceof DissociateBulkAction;
    }
}