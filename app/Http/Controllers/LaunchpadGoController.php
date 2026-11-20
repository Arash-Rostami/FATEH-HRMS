<?php

namespace App\Http\Controllers;

use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Models\Launchpad;
use App\Services\SsoTokenService;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LaunchpadGoController extends Controller
{
    public function __invoke(Request $request, string $launchpad): Response
    {
        $user = $request->user();
        $row = Launchpad::query()->whereKey((int) $launchpad)->first(['id', 'user_id', 'kind', 'title', 'active', 'audience', 'seen_by', 'accessible_from', 'accessible_to', 'expire_at', 'settings']);

        if ($row === null || $row->kind !== 'link' || !in_array($row->status(), ['live', 'soon'], true) || !LaunchpadAccessPolicy::canView($row, $user)) {
            return $this->missing();
        }

        $site = (string) ($row->settings['site'] ?? '');
        $sso = Launchpad::ssoSites()[$site] ?? null;
        $sibling = $sso ?? collect((array) config('app.siblings'))->first(fn (array $item) => (string) ($item['name'] ?? '') === $site);
        if ($sibling === null || preg_match(LaunchpadForm::PATH_PATTERN, (string) ($row->settings['path'] ?? '')) !== 1) {
            return $this->missing();
        }

        $row->markSeenBy((int) $user->id);

        $email = mb_strtolower(trim((string) $user->email));
        $plain = rtrim((string) $sibling['url'], '/') . '/' . ltrim((string) ($row->settings['path'] ?? ''), '/');
        $headers = [
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'strict-origin',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($sso !== null && ($email === '' || $user->email_verified_at === null)) {
            return response()->view('livewire.dashboard.launchpad.go', [
                'notice' => true,
                'url' => $plain,
                'title' => (string) $row->title,
            ], 200, $headers + ['Content-Security-Policy' => "default-src 'none'; style-src 'self'; font-src 'self'; base-uri 'none'; frame-ancestors 'self'"]);
        }

        if ($sso !== null) {
            $iat = time();
            $jti = bin2hex(random_bytes(16));
            $assertion = SsoTokenService::sign([
                'iss' => config('launchpad.sso.issuer'),
                'aud' => $sso['sso']['aud'],
                'sub' => (string) $user->id,
                'iat' => $iat,
                'exp' => $iat + 60,
                'jti' => $jti,
                'email' => $email,
                'name' => $user->name,
            ], config('launchpad.sso.secret_key'));

            Log::info('launchpad sso', ['user' => $user->id, 'peer' => $site, 'jti' => $jti]);

            $receiver = $sso['sso']['receiver'];
            $parts = parse_url($receiver);
            $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

            return response()->view('livewire.dashboard.launchpad.go', [
                'notice' => false,
                'receiver' => $receiver,
                'assertion' => $assertion,
                'next' => filled($row->settings['path'] ?? null) ? '/' . ltrim((string) $row->settings['path'], '/') : '',
                'title' => (string) $row->title,
            ], 200, $headers + ['Content-Security-Policy' => "default-src 'none'; style-src 'self'; font-src 'self'; form-action {$origin}; base-uri 'none'; frame-ancestors 'self'"]);
        }

        return redirect()->away($plain);
    }

    private function missing(): Response
    {
        return response('', 404, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store']);
    }
}
