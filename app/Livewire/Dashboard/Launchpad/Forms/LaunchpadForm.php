<?php

namespace App\Livewire\Dashboard\Launchpad\Forms;

use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class LaunchpadForm extends Form
{
    public string $title = '';

    public string $description = '';

    public string $department_id = '';

    public string $kind = 'static';

    public string $site = '';

    public string $path = '';

    public const PATH_PATTERN = '/^(?!\/)(?!.*\.\.)[A-Za-z0-9\-._~%?=&#\/]{0,200}$/D';

    public array $files = [];

    public array $audience = [];

    public ?string $accessible_from = null;

    public ?string $accessible_to = null;

    public ?string $expire_at = null;

    public function normalizedTitle(): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $this->title));
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|min:3|max:120',
            'description' => 'nullable|string|max:500',
            'department_id' => 'required|string|exists:departments,code',
            'kind' => 'required|string|in:static,link',
            'audience' => 'array|max:' . (int) config('launchpad.max_audience'),
            'audience.*' => 'integer',
            'accessible_from' => 'nullable|date',
            'accessible_to' => ['nullable', 'date', 'after_or_equal:today', ...(filled($this->accessible_from) ? ['after_or_equal:accessible_from'] : [])],
            'expire_at' => ['nullable', 'date', 'after_or_equal:today', ...(filled($this->accessible_from) ? ['after_or_equal:accessible_from'] : [])],
        ];

        if ($this->kind === 'link') {
            $rules['site'] = ['required', 'string', Rule::in(array_column((array) config('app.siblings'), 'name'))];
            $rules['path'] = ['nullable', 'max:200', 'regex:' . self::PATH_PATTERN];
        } else {
            $rules['files'] = 'required|array|min:1|max:' . self::maxFiles();
            $rules['files.*'] = 'file|max:' . (int) config('file_upload_limits.launchpad');
        }

        return $rules;
    }

    public static function maxFiles(): int
    {
        return min((int) config('launchpad.max_files'), (int) (ini_get('max_file_uploads') ?: 20));
    }

    public function precheck(): array
    {
        $types = (array) config('launchpad.types');
        $denied = (array) config('launchpad.denied');
        $entry = (string) config('launchpad.entry');
        $maxName = (int) config('launchpad.max_name_length');
        $limit = (int) config('file_upload_limits.launchpad') * 1024;
        $maxBytes = (int) config('file_upload_limits.launchpad_total') * 1024;
        $maxCount = self::maxFiles();
        $files = array_values(array_filter(is_array($this->files) ? $this->files : [], fn ($file) => $file instanceof TemporaryUploadedFile));
        $rejected = [];
        $seen = [];
        $bytes = 0;
        $hasEntry = false;

        foreach ($files as $file) {
            $name = (string) $file->getClientOriginalName();
            $size = (int) $file->getSize();
            $lower = mb_strtolower($name);
            $bytes += $size;

            $reason = match (true) {
                $name === '' || mb_strlen($name) > $maxName || preg_match('~[/\\\\\x00-\x1F\x7F]|\.\.~', $name) === 1 => 'name',
                str_starts_with($name, '.') => 'hidden',
                collect($denied)->contains(fn ($fragment) => str_contains($lower . '.', (string) $fragment . '.')) => 'executable',
                !array_key_exists(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $types) => 'extension',
                $size > $limit => 'size',
                isset($seen[$lower]) => 'duplicate',
                self::hasPhpTag($file) => 'content',
                default => null,
            };

            $seen[$lower] = true;

            if ($reason !== null) {
                $rejected[] = ['name' => $name, 'reason' => $reason];
            } elseif ($name === $entry) {
                $hasEntry = true;
            }
        }

        $errors = array_keys(array_filter([
            'empty' => $files === [],
            'too_many' => count($files) > $maxCount,
            'too_big' => $bytes > $maxBytes,
            'no_entry' => $files !== [] && !$hasEntry,
        ]));

        return [
            'ok' => $errors === [] && $rejected === [],
            'errors' => $errors,
            'rejected' => $rejected,
            'has_entry' => $hasEntry,
            'count' => count($files),
            'max_count' => $maxCount,
            'bytes' => $bytes,
            'max_bytes' => $maxBytes,
        ];
    }

    private static function hasPhpTag(TemporaryUploadedFile $file): bool
    {
        $stream = $file->readStream();
        $head = is_resource($stream) ? (string) fread($stream, 512) : '';
        is_resource($stream) && fclose($stream);

        return stripos($head, '<?php') !== false || str_contains($head, '<?=');
    }
}
