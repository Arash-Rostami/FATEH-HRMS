<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IconMissController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $name = $request->input('name');

        abort_unless(is_string($name) && preg_match('/^[a-z0-9_]{1,60}$/', $name) === 1, 422);

        if (Cache::add("icon-miss:{$name}", 1, now()->addDay())) {
            Log::warning('Icon missing from the font subset: add it to resources/icons/safelist.json or rebuild', [
                'icon' => $name,
                'page' => mb_substr((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), 0, 120),
            ]);
        }

        return response()->noContent();
    }
}
