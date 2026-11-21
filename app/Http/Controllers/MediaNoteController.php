<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsJsonEndpoint;
use App\Livewire\Dashboard\Media\Actions\SaveNoteAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaNoteController extends Controller
{
    use GuardsJsonEndpoint;

    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $action = $request->input('action');

        if (!in_array($action, ['add', 'delete'], true)) {
            return $this->malformed();
        }

        $data = $request->only(['media_id', 'position', 'body', 'id']);

        if ($action === 'delete') {
            $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                return $this->malformed('invalid');
            }

            SaveNoteAction::execute($request->user(), $action, $data);

            return response()->noContent();
        }

        $note = SaveNoteAction::execute($request->user(), $action, $data);

        if ($note === null) {
            return $this->malformed(SaveNoteAction::capReached($request->user(), $data) ? 'cap' : 'invalid');
        }

        return response()->json([
            'id' => $note->id,
            'position' => $note->position,
            'body' => $note->body,
        ], 201);
    }
}
