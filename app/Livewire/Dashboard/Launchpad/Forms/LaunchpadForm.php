<?php

namespace App\Livewire\Dashboard\Launchpad\Forms;

use App\Livewire\Dashboard\Launchpad\Actions\RefreshLaunchpadAction;
use App\Models\Launchpad;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public const HEADER_PATTERN = '/^(?!(?:host|content-length|transfer-encoding|connection)$)[A-Za-z0-9-]{1,64}$/iD';

    public const TOKEN_PATTERN = '/^[A-Za-z0-9_.-]{1,80}$/D';

    public const SHEET_PATTERN = '/^[A-Za-z0-9_-]{20,80}$/D';

    public const SERVICE_EMAIL_PATTERN = '/^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+\.iam\.gserviceaccount\.com$/D';

    public const PEM_PATTERN = '/^-----BEGIN [A-Z ]*PRIVATE KEY-----/D';

    public array $files = [];

    public string $sourceUrl = '';

    public string $sourceMethod = 'GET';

    public string $sourceBody = '';

    public string $sourceAuth = 'off';

    public array $sourceEnv = [];

    public array $audience = [];

    public bool $public = false;

    public ?string $accessible_from = null;

    public ?string $accessible_to = null;

    public ?string $expire_at = null;

    public function normalizedTitle(): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $this->title));
    }

    public function normalizeSource(): void
    {
        $clean = fn (string $value): string => trim((string) preg_replace('/[\x{061C}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $value));

        $this->sourceUrl = $clean($this->sourceUrl);
        $this->sourceBody = $clean($this->sourceBody);

        foreach ($this->sourceEnv as $field => $value) {
            if (is_string($value)) {
                $this->sourceEnv[$field] = $field === 'ranges'
                    ? implode("\n", array_map($clean, preg_split('/\R/u', $value) ?: []))
                    : $clean($value);
            }
        }
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|min:3|max:120',
            'description' => 'nullable|string|max:500',
            'department_id' => 'required|string|exists:departments,code',
            'kind' => 'required|string|in:static,link',
            'public' => 'boolean',
            'audience' => $this->public ? 'array' : 'array|max:' . (int) config('launchpad.max_audience'),
            'audience.*' => 'integer',
            'accessible_from' => 'nullable|date',
            'accessible_to' => ['nullable', 'date', 'after_or_equal:today', ...(filled($this->accessible_from) ? ['after_or_equal:accessible_from'] : [])],
            'expire_at' => ['nullable', 'date', 'after_or_equal:today', ...(filled($this->accessible_from) ? ['after_or_equal:accessible_from'] : [])],
        ];

        if ($this->kind === 'link') {
            $rules['site'] = ['required', 'string', Rule::in(array_keys(Launchpad::ssoSites()))];
            $rules['path'] = ['nullable', 'max:200', 'regex:' . self::PATH_PATTERN];
        } else {
            $rules['files'] = 'required|array|min:1|max:' . self::maxFiles();
            $rules['files.*'] = 'file|max:' . (int) config('file_upload_limits.launchpad');
            $call = $this->callFields();
            $rules['sourceMethod'] = ['required', Rule::in(array_keys(self::callMatrix()['method']['options']))];
            $rules['sourceAuth'] = ['required', Rule::in(array_keys(self::authChoices()))];
            $rules['sourceEnv'] = 'array';

            if (isset($call['url'])) {
                $rules['sourceUrl'] = ['bail', 'required', ...self::urlRules()];
            }

            if (isset($call['body']) && filled($this->sourceUrl)) {
                $rules['sourceBody'] = ['bail', 'nullable', 'string', 'max:2000', 'json'];
            }

            foreach ($this->hasSource() ? $this->sourceFields() : [] as $field => $spec) {
                $rules["sourceEnv.{$field}"] = [
                    'bail',
                    $spec['required'] && $spec['default'] === null ? 'required' : 'nullable',
                    'string',
                    ...match (true) {
                        isset($spec['options']) => [Rule::in(array_keys($spec['options']))],
                        $field === 'url' => self::urlRules(),
                        $field === 'name' => ['regex:' . self::HEADER_PATTERN],
                        $field === 'value' => ['max:2000', 'regex:/^[^\x00-\x1F\x7F]+$/'],
                        $field === 'body' => ['max:2000', 'json', self::jsonObjectRule()],
                        $field === 'token' => ['regex:' . self::TOKEN_PATTERN],
                        $field === 'sheet' => ['regex:' . self::SHEET_PATTERN],
                        $field === 'ranges' => ['max:1200', self::rangesRule()],
                        $field === 'key' => ['max:10000', self::serviceKeyRule()],
                        default => [],
                    },
                ];
            }
        }

        return $rules;
    }

    public function validationAttributes(): array
    {
        $attributes = ['title' => 'نام برنامه', 'description' => 'توضیح کوتاه', 'department_id' => 'دپارتمان', 'kind' => 'نوع برنامه', 'site' => 'سامانهٔ مقصد', 'path' => 'صفحهٔ موردنظر در سامانه', 'files' => 'فایل‌های برنامه', 'files.*' => 'فایل انتخاب‌شده', 'audience' => 'مخاطبان برنامه', 'audience.*' => 'مخاطب', 'accessible_from' => 'تاریخ شروع دسترسی', 'accessible_to' => 'تاریخ پایان دسترسی', 'expire_at' => 'آخرین روز نگهداری برنامه', 'sourceUrl' => 'نشانی دریافت اطلاعات', 'sourceMethod' => 'روش دریافت اطلاعات', 'sourceBody' => 'اطلاعات ارسالی', 'sourceAuth' => 'نوع منبع داده'];

        foreach (self::authMatrix() as $fields) {
            foreach ($fields as $field => $spec) {
                $attributes["sourceEnv.{$field}"] = $spec['label'];
            }
        }

        return $attributes;
    }

    protected function messages(): array
    {
        return [
            'path.regex' => 'فقط بخش بعد از نشانی سامانه را وارد کنید؛ مثلاً reports/q1. نشانی کامل، علامت / در ابتدا یا دو نقطهٔ پشت سر هم (..) پذیرفته نمی‌شود.',
            'files.required' => 'فایل index.html و فایل‌های همراه برنامه را با هم انتخاب کنید.',
            'sourceUrl.regex' => 'نشانی دریافت اطلاعات را در یک خط و بدون فاصله وارد کنید.',
            'sourceBody.json' => 'قالب اطلاعات ارسالی درست نیست. متن JSON را مطابق نمونهٔ راهنمای سرویس وارد کنید.',
            'sourceEnv.url.regex' => 'نشانی ورود را در یک خط و بدون فاصله وارد کنید.',
            'sourceEnv.name.regex' => 'نام کلید در درخواست (Header) باید حداکثر ۶۴ کاراکتر و فقط شامل حروف انگلیسی، عدد و خط تیره باشد. نام‌های Host، Content-Length، Transfer-Encoding و Connection پذیرفته نمی‌شوند؛ نام درست را از راهنمای سرویس بگیرید.',
            'sourceEnv.value.regex' => 'کلید یا توکن را کامل و در یک خط کپی کنید؛ خط جدید و کاراکترهای نامرئی را حذف کنید.',
            'sourceEnv.body.json' => 'قالب اطلاعات ورود درست نیست. متن JSON را مطابق نمونهٔ راهنمای سرویس وارد کنید.',
            'sourceEnv.sheet.regex' => 'شناسهٔ گوگل‌شیت را از بخش بین /d/ و /edit در نشانی فایل کپی کنید. این کد باید ۲۰ تا ۸۰ کاراکتر و فقط شامل حروف انگلیسی، عدد، خط تیره یا زیرخط باشد.',
            'sourceEnv.token.regex' => 'محل کد دسترسی را مثل access_token یا data.token وارد کنید؛ حداکثر ۸۰ کاراکتر و فقط حروف انگلیسی، عدد، نقطه، خط تیره یا زیرخط.',
        ];
    }

    private static function urlRules(): array
    {
        return ['string', 'max:500', 'regex:/^[^\s\x00-\x1F\x7F]+$/', 'url:https', function (string $attribute, mixed $value, Closure $fail): void {
            if (!self::urlAllowed((string) $value)) {
                $fail(':attribute را با https:// و نام سایت وارد کنید؛ مثلاً https://api.example.com/report. نشانی نباید شامل نام کاربری، رمز عبور، شمارهٔ پورت غیر از ۴۴۳، نشانی عددی IP یا نقطهٔ پایانی بعد از نام سایت باشد.');
            }
        }];
    }

    public static function urlAllowed(string $url): bool
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && !isset($parts['user']) && !isset($parts['pass'])
            && ($parts['port'] ?? 443) === 443
            && preg_match('/^[a-z0-9.-]+$/', $host) === 1 && !str_ends_with($host, '.') && !str_contains($url, '\\')
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && preg_match('/[\x00-\x20\x7F]/', $url) !== 1;
    }

    private static function jsonObjectRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (!is_object(json_decode((string) $value))) {
                $fail(':attribute باید در قالب JSON و داخل آکولاد { } باشد؛ نمونهٔ آماده را از راهنمای سرویس بگیرید.');
            }
        };
    }

    private static function rangesRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $lines = self::rangeLines((string) $value);

            if ($lines === [] || count($lines) > 10 || collect($lines)->contains(fn (string $line) => mb_strlen($line) > 100 || preg_match('/[\x00-\x1F\x7F]/', $line) === 1)) {
                $fail('حداقل یک محدودهٔ سلول بنویسید؛ مثلاً Sheet1!A1:H17. هر محدوده در یک خط، حداکثر ۱۰ خط و هر خط تا ۱۰۰ کاراکتر باشد. علامت‌های اضافی را حذف کنید.');
            } elseif (collect($lines)->contains(fn (string $line) => RefreshLaunchpadAction::rangeParts($line) === null)) {
                $fail('هر محدوده باید نام برگه و سلول‌ها را داشته باشد؛ مثلاً Sheet1!A1:H17 یا \'داشبورد پروژه\'!A1. نام فارسی یا دارای فاصله را بین دو علامت \' بگذارید.');
            }
        };
    }

    private static function serviceKeyRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (self::serviceAccount((string) $value) === null) {
                $fail('کلید گوگل درست نیست. تمام متن فایل JSON کلید حساب سرویس را کپی کنید؛ اگر این فایل را ندارید، از مسئول فنی بگیرید.');
            }
        };
    }

    public static function rangeLines(string $ranges): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $ranges) ?: []), fn (string $line) => $line !== ''));
    }

    public static function serviceAccount(string $json): ?array
    {
        $data = json_decode($json, true);
        $email = is_array($data) ? ($data['client_email'] ?? null) : null;
        $key = is_array($data) ? ($data['private_key'] ?? null) : null;

        if (($data['type'] ?? null) !== 'service_account' || !is_string($email) || preg_match(self::SERVICE_EMAIL_PATTERN, $email) !== 1 || !is_string($key) || preg_match(self::PEM_PATTERN, $key) !== 1 || @openssl_pkey_get_private($key) === false) {
            return null;
        }

        return ['email' => $email, 'key' => $key];
    }

    public static function callMatrix(): array
    {
        return (array) config('launchpad.refresh.call');
    }

    public static function authMatrix(): array
    {
        return array_map(fn (array $option) => $option['fields'], (array) config('launchpad.refresh.auth'));
    }

    public static function authChoices(): array
    {
        return ['off' => 'بدون منبع داده', ...array_map(fn (array $option) => $option['label'], (array) config('launchpad.refresh.auth'))];
    }

    public function callFields(): array
    {
        $scope = ['method' => $this->sourceMethod, 'auth' => $this->sourceAuth];

        return array_filter(self::callMatrix(), fn (array $spec) => self::holds($spec['when'] ?? null, $scope));
    }

    public function sourceFields(): array
    {
        $fields = self::authMatrix()[$this->sourceAuth] ?? [];
        $scope = ['auth' => $this->sourceAuth];

        foreach ($fields as $key => $spec) {
            $scope[$key] = filled($this->sourceEnv[$key] ?? null) ? $this->sourceEnv[$key] : $spec['default'];
        }

        return array_filter($fields, fn (array $spec) => self::holds($spec['when'] ?? null, $scope));
    }

    public function hasSource(): bool
    {
        return $this->sourceAuth !== 'off';
    }

    private static function holds(?string $when, array $scope): bool
    {
        foreach (explode(',', (string) $when) as $term) {
            if (preg_match('/^(\w+)(!?=)(.+)$/D', $term, $match) === 1 && in_array($scope[$match[1]] ?? null, explode('|', $match[3]), true) === ($match[2] === '!=')) {
                return false;
            }
        }

        return true;
    }

    public function sourceRows(): array
    {
        $name = $this->getPropertyName();
        $models = ['url' => "{$name}.sourceUrl", 'method' => "{$name}.sourceMethod", 'body' => "{$name}.sourceBody"];
        $rows = ['type' => [[
            'type' => 'select',
            'label' => 'نوع منبع داده',
            'options' => self::authChoices(),
            'hint' => 'ابتدا منبع دریافت اطلاعات را انتخاب کنید. سایر گزینه‌ها بر اساس انتخاب شما نمایش داده می‌شوند.',
            'placeholder' => null,
            'key' => 'auth',
            'model' => "{$name}.sourceAuth",
        ]]];

        foreach ($this->callFields() as $field => $spec) {
            $rows[$spec['row']][] = [...$spec, 'key' => "call-{$field}", 'model' => $models[$field]];
        }

        foreach ($this->sourceFields() as $field => $spec) {
            $rows[$spec['row']][] = [...$spec, 'key' => "{$this->sourceAuth}-{$field}", 'model' => "{$name}.sourceEnv.{$field}"];
        }

        return $rows;
    }

    public function source(array $stored = []): ?array
    {
        if (!$this->hasSource()) {
            return null;
        }

        $kept = ($stored['auth'] ?? null) === $this->sourceAuth ? (array) ($stored['env'] ?? []) : [];
        $env = [];
        foreach ($this->sourceFields() as $field => $spec) {
            $value = trim((string) ($this->sourceEnv[$field] ?? ''));
            $env[$field] = $value !== '' ? $value : ($spec['secret'] ? ($kept[$field] ?? $spec['default']) : $spec['default']);
        }

        if ($this->sourceAuth === 'google') {
            $typed = trim((string) ($this->sourceEnv['key'] ?? ''));
            $account = match (true) {
                $env['access'] !== 'private' => [],
                $typed === '' && filled($kept['key'] ?? null) && filled($kept['email'] ?? null) => ['email' => $kept['email'], 'key' => $kept['key']],
                default => self::serviceAccount($typed),
            };
            unset($env['key']);

            return $account === null ? null : ['auth' => 'google', 'env' => [...$env, 'ranges' => implode("\n", self::rangeLines((string) $env['ranges'])), ...$account]];
        }

        $body = trim($this->sourceBody);

        return [
            'url' => trim($this->sourceUrl),
            'method' => $this->sourceMethod,
            'body' => isset($this->callFields()['body']) && $body !== '' ? $body : null,
            'auth' => $this->sourceAuth,
            'env' => $env,
        ];
    }

    public function verifyTabs(): void
    {
        $env = $this->sourceEnv;
        if (!config('launchpad.refresh.verify_tabs') || $this->kind === 'link' || $this->sourceAuth !== 'google' || ($env['access'] ?? 'public') !== 'public') {
            return;
        }

        $known = app(RefreshLaunchpadAction::class)->sheetTabs((string) ($env['sheet'] ?? ''));
        if ($known === null) {
            return;
        }

        $keys = array_map(RefreshLaunchpadAction::tabKey(...), $known);

        foreach (self::rangeLines((string) ($env['ranges'] ?? '')) as $line) {
            $tab = RefreshLaunchpadAction::rangeParts($line)[0] ?? null;
            if ($tab !== null && !in_array(RefreshLaunchpadAction::tabKey($tab), $keys, true)) {
                throw ValidationException::withMessages(["{$this->getPropertyName()}.sourceEnv.ranges" => "برگهٔ {$tab} در فایل Google Sheets پیدا نشد. نام برگه‌های موجود: " . implode('، ', array_slice(array_values(array_filter($known, fn (string $name) => strtolower(trim($name)) !== 'help')), 0, 8))]);
            }
        }
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
            $key = Launchpad::storedName($name) ?? $lower;
            $bytes += $size;

            $reason = match (true) {
                $name === '' || mb_strlen($name) > $maxName || preg_match('~[/\\\\\x00-\x1F\x7F]|\.\.~', $name) === 1 => 'name',
                str_starts_with($name, '.') => 'hidden',
                collect($denied)->contains(fn ($fragment) => str_contains($lower . '.', (string) $fragment . '.')) => 'executable',
                !array_key_exists(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $types) => 'extension',
                $size > $limit => 'size',
                isset($seen[$key]) => 'duplicate',
                self::hasPhpTag($file) => 'content',
                default => null,
            };

            $seen[$key] = true;

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
