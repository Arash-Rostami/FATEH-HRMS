<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Jobs\RefreshLaunchpadJob;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Services\Menu\StateService;
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

    private const DUPLICATE_TITLE_MESSAGE = 'در این دپارتمان برنامه‌ای با همین نام وجود دارد. نام دیگری انتخاب کنید. برای جایگزینی، به اجازهٔ مدیریت برنامهٔ قبلی نیاز دارید.';

    public function execute(LaunchpadForm $form, User $user, bool $replace = false): array
    {
        $form->normalizeSource();
        $form->validate();
        $title = $form->normalizedTitle();
        $dept = $form->department_id;

        if (!LaunchpadAccessPolicy::canCreateIn($user, $dept)) {
            throw new RuntimeException('شما اجازهٔ افزودن برنامه در دپارتمان انتخاب‌شده را ندارید.');
        }

        $form->verifyTabs();

        $kind = $form->kind === 'link' ? 'link' : 'static';
        $items = [];
        $disk = null;
        $folder = null;
        $base = null;
        $source = $kind === 'static' ? $form->source() : null;

        if ($kind === 'static') {
            $report = $form->precheck();
            if (!$report['ok']) {
                throw ValidationException::withMessages(['form.files' => 'فایل‌ها آمادهٔ ذخیره نیستند. مشکل‌های نوشته‌شده زیر بخش انتخاب فایل را برطرف کنید و دوباره فایل‌ها را انتخاب کنید.']);
            }

            $items = array_map(fn ($file) => [$file, (string) $file->getClientOriginalName()], array_values($form->files));
            $settings = ['entry' => (string) config('launchpad.entry'), 'files_count' => $report['count'], 'size' => $report['bytes']];
            $disk = Storage::disk('public');
            $folder = (string) Str::ulid();
            $base = "launchpad/files/{$folder}";
        } else {
            $settings = ['site' => $form->site, 'path' => $form->path];
        }

        if ($form->public) {
            $settings['public'] = true;
        }

        try {
            if ($disk !== null) {
                foreach ($items as [$file, $name]) {
                    $stream = $file->readStream();
                    $written = is_resource($stream) && $disk->writeStream($base . '/' . Launchpad::storedName($name), $stream);
                    is_resource($stream) && fclose($stream);
                    if (!$written) {
                        throw new RuntimeException('فایل‌های برنامه ذخیره نشدند. دوباره تلاش کنید؛ اگر مشکل ادامه داشت، به پشتیبانی اطلاع دهید.');
                    }
                }
            }

            [$row, $oldDirectory, $replaced] = DB::transaction(function () use ($form, $user, $title, $dept, $replace, $kind, $folder, $settings, $source, $items, $base) {
                Department::query()->whereKey($dept)->lockForUpdate()->first(['code']);

                $existing = Launchpad::query()->where('department_id', $dept)->where('title', $title)->lockForUpdate()->first(['id', 'user_id', 'department_id', 'title', 'folder', 'refresh_due_at', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at']);

                if ($existing !== null) {
                    if (!$replace || !LaunchpadAccessPolicy::canManage($existing, $user)) {
                        throw ValidationException::withMessages(['form.title' => self::DUPLICATE_TITLE_MESSAGE]);
                    }
                    if ($kind === 'link') {
                        $this->assertSiteFree((string) $form->site, $user, $existing->id);
                    }
                    $oldDirectory = $existing->directory();
                    $hadSource = $existing->refresh_due_at !== null;
                    $existing->update([
                        'kind' => $kind,
                        'folder' => $folder,
                        'description' => filled($form->description) ? $form->description : null,
                        'settings' => $settings,
                        'seen_by' => [],
                        ...($form->public ? ['audience' => []] : []),
                        ...match (true) {
                            $kind === 'link' => ['source' => null, 'refresh_due_at' => null],
                            $source !== null => ['source' => $source, 'refresh_due_at' => now()],
                            default => [],
                        },
                    ]);

                    if ($hadSource) {
                        $this->carryData($oldDirectory, $base, $items);
                    }

                    return [$existing, $oldDirectory, true];
                }

                if (!$user->isDeveloper() && Launchpad::query()->where('department_id', $dept)->count() >= (int) config('launchpad.max_per_department')) {
                    throw ValidationException::withMessages([$kind === 'link' ? 'form.site' : 'form.files' => 'ظرفیت ثبت برنامه در این دپارتمان پر است. یکی از برنامه‌های قبلی را حذف کنید یا از مسئول برنامه بخواهید این کار را انجام دهد.']);
                }

                if ($kind === 'link') {
                    $this->assertSiteFree((string) $form->site, $user);
                }

                $audience = $form->public ? [] : User::query()->visibleOnBoard()
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
                    'active' => true,
                    'kind' => $kind,
                    'title' => $title,
                    'description' => filled($form->description) ? $form->description : null,
                    'folder' => $folder,
                    'audience' => $audience,
                    'accessible_from' => filled($form->accessible_from) ? $form->accessible_from : null,
                    'accessible_to' => filled($form->accessible_to) ? $form->accessible_to : null,
                    'expire_at' => filled($form->expire_at) ? $form->expire_at : null,
                    'settings' => $settings,
                    'source' => $source,
                    'refresh_due_at' => $source === null ? null : now(),
                ]);

                return [$row, null, false];
            });
        } catch (Throwable $e) {
            if ($base !== null) {
                self::deleteStoredDirectory($base);
            }

            throw $e instanceof QueryException && ($e->errorInfo[1] ?? null) === 1062
                ? ValidationException::withMessages(['form.title' => self::DUPLICATE_TITLE_MESSAGE])
                : $e;
        }

        if ($oldDirectory !== null) {
            self::deleteStoredDirectory($oldDirectory);
        }

        if ($replaced || filled($row->audience)) {
            StateService::flush();
        }

        if ($row->refresh_due_at !== null && $row->isLive()) {
            RefreshLaunchpadJob::dispatch((int) $row->id);
        }

        return ['launchpad' => $row, 'replaced' => $replaced];
    }

    private function assertSiteFree(string $site, User $user, ?int $except = null): void
    {
        $taken = Launchpad::query()
            ->where('kind', 'link')
            ->where('settings->site', $site)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->first(['id', 'user_id', 'department_id', 'title']);

        if ($taken === null) {
            return;
        }

        throw new RuntimeException(LaunchpadAccessPolicy::canManage($taken, $user)
            ? 'برای این سامانه قبلاً میان‌بر «' . $taken->title . '» در دپارتمان «' . (Department::getCachedOptions()[$taken->department_id] ?? $taken->department_id) . '» ساخته شده است؛ آن را ویرایش یا جایگزین کنید.'
            : 'برای این سامانه قبلاً میان‌بر ساخته شده است. از مدیر بخواهید دسترسی شما را به آن اضافه کند.');
    }

    private function carryData(?string $old, ?string $base, array $items): void
    {
        $name = Launchpad::storedName((string) config('launchpad.refresh.file'));
        if ($old === null || $base === null || $name === null || collect($items)->contains(fn ($item) => Launchpad::storedName($item[1]) === $name)) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists("{$old}/{$name}")) {
            $disk->copy("{$old}/{$name}", "{$base}/{$name}");
        }
    }
}
