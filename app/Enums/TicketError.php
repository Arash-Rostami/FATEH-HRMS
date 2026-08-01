<?php

namespace App\Enums;

enum TicketError: string
{
    case EffectivenessRequired = 'ERR-T001';

    public function message(): string
    {
        $text = match ($this) {
            self::EffectivenessRequired => 'برای بستن تیکت، ثبت اثربخشی توسط تخصیص‌گیرنده یا مدیر واحد الزامی است.',
        };

        return "[{$this->value}] {$text}";
    }

    public function throw(): never
    {
        throw new \Exception($this->message());
    }
}
