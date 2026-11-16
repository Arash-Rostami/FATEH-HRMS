<?php

namespace App\Http\Controllers;

use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Models\Launchpad;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LaunchpadGoController extends Controller
{
    public function __invoke(Request $request, string $launchpad): Response
    {
        $user = $request->user();
        $row = Launchpad::query()->whereKey((int) $launchpad)->first(['id', 'user_id', 'kind', 'active', 'audience', 'seen_by', 'accessible_from', 'accessible_to', 'expire_at', 'settings']);

        if ($row === null || $row->kind !== 'link' || !in_array($row->status(), ['live', 'soon'], true) || !LaunchpadAccessPolicy::canView($row, $user)) {
            return $this->missing();
        }

        $site = (string) ($row->settings['site'] ?? '');
        $sibling = collect((array) config('app.siblings'))->first(fn (array $item) => (string) ($item['name'] ?? '') === $site);
        if ($sibling === null || preg_match(LaunchpadForm::PATH_PATTERN, (string) ($row->settings['path'] ?? '')) !== 1) {
            return $this->missing();
        }

        $row->markSeenBy((int) $user->id);

        return redirect()->away(rtrim((string) $sibling['url'], '/') . '/' . ltrim((string) ($row->settings['path'] ?? ''), '/'));
    }

    private function missing(): Response
    {
        return response('', 404, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store']);
    }
}
