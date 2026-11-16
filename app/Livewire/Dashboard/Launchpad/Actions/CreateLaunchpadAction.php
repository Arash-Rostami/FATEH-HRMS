<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use App\Traits\CleansAttachedFiles;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CreateLaunchpadAction
{
    use CleansAttachedFiles;

    public function execute(LaunchpadForm $form, User $user, bool $replace = false): array
    {
        $form->validate();
        $title = $form->normalizedTitle();
        $dept = $form->department_id;

        if (!LaunchpadAccessPolicy::canCreateIn($user, $dept)) {
            throw new RuntimeException('این عملیات مجاز نیست.');
        }

        $report = $form->precheck();
        if (!$report['ok']) {
            throw ValidationException::withMessages(['form.files' => 'فایل‌های انتخاب‌شده معتبر نیستند.']);
        }

        $items = array_map(fn ($file) => [$file, (string) $file->getClientOriginalName()], array_values($form->files));
        $settings = ['entry' => (string) config('launchpad.entry'), 'files_count' => $report['count'], 'size' => $report['bytes']];
        $disk = Storage::disk('public');
        $folder = (string) Str::ulid();
        $base = "launchpad/files/{$folder}";

        try {
            foreach ($items as [$file, $name]) {
                $stream = $file->readStream();
                $written = is_resource($stream) && $disk->writeStream($base . '/' . Launchpad::storedName($name), $stream);
                is_resource($stream) && fclose($stream);
                if (!$written) {
                    throw new RuntimeException('ذخیره فایل ناموفق بود.');
                }
            }

            [$row, $oldDirectory] = DB::transaction(function () use ($form, $user, $title, $dept, $replace, $folder, $settings) {
                Department::query()->whereKey($dept)->lockForUpdate()->first(['code']);

                $existing = Launchpad::query()->where('department_id', $dept)->where('title', $title)->lockForUpdate()->first();

                if ($existing !== null) {
                    if (!$replace || !LaunchpadAccessPolicy::canManage($existing, $user)) {
                        throw ValidationException::withMessages(['form.title' => 'این عنوان در این بخش قبلاً ثبت شده است.']);
                    }
                    $oldDirectory = $existing->directory();
                    $existing->update(['folder' => $folder, 'description' => filled($form->description) ? $form->description : null, 'settings' => $settings]);

                    return [$existing, $oldDirectory];
                }

                if (Launchpad::query()->where('department_id', $dept)->count() >= (int) config('launchpad.max_per_department')) {
                    throw ValidationException::withMessages(['form.files' => 'سقف تعداد برنامه‌ها برای این بخش پر شده است.']);
                }

                $audience = User::query()->visibleOnBoard()
                    ->whereIn('users.id', array_map('intval', $form->audience))
                    ->where('users.id', '!=', $user->id)
                    ->pluck('users.id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $row = Launchpad::query()->create([
                    'user_id' => $user->id,
                    'department_id' => $dept,
                    'kind' => 'static',
                    'title' => $title,
                    'description' => filled($form->description) ? $form->description : null,
                    'folder' => $folder,
                    'audience' => $audience,
                    'accessible_from' => filled($form->accessible_from) ? $form->accessible_from : null,
                    'accessible_to' => filled($form->accessible_to) ? $form->accessible_to : null,
                    'expire_at' => filled($form->expire_at) ? $form->expire_at : null,
                    'settings' => $settings,
                ]);

                return [$row, null];
            });
        } catch (Throwable $e) {
            self::deleteStoredDirectory($base);

            throw $e instanceof QueryException && ($e->errorInfo[1] ?? null) === 1062
                ? ValidationException::withMessages(['form.title' => 'این عنوان در این بخش قبلاً ثبت شده است.'])
                : $e;
        }

        if ($oldDirectory !== null) {
            self::deleteStoredDirectory($oldDirectory);
        }

        return ['launchpad' => $row, 'replaced' => $oldDirectory !== null];
    }
}
