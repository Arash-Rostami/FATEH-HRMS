<?php

namespace App\Enums;

enum NotifyEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'ایجاد',
            self::Updated => 'ویرایش',
            self::Deleted => 'حذف',
        };
    }

    public static function options(): array
    {
        return array_combine(array_column(self::cases(), 'value'), array_map(fn(self $e) => $e->label(), self::cases()));
    }
}
