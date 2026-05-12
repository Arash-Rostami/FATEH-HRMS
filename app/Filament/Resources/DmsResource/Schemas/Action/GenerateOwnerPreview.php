<?php


namespace App\Filament\Resources\DmsResource\Schemas\Action;

use App\Models\Department;
use App\Models\User;

class GenerateOwnerPreview
{
    public function handle(array $state): ?string
    {
        if (empty($state)) {
            return null;
        }

        if (in_array('ALL', $state)) {
            return __('resources/dms/strings.fields.all_users_text');
        }

        $departments = Department::getCachedOptions()->only($state);

        return User::query()
            ->select('users.id', 'users.name', 'profiles.department_id')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->whereIn('profiles.department_id', $state)
            ->where('users.status', 'active')
            ->orderBy('profiles.department_id')
            ->orderBy('users.name')
            ->get()
            ->groupBy('department_id')
            ->map(fn($deptUsers, string $code) => ($departments[$code] ?? $code) . ': ' .
                $deptUsers->map(fn($u) => $u->name)->implode(' ┆ ')
            )
            ->values()
            ->implode("\n\n") ?: null;
    }
}
