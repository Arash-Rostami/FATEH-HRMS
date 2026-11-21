<?php

namespace App\Http\Controllers;

use App\Livewire\Dashboard\Media\Actions\SaveProgressAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaProgressController extends Controller
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $items = $request->input('items');

        if (!is_array($items) || !array_is_list($items) || count($items) > 50 || array_filter($items, fn ($item) => !is_array($item)) !== []) {
            return response()->json(['message' => 'بدنهٔ درخواست نامعتبر است.'], 422);
        }

        SaveProgressAction::execute($request->user(), $items);

        return response()->noContent();
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
