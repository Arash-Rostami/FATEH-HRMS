<?php

namespace App\Services\ProjectTask;

use App\Models\Department;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class TasksheetShareService
{
    public static function managersFor(User $subject): Collection
    {
        $code = $subject->profile?->department_id;

        if (!$code) {
            return collect();
        }

        $heads = User::headsOfDepartment($code);

        if (!$heads->contains('id', $subject->id)) {
            return $heads;
        }

        $parent = Department::getCachedModels()->get($code)?->subordinate_to;

        return $parent ? User::headsOfDepartment($parent) : collect();
    }

    public static function managerFor(User $subject): ?User
    {
        return self::managersFor($subject)->sortBy('id')->first();
    }

    public function shareWithManager(User $subject, ?User $recipient = null, ?User $requestedBy = null, array $windowParams = []): array
    {
        $manager = $recipient ?? self::managerFor($subject);

        if (!$manager) {
            return [
                'success' => false,
                'manager' => null,
                'message' => 'مدیر این کاربر به‌طور خودکار شناسایی نشد.',
            ];
        }

        if ($manager->id === $subject->id) {
            return [
                'success' => false,
                'manager' => null,
                'message' => 'گیرنده نمی‌تواند خود کاربر باشد.',
            ];
        }

        $signedUrl = URL::temporarySignedRoute(
            'tasksheet.shared',
            now()->addDays(7),
            array_merge(['subject' => $subject->id], $windowParams)
        );

        $body = $requestedBy && $requestedBy->id !== $subject->id
            ? "گزارش تسک‌شیت {$subject->name} توسط مدیریت با شما به اشتراک گذاشته شد."
            : "{$subject->name} یک گزارش تسک‌شیت با شما به اشتراک گذاشت.";

        Notification::make()
            ->title('گزارش تسک‌شیت به اشتراک گذاشته شد')
            ->body($body)
            ->actions([
                Action::make('view')->label('مشاهده')->url($signedUrl)->openUrlInNewTab(),
            ])
            ->sendToDatabase($manager);

        return [
            'success' => true,
            'manager' => $manager,
            'message' => $recipient
                ? 'گزارش برای گیرندهٔ انتخاب‌شده ارسال شد.'
                : 'گزارش برای مدیرِ همین کاربر ارسال شد.',
        ];
    }
}
