<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class FileSizeByType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $mime = (string) $value->getMimeType();

        [$tier, $label] = match (true) {
            str_starts_with($mime, 'video/') => ['video', 'ویدیو'],
            str_starts_with($mime, 'image/') => ['image', 'تصویر'],
            default => ['document', 'فایل'],
        };

        $limitKb = (int) config("file_upload_limits.{$tier}");

        if ($value->getSize() / 1024 > $limitKb) {
            $fail("حجم {$label} نباید بیشتر از " . intdiv($limitKb, 1024) . ' مگابایت باشد.');
        }
    }
}
