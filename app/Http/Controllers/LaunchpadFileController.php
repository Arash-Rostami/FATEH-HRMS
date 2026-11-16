<?php

namespace App\Http\Controllers;

use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LaunchpadFileController extends Controller
{
    public function __invoke(Request $request, string $launchpad, string $token, string $file): Response
    {
        if ($request->header('Sec-Fetch-Dest') === 'document') {
            return $this->missing();
        }

        $userId = LaunchpadAccessPolicy::tokenUser((int) $launchpad, $token);
        if ($userId === null) {
            return $this->missing();
        }

        $row = Launchpad::query()->whereKey((int) $launchpad)->first(['id', 'user_id', 'kind', 'folder', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at']);
        $directory = $row?->directory();
        if ($row === null || $row->kind !== 'static' || $directory === null) {
            return $this->missing();
        }

        $user = User::query()->find($userId);
        if ($user === null || !LaunchpadAccessPolicy::canView($row, $user)) {
            return $this->missing();
        }

        $stored = Launchpad::storedName($file);
        $path = $stored === null ? null : Storage::disk('public')->path($directory . '/' . $stored);
        if ($path === null || !is_file($path)) {
            return $this->missing();
        }

        $origin = $request->getSchemeAndHttpHost();
        $base = "{$origin}/launchpad/{$row->id}/{$token}/";
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $response = response()->file($path, [
            'Content-Type' => (string) config("launchpad.types.{$extension}"),
            'Content-Security-Policy' => "sandbox allow-scripts; default-src 'none'; script-src 'unsafe-inline' {$base}; style-src 'unsafe-inline' {$base}; img-src {$base} data: blob:; font-src {$base} data:; connect-src {$base}; form-action 'none'; base-uri 'none'; frame-ancestors {$origin}",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
        $response->setPrivate();
        $response->setEtag(hash_hmac('sha256', $row->folder . '|' . mb_strtolower($file), (string) config('app.key')));
        $response->isNotModified($request);

        return $response;
    }

    private function missing(): Response
    {
        return response('', 404, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store']);
    }
}
