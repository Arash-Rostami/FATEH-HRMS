<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class
ToggleCalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['extra' => ['preferences' => [
            'persian_dates' => !$user->getPreference('persian_dates', true),
        ]]]);

        return response()->json(['persian' => $user->fresh()->getPreference('persian_dates', true)]);
    }
}
