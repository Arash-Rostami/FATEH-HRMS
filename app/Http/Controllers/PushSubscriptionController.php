<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsJsonEndpoint;
use App\Jobs\SendChatPushJob;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    use GuardsJsonEndpoint;

    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'endpoint' => ['required', 'string', 'max:2048', 'url'],
            'keys.p256dh' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'keys.auth' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'scopes.contact' => ['required', 'boolean'],
            'scopes.channel' => ['required', 'boolean'],
            'confirm' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->malformed();
        }

        $data = $validator->validated();

        if (!$this->allowedEndpoint($data['endpoint']) || !$this->validKeys($data['keys'])) {
            return $this->malformed();
        }

        $row = PushSubscription::sync($request->user(), $data);

        if ($row && $request->boolean('confirm')) {
            SendChatPushJob::fire('confirm', $row->id, 0);
        }

        return response()->noContent();
    }

    private function validKeys(array $keys): bool
    {
        $p256dh = base64_decode(strtr($keys['p256dh'], '-_', '+/'), true);
        $auth = base64_decode(strtr($keys['auth'], '-_', '+/'), true);

        return $p256dh !== false && strlen($p256dh) === 65 && $p256dh[0] === "\x04"
            && $auth !== false && strlen($auth) === 16;
    }

    private function allowedEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        $host = strtolower($parts['host'] ?? '');

        return ($parts['scheme'] ?? '') === 'https'
            && preg_match('/^[a-z0-9.-]+$/', $host) === 1
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && ($parts['port'] ?? 443) === 443
            && ($host === 'fcm.googleapis.com' || Str::endsWith($host, ['.push.services.mozilla.com', '.notify.windows.com', '.push.apple.com']));
    }
}
