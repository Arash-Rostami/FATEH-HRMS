<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsJsonEndpoint;
use App\Livewire\Dashboard\Knowledge\Actions\SaveProgressAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaProgressController extends Controller
{
    use GuardsJsonEndpoint;

    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $items = $request->input('items');

        if (!is_array($items) || !array_is_list($items) || count($items) > 50 || array_filter($items, fn ($item) => !is_array($item)) !== []) {
            return $this->malformed();
        }

        SaveProgressAction::execute($request->user(), $items);

        return response()->noContent();
    }
}
