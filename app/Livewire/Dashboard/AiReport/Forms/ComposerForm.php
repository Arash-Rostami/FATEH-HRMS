<?php

namespace App\Livewire\Dashboard\AiReport\Forms;

use Illuminate\Http\UploadedFile;
use Livewire\Form;

class ComposerForm extends Form
{
    public string $prompt = '';

    public array $files = [];

    public string $type = 'free';

    public string $length = 'standard';

    public string $audience = 'management';

    public ?string $block = null;

    public function rules(): array
    {
        $min = (int) config('ai.report.prompt.min');
        $max = (int) config('ai.report.prompt.max');
        $files = (int) config('ai.report.upload.max_files');
        $kb = (int) config('ai.report.upload.per_file_kb');

        return [
            'prompt' => "required|string|min:{$min}|max:{$max}",
            'type' => 'required|in:' . implode(',', array_keys((array) config('ai.report.types'))),
            'length' => 'required|in:' . implode(',', array_keys((array) config('ai.report.lengths'))),
            'audience' => 'required|in:' . implode(',', array_keys((array) config('ai.report.audiences'))),
            'block' => 'nullable|string|regex:/^\d{1,2}:\d{1,2}$/',
            'files' =>"nullable|array|max:{$files}",
            'files.*' => [
                'bail',
                'file',
                'max:' . $kb,
                fn (string $attribute, mixed $value, \Closure $fail) => $value instanceof UploadedFile
                    && self::isEncryptedOffice((new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath() ?: $value->getPathname()) ?: '', (string) $value->getClientOriginalName())
                    && $fail(self::encryptedFileMessage()),
                'mimes:' . config('ai.report.upload.mimes'),
            ],
        ];
    }

    protected function messages(): array
    {
        $min = (int) config('ai.report.prompt.min');
        $max = (int) config('ai.report.prompt.max');
        $files = (int) config('ai.report.upload.max_files');
        $mb = intdiv((int) config('ai.report.upload.per_file_kb'), 1024);

        return [
            'prompt.required' => 'متن درخواست را وارد کنید.',
            'prompt.min' => 'متن درخواست باید حداقل ' . convertToPersian($min) . ' نویسه باشد.',
            'prompt.max' => 'متن درخواست نباید بیش از ' . convertToPersian($max) . ' نویسه باشد.',
            'type.required' => 'نوع گزارش معتبر نیست.',
            'type.in' => 'نوع گزارش معتبر نیست.',
            'length.required' => 'حجم گزارش معتبر نیست.',
            'length.in' => 'حجم گزارش معتبر نیست.',
            'audience.required' => 'مخاطب گزارش معتبر نیست.',
            'audience.in' => 'مخاطب گزارش معتبر نیست.',
            'files.max' => 'حداکثر ' . convertToPersian($files) . ' فایل می‌توانید پیوست کنید.',
            'files.*.file' => 'فایل پیوست معتبر نیست.',
            'files.*.max' => 'حجم هر فایل نباید بیش از ' . convertToPersian($mb) . ' مگابایت باشد.',
            'files.*.mimes' => self::unsupportedFileMessage(),
        ];
    }

    public function validated(): array
    {
        return $this->validate();
    }

    public static function isEncryptedOffice(string $mime, string $name): bool
    {
        return in_array($mime, ['application/CDFV2-encrypted', 'application/encrypted'], true)
            || ($mime === 'application/CDFV2' && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['docx', 'xlsx'], true));
    }

    public static function encryptedFileMessage(): string
    {
        return 'این فایل با رمز عبور محافظت شده است؛ آن را بدون رمز عبور ذخیره کنید و دوباره پیوست کنید.';
    }

    public static function unsupportedFileMessage(): string
    {
        $exts = str_replace(',', ', ', (string) config('ai.report.upload.mimes'));

        return 'قالب فایل پشتیبانی نمی‌شود. مجاز: ' . $exts . '. به‌جای .xls و .doc از .xlsx و .docx استفاده کنید؛ .ppt و .pptx پذیرفته نمی‌شود.';
    }
}
