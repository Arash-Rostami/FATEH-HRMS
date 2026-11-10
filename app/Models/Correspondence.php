<?php

namespace App\Models;

use App\Models\Concerns\HasDailyQuota;
use App\Models\Concerns\HasGenerationLifecycle;
use App\Models\Concerns\HasSharedRecipients;
use App\Models\Concerns\HasUser;
use App\Models\Concerns\HasVersionHistory;
use App\Models\Concerns\Hooks\HasCorrespondenceHooks;
use App\Services\ContentSanitizerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

class Correspondence extends Model
{
    use HasDailyQuota,
        HasGenerationLifecycle,
        HasSharedRecipients,
        HasUser,
        HasVersionHistory,
        SoftDeletes,
        HasCorrespondenceHooks;

    public const STATUS_DRAFT = 'draft', STATUS_GENERATING = 'generating', STATUS_FAILED = 'failed', STATUS_SAVED = 'saved', STATUS_SHARED = 'shared', STATUS_APPROVED = 'approved', STATUS_REJECTED = 'rejected', STATUS_WITHDRAWN = 'withdrawn', STATUS_VOID = 'void', STATUS_SUPERSEDED = 'superseded', STATUS_RECEIVED = 'received', STATUS_ROUTED = 'routed', STATUS_DONE = 'done';

    public const DRAFT_LIKE = [self::STATUS_DRAFT, self::STATUS_GENERATING, self::STATUS_FAILED];

    public const COMMENTABLE = [self::STATUS_SHARED, self::STATUS_RECEIVED, self::STATUS_ROUTED];

    public const CLASSIFICATION_NORMAL = 'normal', CLASSIFICATION_CONFIDENTIAL = 'confidential', CLASSIFICATION_SECRET = 'secret';

    public const DIRECTION_OUT = 'out', DIRECTION_IN = 'in';

    public const MODE_SIMPLE = 'simple', MODE_SMART = 'smart';

    public const URGENCY_NORMAL = 'normal', URGENCY_URGENT = 'urgent', URGENCY_IMMEDIATE = 'immediate';

    public const SIGN_MODE_ELECTRONIC = 'electronic', SIGN_MODE_WET = 'wet';

    public const ROUTE_SEQUENTIAL = 'sequential', ROUTE_PARALLEL = 'parallel';

    public const CATEGORY_NORMAL = 'normal', CATEGORY_AUTHORITY = 'authority';

    public const BLOCK_KEYS = ['type', 'heading', 'html', 'text', 'path', 'size', 'align', 'caption', 'id'];

    public const STRUCTURAL_BLOCK_TYPES = ['heading', 'text', 'html', 'table', 'image', 'signature_row', 'attachments_note'];

    public const HTML_BLOCK_BYTES = ContentSanitizerService::MAX_BYTES;

    public const MAX_BODY_DEPTH = 100, MAX_BODY_TAGS = 6000;

    public const VOID_ELEMENTS = ['br', 'hr', 'img', 'input', 'meta', 'link', 'col', 'source', 'track', 'wbr', 'area', 'base', 'embed', 'param'];

    public const RAW_TEXT_ELEMENTS = ['script', 'style', 'title', 'head', 'textarea', 'xmp', 'noembed', 'noframes', 'plaintext', 'listing', 'svg', 'math'];

    public const IMPLIED_CLOSE = [
        'p' => ['p'],
        'li' => ['p', 'li'],
        'dt' => ['p', 'dt', 'dd'],
        'dd' => ['p', 'dt', 'dd'],
        'td' => ['p', 'td', 'th'],
        'th' => ['p', 'td', 'th'],
        'option' => ['option'],
        'div' => ['p'],
        'ul' => ['p'],
        'ol' => ['p'],
        'table' => ['p'],
        'h2' => ['p'],
        'h3' => ['p'],
        'h4' => ['p'],
    ];

    public const SPEC_KEYS = ['blocks', 'attachments', 'evidence_path', 'recipient', 'sender'];

    public const BLOCK_SIZES = ['small', 'medium', 'full'];

    public const BLOCK_ALIGNS = ['left', 'center', 'right'];

    public const CANONICAL_MAX_BYTES = 300000;

    private static ?HtmlSanitizer $bodySanitizer = null;

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'classification' => self::CLASSIFICATION_NORMAL,
        'direction' => self::DIRECTION_OUT,
        'mode' => self::MODE_SIMPLE,
        'urgency' => self::URGENCY_NORMAL,
        'is_template' => false,
        'sign_mode' => self::SIGN_MODE_ELECTRONIC,
        'route_mode' => self::ROUTE_SEQUENTIAL,
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_GENERATING => 'info',
        self::STATUS_FAILED => 'danger',
        self::STATUS_SAVED => 'info',
        self::STATUS_SHARED => 'warning',
        self::STATUS_APPROVED => 'success',
        self::STATUS_REJECTED => 'danger',
        self::STATUS_WITHDRAWN => 'gray',
        self::STATUS_VOID => 'gray',
        self::STATUS_SUPERSEDED => 'gray',
        self::STATUS_RECEIVED => 'info',
        self::STATUS_ROUTED => 'warning',
        self::STATUS_DONE => 'success',
    ];

    public const STATUS_ICONS = [
        self::STATUS_DRAFT => 'heroicon-o-pencil-square',
        self::STATUS_GENERATING => 'heroicon-o-arrow-path',
        self::STATUS_FAILED => 'heroicon-o-x-circle',
        self::STATUS_SAVED => 'heroicon-o-bookmark',
        self::STATUS_SHARED => 'heroicon-o-paper-airplane',
        self::STATUS_APPROVED => 'heroicon-o-check-circle',
        self::STATUS_REJECTED => 'heroicon-o-arrow-uturn-left',
        self::STATUS_WITHDRAWN => 'heroicon-o-arrow-left-circle',
        self::STATUS_VOID => 'heroicon-o-no-symbol',
        self::STATUS_SUPERSEDED => 'heroicon-o-arrows-right-left',
        self::STATUS_RECEIVED => 'heroicon-o-inbox-arrow-down',
        self::STATUS_ROUTED => 'heroicon-o-arrow-right-circle',
        self::STATUS_DONE => 'heroicon-o-check-badge',
    ];

    public const LIST_COLUMNS = [
        'id', 'user_id', 'direction', 'type', 'mode', 'status', 'classification', 'urgency', 'sign_mode', 'sender_category', 'step_no', 'steps_total', 'is_template', 'letterhead_id', 'in_reply_to_id', 'supersedes_id',
        'related_type', 'related_id', 'subject', 'number', 'number_seq', 'letter_date', 'recipients', 'awaiting_user_id', 'deadline_at',
        'shared_at', 'approved_at', 'content_hash', 'tags', 'pdf_path', 'pdf_hash', 'fail_reason', 'model', 'tokens_total',
        'their_number', 'their_date', 'received_at', 'created_at', 'updated_at', 'deleted_at',
    ];

    public const ROW_COLUMNS = [
        'id', 'user_id', 'direction', 'type', 'status', 'classification', 'urgency', 'sign_mode', 'step_no', 'steps_total', 'is_template',
        'subject', 'number', 'letter_date', 'awaiting_user_id', 'deadline_at', 'shared_at', 'tags', 'created_at', 'updated_at',
    ];

    public const ADMIN_COLUMNS = [
        'id', 'user_id', 'direction', 'type', 'status', 'classification', 'urgency', 'sign_mode', 'route_mode', 'sender_category', 'is_template',
        'subject', 'number', 'awaiting_user_id', 'shared_at', 'approved_at', 'created_at',
    ];

    protected $fillable = [
        'user_id',
        'direction',
        'type',
        'mode',
        'classification',
        'urgency',
        'sign_mode',
        'route_mode',
        'letterhead_id',
        'in_reply_to_id',
        'supersedes_id',
        'related_type',
        'related_id',
        'subject',
        'spec',
        'deadline_at',
        'tags',
        'their_number',
        'their_date',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'spec' => 'array',
            'versions' => 'array',
            'recipients' => 'array',
            'parties' => 'array',
            'events' => 'array',
            'tags' => 'array',
            'meta' => 'array',
            'is_template' => 'boolean',
            'deadline_at' => 'datetime',
            'shared_at' => 'datetime',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'number_seq' => 'integer',
            'tokens_total' => 'integer',
        ];
    }

    public function letterhead(): BelongsTo
    {
        return $this->belongsTo(Letterhead::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'in_reply_to_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query;
    }

    public function scopeSharedWith(Builder $query, int $userId): Builder
    {
        return $query->whereNotIn('status', [...self::DRAFT_LIKE, self::STATUS_SAVED, self::STATUS_WITHDRAWN])->where(
            fn (Builder $q) => $q->whereJsonContains('recipients', $userId)->orWhereJsonContains('parties', ['user_id' => $userId])
        );
    }

    public function scopeUnseenBy(Builder $query, int $userId): Builder
    {
        throw new LogicException('seen-by lives on party entries');
    }

    public function markSeenBy(int $userId): void
    {
        throw new LogicException('seen-by lives on party entries');
    }

    protected static function quotaFeature(): string
    {
        return 'correspondence';
    }

    public static function sanitizeHtml(?string $html): string
    {
        self::$bodySanitizer ??= new HtmlSanitizer(self::bodySanitizerConfig());

        $html = (string) $html;

        if (strlen($html) > self::HTML_BLOCK_BYTES) {
            throw self::bodyError('body_too_large');
        }

        $html = self::stripRawTextElements(self::canonicaliseHtml($html));

        self::rejectDeepNesting($html);

        return self::$bodySanitizer->sanitize($html);
    }

    private static function bodyError(string $key): ValidationException
    {
        return ValidationException::withMessages(['spec' => __("resources/correspondence/strings.errors.{$key}")]);
    }

    private static function canonicaliseHtml(string $html): string
    {
        $html = preg_replace('#<!--.*?(?:-->|$)#s', '', $html) ?? throw self::bodyError('body_too_nested');
        $html = preg_replace('#<[!?][^>]*+>?#', '', $html) ?? throw self::bodyError('body_too_nested');
        $html = preg_replace('#<(?!/?+[a-z][a-z0-9:_-]*+(?:\s++[^\s=<>"\'/]++(?:\s*+=\s*+(?:"[^"<>]*+"|\'[^\'<>]*+\'|[^\s"\'<>=`]++))?+)*+\s*+/?+>)#i', '&lt;', $html) ?? throw self::bodyError('body_too_nested');

        $html = preg_replace('/&(?!(?:#[0-9]{1,7}|#x[0-9a-f]{1,6}|[a-z][a-z0-9]{1,31});)/i', '&amp;', $html) ?? throw self::bodyError('body_too_nested');

        return strlen($html) > self::CANONICAL_MAX_BYTES ? throw self::bodyError('body_too_large') : $html;
    }

    private static function stripRawTextElements(string $html): string
    {
        $names = implode('|', self::RAW_TEXT_ELEMENTS);

        if (preg_match_all("#<(?:{$names})\\b#i", $html) > 20) {
            throw self::bodyError('body_too_nested');
        }

        do {
            $before = $html;

            foreach (self::RAW_TEXT_ELEMENTS as $tag) {
                $offset = 0;

                while (($open = stripos($html, "<{$tag}", $offset)) !== false) {
                    $next = $html[$open + strlen($tag) + 1] ?? '';

                    if (ctype_alnum($next) || in_array($next, ['_', '-', ':', '.'], true)) {
                        $offset = $open + 1;

                        continue;
                    }

                    $close = stripos($html, "</{$tag}", $open);
                    $end = $close === false ? false : strpos($html, '>', $close);
                    $html = substr($html, 0, $open) . ($end === false ? '' : substr($html, $end + 1));
                    $offset = max(0, $open - strlen($tag));
                }
            }
        } while ($html !== $before);

        return $html;
    }

    private static function rejectDeepNesting(string $html): void
    {
        preg_match_all('#<(/?)([a-z][a-z0-9:_-]*)#i', $html, $tags, PREG_SET_ORDER);

        $stack = [];
        $opens = 0;

        foreach ($tags as [, $slash, $name]) {
            $name = strtolower($name);

            if ($slash === '/') {
                if ($stack && end($stack) === $name && $name !== 'html') {
                    array_pop($stack);
                }

                continue;
            }

            if (in_array($name, self::VOID_ELEMENTS, true)) {
                continue;
            }

            while ($stack && in_array(end($stack), self::IMPLIED_CLOSE[$name] ?? [], true)) {
                array_pop($stack);
            }

            $stack[] = $name;

            if (++$opens > self::MAX_BODY_TAGS || count($stack) > self::MAX_BODY_DEPTH) {
                throw self::bodyError('body_too_nested');
            }
        }
    }

    private static function bodySanitizerConfig(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowElement('a', ['href'])
            ->forceAttribute('a', 'rel', 'noopener')
            ->forceAttribute('a', 'target', '_blank')
            ->withMaxInputLength(self::CANONICAL_MAX_BYTES)
            ->withAttributeSanitizer(new class implements AttributeSanitizerInterface {
                private const PATTERNS = [
                    'color' => '/^(#[0-9a-f]{3}|#[0-9a-f]{6}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d{1,3})\s*)?\)|[a-z]{3,20})$/i',
                    'font-size' => '/^\d{1,3}(\.\d{1,2})?(px|pt|em|rem|%)$/',
                    'text-align' => '/^(left|right|center|justify)$/',
                    'font-family' => '/^[\p{L}\p{N} ,\-]{1,100}$/u',
                    'background-color' => '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i',
                    'margin-right' => '/^(24|48|72|96|120)pt$/',
                    'margin-left' => '/^(24|48|72|96|120)pt$/',
                    'page-break-after' => '/^always$/',
                ];

                private const SIZE_CEILINGS = ['px' => 200, 'pt' => 200, 'em' => 20, 'rem' => 20, '%' => 400];

                private const ONLY_ON = [
                    'background-color' => ['th', 'td', 'p', 'span', 'mark'],
                    'margin-right' => ['p', 'h2', 'h3', 'h4'],
                    'margin-left' => ['p', 'h2', 'h3', 'h4'],
                    'page-break-after' => ['hr'],
                ];

                private const MAX_HREF = 2048;

                public function getSupportedElements(): ?array
                {
                    return null;
                }

                public function getSupportedAttributes(): ?array
                {
                    return ['style', 'dir', 'colspan', 'rowspan', 'href'];
                }

                public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
                {
                    if ($attribute === 'href') {
                        return strlen($value) <= self::MAX_HREF ? $value : null;
                    }

                    if ($attribute === 'dir') {
                        $dir = strtolower(trim($value));

                        return in_array($dir, ['rtl', 'ltr', 'auto'], true) ? $dir : null;
                    }

                    if ($attribute !== 'style') {
                        return preg_match('/^[1-9]\d?$/', trim($value)) ? trim($value) : null;
                    }

                    $kept = [];
                    foreach (explode(';', $value) as $declaration) {
                        [$property, $setting] = array_pad(explode(':', $declaration, 2), 2, '');
                        $property = strtolower(trim($property));
                        $setting = trim(str_replace(['"', "'"], '', $setting));
                        if (isset(self::PATTERNS[$property])
                            && preg_match(self::PATTERNS[$property], $setting)
                            && ($property !== 'font-size' || (float) $setting <= self::SIZE_CEILINGS[ltrim($setting, '0123456789.')])
                            && (!isset(self::ONLY_ON[$property]) || in_array($element, self::ONLY_ON[$property], true))) {
                            $kept[] = "{$property}: {$setting}";
                        }
                    }

                    return $kept ? implode('; ', $kept) : null;
                }
            });

        foreach (['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'hr', 'sup', 'sub', 'mark', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'span'] as $element) {
            $config = $config->allowElement($element);
        }

        $config = $config
            ->allowAttribute('dir', '*')
            ->allowAttribute('style', '*')
            ->allowAttribute('colspan', ['td', 'th'])
            ->allowAttribute('rowspan', ['td', 'th']);

        foreach (['script', 'iframe', 'object', 'embed', 'img', 'svg', 'math', 'template', 'noscript', 'video', 'audio', 'canvas', 'form'] as $element) {
            $config = $config->dropElement($element);
        }

        return $config;
    }
}
