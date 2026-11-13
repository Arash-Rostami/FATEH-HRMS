<?php

namespace App\Livewire\Dashboard\AiReport\Forms;

use Closure;
use finfo;
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
                fn (string $attribute, mixed $value, Closure $fail) => $value instanceof UploadedFile
                    && self::isEncryptedOffice((new finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath() ?: $value->getPathname()) ?: '', (string) $value->getClientOriginalName())
                    && $fail(self::encryptedFileMessage()),
                'mimes:' . config('ai.report.upload.mimes') . ',zip',
                fn (string $attribute, mixed $value, Closure $fail) => $value instanceof UploadedFile
                    && $value->guessExtension() === 'zip'
                    && !self::isOfficeZip($value)
                    && $fail(self::unsupportedFileMessage()),
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
            'prompt.required' => __('resources/ai_report/strings.form.prompt_required'),
            'prompt.min' => __('resources/ai_report/strings.form.prompt_min', ['min' => convertToPersian($min)]),
            'prompt.max' => __('resources/ai_report/strings.form.prompt_max', ['max' => convertToPersian($max)]),
            'type.required' => __('resources/ai_report/strings.form.type'),
            'type.in' => __('resources/ai_report/strings.form.type'),
            'length.required' => __('resources/ai_report/strings.form.length'),
            'length.in' => __('resources/ai_report/strings.form.length'),
            'audience.required' => __('resources/ai_report/strings.form.audience'),
            'audience.in' => __('resources/ai_report/strings.form.audience'),
            'files.max' => __('resources/ai_report/strings.form.files_max', ['count' => convertToPersian($files)]),
            'files.*.file' => __('resources/ai_report/strings.errors.file_invalid'),
            'files.*.max' => __('resources/ai_report/strings.form.file_max', ['mb' => convertToPersian($mb)]),
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

    public static function isOfficeZip(UploadedFile $file): bool
    {
        return in_array(strtolower(pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION)), ['docx', 'xlsx'], true);
    }

    public static function encryptedFileMessage(): string
    {
        return __('resources/ai_report/strings.form.encrypted');
    }

    public static function unsupportedFileMessage(): string
    {
        return __('resources/ai_report/strings.form.unsupported', ['exts' => str_replace(',', ', ', (string) config('ai.report.upload.mimes'))]);
    }
}
