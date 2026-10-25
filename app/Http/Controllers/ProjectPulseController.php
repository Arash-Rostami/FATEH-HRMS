<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProjectTask\ChannelProvisioner;
use App\Services\ProjectTask\ProjectHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProjectPulseController extends Controller
{
    private const GRANT_TTL_SECONDS = 60;

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $granted = $this->granted($request->user(), $id, $request->query('tab') === 'teamChat');

        return response()
            ->json($granted ? ProjectHeartbeat::versions($id) : ['gone' => true])
            ->header('Cache-Control', 'no-store');
    }

    private function granted(User $user, int $id, bool $teamChat): bool
    {
        $key = ModelCacheVersion::key(Project::class, "pulse-grant:{$user->id}:{$id}:" . (int) $teamChat);

        if (Cache::has($key)) {
            return true;
        }

        $project = Project::visibleTo($user)->find($id) ?? abort(404);

        $granted = !$teamChat
            || app(ChannelProvisioner::class)->resolve($project)->memberUsers()->whereKey($user->id)->exists();

        if ($granted) {
            Cache::put($key, true, self::GRANT_TTL_SECONDS);
        }

        return $granted;
    }
}
