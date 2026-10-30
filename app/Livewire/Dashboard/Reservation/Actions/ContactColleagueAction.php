<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Enums\ReservationStatus;
use App\Models\Message;
use App\Models\NotifyLog;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Notify\Channels\SmsChannel;
use Exception;
use Illuminate\Support\Facades\RateLimiter;

class ContactColleagueAction
{

    public const PRESETS = [
        'contact' => [
            'icon' => 'call',
            'label' => 'تماس با من',
            'body' => 'سلام و وقت بخیر. در خصوص رزرو «:resource» در تاریخ :date، نیاز به هماهنگی با شما دارم. ممنون می‌شوم در فرصت مناسب با بنده تماس بگیرید.',
        ],

        'move' => [
            'icon' => 'sync_alt',
            'label' => 'جابه‌جایی رزرو',
            'body' => 'سلام و وقت بخیر. رزرو شما برای «:resource» در تاریخ :date، با توجه به نیاز به دسترسی به این فضا، موجب محدودیت در استفاده از مسیر عبور شده است. ممنون می‌شوم در صورت امکان، نسبت به جابه‌جایی رزرو اقدام فرمایید. از همکاری شما سپاسگزارم.',
        ],

        'need' => [
            'icon' => 'front_hand',
            'label' => 'آزادسازی منبع',
            'body' => 'سلام و وقت بخیر. در تاریخ :date به «:resource» نیاز دارم. چنانچه در آن تاریخ به این منبع نیاز ندارید، ممنون می‌شوم در صورت امکان رزرو خود را لغو یا آزاد فرمایید تا امکان استفاده برای سایر همکاران فراهم شود. از همراهی و همکاری شما سپاسگزارم.',
        ],
    ];


    public bool $smsDelivered = true;

    public function execute(Reservation $reservation, User $sender, string $preset, ?SmsChannel $sms = null): Message
    {
        $this->assertSenderActive($sender);

        if (! isset(self::PRESETS[$preset])) {
            throw new Exception('پیام انتخاب‌شده معتبر نیست.');
        }

        $recipient = $this->recipientFor($reservation, $sender);

        $this->throttle($sender);

        if ($sms !== null) {
            $this->assertSmsReachable($recipient, $sms, $sender);
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => strtr(self::PRESETS[$preset]['body'], [
                ':resource' => $reservation->resource?->name ?? '—',
                ':date' => convertToPersian(toJalali($reservation->start_time, 'j F')),
            ]),
        ]);

        if ($sms !== null) {
            $this->smsDelivered = $sms->text($recipient->sms_number, $sender->name . ': ' . $message->body);
            NotifyLog::audit(NotifyLog::SOURCE_RESERVATION, $sender->id, $recipient->id, $recipient->sms_number, $this->smsDelivered);
        }

        return $message;
    }

    private function assertSenderActive(User $sender): void
    {
        if (! $sender->isActive()) {
            throw new Exception('حساب شما فعال نیست.');
        }
    }

    private function recipientFor(Reservation $reservation, User $sender): User
    {
        if ($reservation->user_id === $sender->id) {
            throw new Exception('این رزرو خودِ شماست.');
        }

        if (! in_array($reservation->status, [ReservationStatus::Active->value, ReservationStatus::Released->value], true)) {
            throw new Exception('این رزرو دیگر فعال نیست.');
        }

        return User::visibleOnBoard()->findOrFail($reservation->user_id);
    }

    private function assertSmsReachable(User $recipient, SmsChannel $sms, User $sender): void
    {
        if (tenantOff('notify:sms') || ! $sms->isConfigured()) {
            throw new Exception('سرویس پیامک در دسترس نیست.');
        }

        if (presence($recipient->presence)->isObscured() || blank($recipient->sms_number)) {
            throw new Exception('این همکار پیامک دریافت نمی‌کند.');
        }

        if (! RateLimiter::attempt("sms-to:{$recipient->id}:{$sender->id}", 3, fn() => true, 3600)) {
            throw new Exception('به این همکار اخیراً چند پیامک فرستاده شده است؛ بعداً دوباره تلاش کنید.');
        }
    }

    private function throttle(User $sender): void
    {
        if (! RateLimiter::attempt("reservation-contact:{$sender->id}", 5, fn() => true, 60)) {
            throw new Exception('تعداد پیام‌های شما زیاد است؛ کمی بعد دوباره تلاش کنید.');
        }
    }
}
