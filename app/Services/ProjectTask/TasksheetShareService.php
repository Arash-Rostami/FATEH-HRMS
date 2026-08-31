<?php

namespace App\Services\ProjectTask;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TasksheetShareService
{
    public function shareWithManager(User $subject, ?User $recipient = null, ?User $requestedBy = null, array $windowParams = []): array
    {
        $manager = $recipient;

        if (!$manager) {
            $deptCode = $subject->profile?->department_id;
            $manager = $deptCode ? User::highestRankingInDepartment($deptCode) : null;
        }

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
            now()->addDays(14),
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
            'message' => 'گزارش برای مدیر شما ارسال شد.',
        ];
    }
}
