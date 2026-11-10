<?php

namespace App\Livewire\Dashboard\Correspondence\Forms;

use App\Livewire\Dashboard\AiReport\Forms\ComposerForm;
use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Models\Correspondence;
use App\Models\User;
use Closure;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Morilog\Jalali\CalendarUtils;

class LetterForm extends Form
{
    public const ROLE_APPROVER = 'approver';

    public const ROLE_FYI = 'fyi';

    public const ROLES = [self::ROLE_APPROVER, self::ROLE_FYI];

    public const DELIVERIES = ['link', 'pdf', 'both'];

    public const IMAGE_SIZES = Correspondence::BLOCK_SIZES;

    public const IMAGE_ALIGNS = Correspondence::BLOCK_ALIGNS;

    public const MODES = [Correspondence::MODE_SIMPLE, Correspondence::MODE_SMART];

    public const AUDIENCES = ['management', 'internal', 'external'];

    public const TONES = ['formal', 'neutral', 'firm'];

    public const LENGTHS = ['short', 'standard', 'long'];

    public const SIGN_MODES = [Correspondence::SIGN_MODE_ELECTRONIC, Correspondence::SIGN_MODE_WET];

    public const ROUTES = [Correspondence::ROUTE_SEQUENTIAL, Correspondence::ROUTE_PARALLEL];

    public const CATEGORIES = [Correspondence::CATEGORY_NORMAL, Correspondence::CATEGORY_AUTHORITY];

    public const SCOPES = ['private', 'department'];

    public const MERGE_TOKENS = ['{گیرنده}', '{سازمان}', '{آدرس}', '{تاریخ}'];

    public const BLANK_BLOCK = ['type' => 'html', 'heading' => '', 'html' => ''];

    public string $type = 'letter';

    public ?int $letterheadId = null;

    public string $classification = Correspondence::CLASSIFICATION_NORMAL;

    public string $urgency = Correspondence::URGENCY_NORMAL;

    public string $signMode = Correspondence::SIGN_MODE_ELECTRONIC;

    public string $routeMode = Correspondence::ROUTE_SEQUENTIAL;

    public string $senderCategory = Correspondence::CATEGORY_NORMAL;

    public string $subject = '';

    public array $blocks = [self::BLANK_BLOCK];

    public array $parties = [];

    public string $deadline = '';

    public array $tags = [];

    public ?string $relatedType = null;

    public ?int $relatedId = null;

    public string $theirNumber = '';

    public string $theirDate = '';

    public string $sender = '';

    public string $recipientOrg = '';

    public string $recipientPerson = '';

    public string $recipientTitle = '';

    public string $mode = Correspondence::MODE_SIMPLE;

    public string $prompt = '';

    public string $audience = 'management';

    public string $tone = 'formal';

    public string $length = 'standard';

    public array $files = [];

    public mixed $image = null;

    public string $imageSize = 'medium';

    public string $imageAlign = 'center';

    public string $imageCaption = '';

    private ?string $persistedType = null;

    public static function limits(): array
    {
        $upload = (array) config('ai.correspondence.upload');

        return [
            'files' => (int) ($upload['max_files'] ?? 5),
            'per_file_kb' => (int) ($upload['per_file_kb'] ?? 10240),
            'total_kb' => (int) ($upload['total_kb'] ?? 25600),
            'image_kb' => (int) ($upload['image_kb'] ?? 512),
            'image_max_width' => (int) ($upload['image_max_width'] ?? 2000),
            'mimes' => (string) ($upload['mimes'] ?? 'pdf,jpg,jpeg,png,webp'),
            'image_mimes' => (string) ($upload['image_mimes'] ?? 'jpg,jpeg,png,webp'),
            'prompt_min' => (int) config('ai.correspondence.prompt.min'),
            'prompt_max' => (int) config('ai.correspondence.prompt.max'),
            'recipients' => (int) config('ai.correspondence.max_recipients'),
            'blocks' => (int) config('ai.correspondence.max_blocks'),
            'list_max' => (int) config('ai.correspondence.list_max'),
            'versions' => (int) config('ai.correspondence.max_versions'),
            'batch' => (int) config('ai.correspondence.max_batch'),
            'page_chars' => (array) config('ai.correspondence.page_chars'),
            'warn_pages' => (int) config('ai.correspondence.warn_pages'),
            'placeholder' => (string) config('ai.correspondence.placeholder'),
            'poll_seconds' => (int) config('ai.correspondence.poll_seconds'),
            'not_opened_days' => (int) config('ai.correspondence.not_opened_days'),
            'body_bytes' => (int) config('ai.correspondence.body_bytes'),
            'suggest_lines' => (int) config('ai.correspondence.size_suggest_lines'),
        ];
    }

    public static function blankParty(): array
    {
        return [
            'user_id' => null,
            'role' => self::ROLE_FYI,
            'print_in_cc' => false,
            'name' => '',
            'org' => '',
            'title' => '',
            'email' => '',
            'address' => '',
            'delivery' => 'link',
            'order' => 0,
        ];
    }

    public static function partyRows(array $parties): array
    {
        $blank = self::blankParty();

        return array_values(array_map(
            fn (array $party) => array_replace($blank, array_intersect_key($party, $blank)),
            array_filter($parties, 'is_array')
        ));
    }

    public static function directionOf(string $type): string
    {
        return config("ai.correspondence.types.{$type}.direction") === Correspondence::DIRECTION_IN
            ? Correspondence::DIRECTION_IN
            : Correspondence::DIRECTION_OUT;
    }

    public function normalize(): void
    {
        $this->deadline = $this->latinDate($this->deadline);
        $this->theirDate = $this->latinDate($this->theirDate);
        $this->subject = mb_substr(trim($this->subject), 0, 200);
        $this->sender = LetterRules::plain($this->sender, 200);
        $this->recipientOrg = LetterRules::plain($this->recipientOrg, 200);
        $this->recipientPerson = LetterRules::plain($this->recipientPerson, 200);
        $this->recipientTitle = LetterRules::plain($this->recipientTitle, 200);
        $this->theirNumber = mb_substr(trim($this->theirNumber), 0, 64);
        $this->relatedType = filled($this->relatedType) ? $this->relatedType : null;
        $this->mode = in_array($this->mode, self::MODES, true) ? $this->mode : Correspondence::MODE_SIMPLE;
        $this->signMode = in_array($this->signMode, self::SIGN_MODES, true) ? $this->signMode : Correspondence::SIGN_MODE_ELECTRONIC;
        $this->routeMode = in_array($this->routeMode, self::ROUTES, true) ? $this->routeMode : Correspondence::ROUTE_SEQUENTIAL;
    }

    public function loadLetter(Correspondence $letter): void
    {
        $spec = $letter->spec ?? [];
        $recipient = is_array($spec['recipient'] ?? null) ? $spec['recipient'] : [];
        $version = $letter->lastVersion();
        $this->type = (string) $letter->type;
        $this->letterheadId = $letter->letterhead_id;
        $this->classification = (string) $letter->classification;
        $this->urgency = (string) $letter->urgency;
        $this->signMode = (string) ($letter->sign_mode ?: Correspondence::SIGN_MODE_ELECTRONIC);
        $this->senderCategory = (string) ($letter->sender_category ?: Correspondence::CATEGORY_NORMAL);
        $this->subject = (string) $letter->subject;
        $this->routeMode = (string) ($letter->route_mode ?: Correspondence::ROUTE_SEQUENTIAL);
        $this->blocks = $spec['blocks'] ?? [self::BLANK_BLOCK];
        $this->parties = self::partyRows($letter->parties ?? []);
        $this->deadline = $letter->deadline_at ? toJalali($letter->deadline_at, 'Y/m/d') : '';
        $this->tags = $letter->tags ?? [];
        $this->relatedType = $letter->related_type;
        $this->relatedId = $letter->related_id;
        $this->theirNumber = (string) $letter->their_number;
        $this->theirDate = (string) $letter->their_date;
        $this->sender = (string) ($spec['sender']['name'] ?? '');
        $this->recipientOrg = (string) ($recipient['organization'] ?? '');
        $this->recipientPerson = (string) ($recipient['person'] ?? '');
        $this->recipientTitle = (string) ($recipient['title'] ?? '');
        $this->mode = (string) $letter->mode;
        $this->reset('audience', 'tone', 'length');
        $this->prompt = (string) ($version['prompt'] ?? '');
        foreach (['audience' => self::AUDIENCES, 'tone' => self::TONES, 'length' => self::LENGTHS] as $key => $allowed) {
            if (in_array($version[$key] ?? null, $allowed, true)) {
                $this->{$key} = $version[$key];
            }
        }
        $this->files = [];
        $this->image = null;
    }

    public function loadContent(Correspondence $saved): void
    {
        $spec = $saved->spec ?? [];
        $recipient = is_array($spec['recipient'] ?? null) ? $spec['recipient'] : [];
        $this->subject = (string) $saved->subject;
        $this->blocks = $spec['blocks'] ?? [self::BLANK_BLOCK];
        $this->recipientOrg = (string) ($recipient['organization'] ?? '');
        $this->recipientPerson = (string) ($recipient['person'] ?? '');
        $this->recipientTitle = (string) ($recipient['title'] ?? '');
    }

    public function forceSimpleWhenBlocked(): void
    {
        if (tenantOff('correspondence:ai') || $this->classification !== Correspondence::CLASSIFICATION_NORMAL) {
            $this->mode = Correspondence::MODE_SIMPLE;
            $this->prompt = '';
        }
    }

    public function addBlock(): void
    {
        if (count($this->blocks) >= self::limits()['blocks']) {
            return;
        }
        $this->blocks[] = self::BLANK_BLOCK;
    }

    public function removeBlock(int $index): void
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks) ?: [self::BLANK_BLOCK];
    }

    public function addParty(): void
    {
        if (count($this->parties) >= self::limits()['recipients']) {
            return;
        }
        $this->parties[] = self::blankParty();
    }

    public function removeParty(int $index): void
    {
        unset($this->parties[$index]);
        $this->parties = array_values($this->parties);
    }

    public function moveParty(int $index, int $by): void
    {
        $target = $index + ($by <=> 0);
        if (!isset($this->parties[$index], $this->parties[$target])) {
            return;
        }
        [$this->parties[$index], $this->parties[$target]] = [$this->parties[$target], $this->parties[$index]];
    }

    public function addTag(string $value): void
    {
        $this->tags = LetterRules::tags([...$this->tags, mb_substr($value, 0, 256)]);
    }

    public function removeTag(int $index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function applySuggestions(): void
    {
        $have = array_map('intval', array_column($this->parties, 'user_id'));
        foreach ($this->suggestedParties() as $party) {
            if (count($this->parties) >= self::limits()['recipients']) {
                return;
            }
            if (!in_array($party['user_id'], $have, true)) {
                $this->parties[] = $party;
            }
        }
    }

    public function rules(): array
    {
        $limits = self::limits();

        return [
            'type' => ['required', Rule::in($this->allowedTypes())],
            'letterheadId' => 'nullable|integer|min:1',
            'classification' => ['required', Rule::in((array) config('ai.correspondence.classifications'))],
            'urgency' => ['required', Rule::in((array) config('ai.correspondence.urgencies'))],
            'signMode' => ['required', Rule::in(self::SIGN_MODES)],
            'routeMode' => ['required', Rule::in(self::ROUTES)],
            'senderCategory' => ['nullable', Rule::in(self::CATEGORIES), 'required_if:type,incoming'],
            'subject' => 'required|string|min:1|max:200',
            'blocks' => 'array|max:' . $limits['blocks'],
            'blocks.*' => fn (string $attribute, mixed $value, Closure $fail) => $this->rejectBlock($value, $fail),
            'parties' => 'array|max:' . $limits['recipients'],
            'parties.*.user_id' => 'nullable|integer|min:1',
            'parties.*.role' => ['nullable', Rule::in(self::ROLES)],
            'parties.*.print_in_cc' => 'nullable|boolean',
            'parties.*.email' => 'nullable|email|max:200',
            'parties.*.delivery' => ['nullable', Rule::in(self::DELIVERIES)],
            'parties.*.address' => 'nullable|string|max:300',
            'parties.*.title' => 'nullable|string|max:200',
            'deadline' => ['nullable', 'regex:/^\d{4}\/\d{2}\/\d{2}$/'],
            'tags' => 'array|max:12',
            'tags.*' => 'string|max:32',
            'relatedType' => ['nullable', Rule::in((array) config('ai.correspondence.related_types'))],
            'relatedId' => 'nullable|integer|min:1|required_with:relatedType',
            'theirNumber' => 'nullable|string|max:64|required_if:type,incoming',
            'theirDate' => ['nullable', 'regex:/^\d{4}\/\d{2}\/\d{2}$/', 'required_if:type,incoming'],
            'sender' => 'nullable|string|max:200|required_if:type,incoming',
            'recipientOrg' => 'nullable|string|max:200',
            'recipientPerson' => 'nullable|string|max:200',
            'recipientTitle' => 'nullable|string|max:200',
            'mode' => ['required', Rule::in(self::MODES)],
            'prompt' => [
                'nullable',
                'string',
                'max:' . $limits['prompt_max'],
                'required_if:mode,smart',
                ...($this->mode === Correspondence::MODE_SMART ? ['min:' . $limits['prompt_min']] : []),
            ],
            'audience' => ['nullable', Rule::in(self::AUDIENCES)],
            'tone' => ['nullable', Rule::in(self::TONES)],
            'length' => ['nullable', Rule::in(self::LENGTHS)],
            'files' => 'nullable|array|max:' . $limits['files'],
            'files.*' => [
                'bail',
                'file',
                'max:' . $limits['per_file_kb'],
                fn (string $attribute, mixed $value, Closure $fail) => $value instanceof UploadedFile
                    && ComposerForm::isEncryptedOffice((new finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath() ?: $value->getPathname()) ?: '', (string) $value->getClientOriginalName())
                    && $fail(ComposerForm::encryptedFileMessage()),
                'mimes:' . $limits['mimes'],
            ],
            'image' => ['nullable', 'file', 'max:' . $limits['image_kb'], 'mimes:' . $limits['image_mimes']],
            'imageSize' => ['nullable', Rule::in(self::IMAGE_SIZES)],
            'imageAlign' => ['nullable', Rule::in(self::IMAGE_ALIGNS)],
            'imageCaption' => 'nullable|string|max:200',
        ];
    }

    protected function messages(): array
    {
        return [
            'subject.required' => 'موضوع نامه را بنویسید.',
            'subject.max' => 'موضوع نباید بیش از ۲۰۰ نویسه باشد.',
            'type.in' => 'نوع نامه معتبر نیست.',
            'theirNumber.required_if' => 'شماره نامهٔ طرف را وارد کنید.',
            'theirDate.required_if' => 'تاریخ نامهٔ طرف را وارد کنید.',
            'theirDate.regex' => 'تاریخ معتبر نیست.',
            'deadline.regex' => 'مهلت معتبر نیست.',
            'sender.required_if' => 'فرستنده را وارد کنید.',
            'senderCategory.required_if' => 'دستهٔ فرستنده را انتخاب کنید.',
            'prompt.required_if' => 'متن درخواست هوشمند را وارد کنید.',
            'prompt.min' => 'متن درخواست هوشمند کوتاه است.',
            'relatedId.required_with' => 'رکورد مرتبط را انتخاب کنید.',
            'files.max' => 'تعداد پیوست از سقف مجاز بیشتر است.',
            'files.*.mimes' => ComposerForm::unsupportedFileMessage(),
            'image.mimes' => 'تصویر باید jpg، png یا webp باشد.',
            'image.max' => 'حجم تصویر از سقف مجاز بیشتر است.',
        ];
    }

    public function validated(?string $persistedType = null): array
    {
        $this->persistedType = $persistedType;

        return $this->validate();
    }

    public function payload(): array
    {
        return [
            'type' => $this->type,
            'letterhead_id' => $this->letterheadId,
            'classification' => $this->classification,
            'urgency' => $this->urgency,
            'sign_mode' => $this->signMode,
            'route_mode' => $this->routeMode,
            'sender_category' => $this->senderCategory,
            'subject' => $this->subject,
            'blocks' => $this->blocks,
            'parties' => $this->parties,
            'deadline' => $this->deadline,
            'tags' => $this->tags,
            'related_type' => $this->relatedType,
            'related_id' => $this->relatedId,
            'their_number' => $this->theirNumber,
            'their_date' => $this->theirDate,
            'sender' => $this->sender,
            'recipient' => [
                'organization' => $this->recipientOrg,
                'person' => $this->recipientPerson,
                'title' => $this->recipientTitle,
            ],
            'mode' => $this->mode,
            'prompt' => $this->prompt,
            'audience' => $this->audience,
            'tone' => $this->tone,
            'length' => $this->length,
            'image_size' => $this->imageSize,
            'image_align' => $this->imageAlign,
            'image_caption' => $this->imageCaption,
        ];
    }

    private function suggestedParties(): array
    {
        if (self::directionOf($this->type) !== Correspondence::DIRECTION_IN
            || $this->senderCategory !== Correspondence::CATEGORY_AUTHORITY
            || !config('ai.correspondence.authority_cc_ceo')) {
            return [];
        }

        $active = User::getCachedActiveOptions();

        return User::query()->active()->whereHas('profile', fn ($q) => $q->where('position', 'ceo'))->limit(3)->pluck('id')
            ->filter(fn ($id) => $active->has($id))
            ->map(fn ($id) => [...self::blankParty(), 'user_id' => (int) $id, 'name' => (string) $active->get($id)])
            ->values()
            ->all();
    }

    private function allowedTypes(): array
    {
        $enabled = array_keys(array_filter(
            (array) config('ai.correspondence.types'),
            fn ($type) => is_array($type) && ($type['enabled'] ?? false) === true
        ));

        return filled($this->persistedType) ? array_values(array_unique([...$enabled, $this->persistedType])) : $enabled;
    }

    private function rejectBlock(mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail(__('resources/correspondence/strings.errors.block_invalid'));

            return;
        }

        $path = $value['path'] ?? null;
        if (is_string($path) && $path !== '' && !preg_match('#^correspondence/\d+/[A-Za-z0-9._-]+$#', $path)) {
            $fail(__('resources/correspondence/strings.errors.image_path_invalid'));
        }
    }

    private function latinDate(string $value): string
    {
        return str_replace('-', '/', CalendarUtils::convertNumbers(trim($value), true));
    }
}
