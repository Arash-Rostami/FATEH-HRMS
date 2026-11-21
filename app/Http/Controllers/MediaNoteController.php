<?php

namespace App\Http\Controllers;

use App\Livewire\Dashboard\Media\Actions\SaveNoteAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaNoteController extends Controller
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $action = $request->input('action');

        if (!in_array($action, ['add', 'delete'], true)) {
            return response()->json(['message' => 'بدنهٔ درخواست نامعتبر است.'], 422);
        }

        if ($action === 'add' && (!is_numeric($request->input('media_id')) || !is_numeric($request->input('position')) || !is_string($request->input('body')))) {
            return response()->json(['message' => 'بدنهٔ درخواست نامعتبر است.'], 422);
        }

        if ($action === 'delete' && !is_numeric($request->input('id'))) {
            return response()->json(['message' => 'بدنهٔ درخواست نامعتبر است.'], 422);
        }

        $note = SaveNoteAction::execute($request->user(), $action, $request->only(['media_id', 'position', 'body', 'id']));

        if ($action === 'delete') {
            return response()->noContent();
        }

        if ($note === null) {
            return response()->json(['message' => 'یادداشت ذخیره نشد.'], 422);
        }

        return response()->json([
            'id' => $note->id,
            'position' => $note->position,
            'body' => $note->body,
        ], 201);
    }

    private function guard(Request $request): ?JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'احراز هویت نشده است.'], 401);
        }

        $token = (string) $request->input('_token', '');
        $sessionToken = $request->hasSession() ? (string) $request->session()->token() : '';

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            return response()->json(['message' => 'نشست منقضی شده است.'], 419);
        }

        return null;
    }
}
