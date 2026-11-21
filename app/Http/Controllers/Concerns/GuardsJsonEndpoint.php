<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait GuardsJsonEndpoint
{
    protected function guard(Request $request): ?JsonResponse
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

    protected function malformed(): JsonResponse
    {
        return response()->json(['message' => 'بدنهٔ درخواست نامعتبر است.'], 422);
    }
}
