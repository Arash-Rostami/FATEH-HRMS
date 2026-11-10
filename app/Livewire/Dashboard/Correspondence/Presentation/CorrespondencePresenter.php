<?php

namespace App\Livewire\Dashboard\Correspondence\Presentation;

use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use App\Services\Correspondence\CorrespondenceFonts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CorrespondencePresenter
{
    public const REGISTRY_STATUSES = [
        Correspondence::STATUS_SHARED,
        Correspondence::STATUS_APPROVED,
        Correspondence::STATUS_REJECTED,
        Correspondence::STATUS_VOID,
        Correspondence::STATUS_SUPERSEDED,
        Correspondence::STATUS_RECEIVED,
        Correspondence::STATUS_ROUTED,
        Correspondence::STATUS_DONE,
    ];

    private const SENDING_SECONDS = 150;

    private const LINES_PER_PAGE = 40;

    private const DIFF_WORDS = 400;

    private const FIELDS_BOX = ['side' => 'left', 'top_mm' => 10, 'offset_mm' => 10, 'font_size' => 11];

    private const PAPER_MM = [Letterhead::SIZE_A4 => [210, 297], Letterhead::SIZE_A5 => [148, 210]];

    private const IMAGE_WIDTHS = ['small' => '30%', 'medium' => '55%', 'full' => '100%'];

    private const REFINABLE = ['text', 'html'];

    private const TIMELINE_LATEST = 30;

    private const RELATED_ROUTES = ['project' => 'projects', 'ticket' => 'ths', 'contact' => 'contact'];

    private ?Collection $names = null;

    private ?int $snapId = null;

    private ?array $snapMemo = null;

    private ?Correspondence $replyParent = null;

    public function composerState(?Correspondence $letter, bool $canEdit, bool $structural, array $quota, bool $generatingElsewhere, bool $smartOn, string $classification = Correspondence::CLASSIFICATION_NORMAL, bool $registry = false): array
    {
        $exhausted = $quota['used'] >= $quota['cap'];
        $busy = $letter !== null && $letter->status === Correspondence::STATUS_GENERATING;
        $quotaPct = min(100, max(0, (int) round($quota['used'] * 100 / max(1, $quota['cap']))));
        $hidden = tenantOff('correspondence:ai');
        $blocked = $classification !== Correspondence::CLASSIFICATION_NORMAL;

        return [
            'exhausted' => $exhausted,
            'busy' => $busy,
            'canSubmit' => $smartOn && !$hidden && !$blocked && !($exhausted || $busy || $generatingElsewhere),
            'canSave' => $canEdit && !$busy,
            'structural' => $structural,
            'quotaPct' => $quotaPct,
            'quotaLeft' => max(0, $quota['cap'] - $quota['used']),
            'quotaTone' => $this->quotaTone($exhausted, $quotaPct),
            'smartOn' => $smartOn && !$hidden && !$blocked,
            'smartBlocked' => $blocked && !$hidden,
            'types' => $this->typeOptions($letter, $registry),
            'rules' => $this->rules($quota),
        ];
    }

    public function confidentialLabel(): string
    {
        return __('resources/correspondence/strings.confidential_subject');
    }

    public function listRow(Correspondence $letter, int $viewerId, bool $hideSubject): array
    {
        $overdue = $letter->status === Correspondence::STATUS_SHARED
            && $letter->deadline_at !== null
            && now()->gt($letter->deadline_at);
        $from = $letter->shared_at ?? $letter->created_at;
        $step = $letter->steps_total && $letter->step_no
            ? convertToPersian((int) $letter->step_no) . ' از ' . convertToPersian((int) $letter->steps_total)
            : '';

        return [
            'id' => $letter->id,
            'subject' => $hideSubject ? $this->confidentialLabel() : ($letter->subject ?: 'بدون موضوع'),
            'number' => $letter->number ?: '',
            'status' => (string) $letter->status,
            'status_label' => __('resources/correspondence/strings.status.' . $letter->status),
            'dot' => self::DOTS[$overdue ? 'overdue' : $letter->status] ?? self::DOTS['draft'],
            'overdue' => $overdue,
            'step' => $step,
            'awaiting' => $letter->awaiting_user_id ? $this->name($letter->awaiting_user_id) : '',
            'waiting' => $from ? convertToPersian((int) abs($from->diffInDays(now()))) : '۰',
            'is_template' => (bool) $letter->is_template,
            'template_name' => $hideSubject ? '' : ($letter->meta['template_name'] ?? ''),
            'is_mine' => (int) $letter->user_id === $viewerId,
            'tags' => $hideSubject ? [] : ($letter->tags ?? []),
            'stamp' => $letter->letter_date ?: ($letter->updated_at ? toJalali($letter->updated_at, 'Y/m/d') : ''),
            'direction' => (string) $letter->direction,
            'type' => __('resources/correspondence/strings.types.' . $letter->type),
            'urgency' => __('resources/correspondence/strings.urgency.' . $letter->urgency),
            'sign_mode' => (string) $letter->sign_mode,
            'urgent' => filled($letter->urgency) && $letter->urgency !== 'normal',
            'wet' => $letter->sign_mode === Correspondence::SIGN_MODE_WET,
            'deadline' => $this->deadlineDate($letter),
        ];
    }

    public static function waitingForMe(Correspondence $letter, int $userId): bool
    {
        if ($letter->status !== Correspondence::STATUS_SHARED) {
            return false;
        }
        if ((int) $letter->awaiting_user_id === $userId) {
            return true;
        }
        if (($letter->route_mode ?? Correspondence::ROUTE_SEQUENTIAL) !== Correspondence::ROUTE_PARALLEL) {
            return false;
        }

        foreach ($letter->parties ?? [] as $party) {
            if (is_array($party) && (int) ($party['user_id'] ?? 0) === $userId && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER) {
                return !filled($party['signed_at'] ?? null);
            }
        }

        return false;
    }

    public function templateChip(Correspondence $letter): array
    {
        return [
            'id' => $letter->id,
            'name' => $letter->meta['template_name'] ?? $letter->subject ?: 'بدون نام',
            'type' => __('resources/correspondence/strings.types.' . $letter->type),
        ];
    }

    public function templateRow(Correspondence $letter): array
    {
        $snippet = $letter->classification === Correspondence::CLASSIFICATION_NORMAL
            ? mb_substr(trim((string) $letter->search_text), 0, 160)
            : '';

        return [
            'id' => $letter->id,
            'name' => $letter->meta['template_name'] ?? $letter->subject ?: 'بدون نام',
            'type' => __('resources/correspondence/strings.types.' . $letter->type),
            'used' => toJalali($letter->updated_at, 'Y/m/d'),
            'snippet' => $snippet,
            'scope' => $letter->meta['template_scope'] ?? 'private',
            'scope_label' => __('resources/correspondence/strings.template_scope.' . ($letter->meta['template_scope'] ?? 'private')),
        ];
    }

    public function timeline(array $events): array
    {
        $rows = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $action = (string) ($event['action'] ?? '');
            $key = 'resources/correspondence/strings.events.' . $action;
            $rows[] = [
                'action' => $action,
                'sentence' => Lang::has($key)
                    ? __($key, [
                        'name' => $this->name($event['user_id'] ?? null),
                        'time' => isset($event['at']) ? toJalali($event['at'], 'Y/m/d H:i') : '—',
                        'target' => isset($event['target_id']) ? $this->name($event['target_id']) : (string) ($event['target'] ?? ''),
                        'reason' => (string) ($event['reason'] ?? ''),
                        'text' => (string) ($event['text'] ?? ''),
                    ])
                    : $action,
            ];
        }

        return $rows;
    }

    public function deliveryRows(array $parties, bool $linkable = true): array
    {
        $days = LetterForm::limits()['not_opened_days'];
        $mailOn = correspondenceMailOn();
        $rows = [];
        foreach ($parties as $index => $party) {
            if (!is_array($party) || filled($party['user_id'] ?? null)) {
                continue;
            }
            $sentAt = filled($party['sent_at'] ?? null) ? Carbon::parse($party['sent_at']) : null;
            $openedAt = filled($party['opened_at'] ?? null) ? Carbon::parse($party['opened_at']) : null;
            $stale = $sentAt && !$openedAt && $sentAt->lte(now()->subDays($days));
            $code = (string) ($party['delivery_error'] ?? '');
            $sending = $code === 'sending' && filled($party['sending_at'] ?? null) && Carbon::parse($party['sending_at'])->gt(now()->subSeconds(self::SENDING_SECONDS));
            $state = $sending ? 'sending' : ($openedAt ? 'opened' : ($stale ? 'not_opened' : ($sentAt ? 'sent' : 'not_sent')));
            $failed = $code !== '' && $code !== 'sending';
            $ready = $linkable && $mailOn && LetterRules::modeValid($party) && LetterRules::emailValid($party);
            $lapsed = $sentAt !== null && !$sending && blank($party['manual_note'] ?? null) && LetterRules::lapsed($party);
            $rows[] = [
                'index' => (int) $index,
                'name' => ($party['name'] ?? '') ?: (($party['org'] ?? '') ?: ($party['email'] ?? '')),
                'delivery' => __('resources/correspondence/strings.delivery_modes.' . ($party['delivery'] ?? 'link')),
                'state' => $state,
                'label' => $sending ? __('resources/correspondence/strings.delivery_errors.sending') : __('resources/correspondence/strings.delivery.' . $state),
                'sent_at' => $sentAt ? toJalali($sentAt, 'Y/m/d H:i') : '',
                'opened_at' => $openedAt ? toJalali($openedAt, 'Y/m/d H:i') : '',
                'expires' => $sentAt && LetterRules::linked($party) && filled($party['expires_at'] ?? $party['expires_at_next'] ?? null) ? toJalali(Carbon::parse($party['expires_at'] ?? $party['expires_at_next']), 'Y/m/d') : '',
                'error' => $failed ? $code : '',
                'error_label' => $failed ? __('resources/correspondence/strings.delivery_errors.' . (Lang::has('resources/correspondence/strings.delivery_errors.' . $code) ? $code : 'send_failed')) : '',
                'can_send' => $ready && !$sentAt && !$sending,
                'can_remind' => $ready && ($state === 'not_opened' || $lapsed),
                'can_revoke' => $mailOn && $sentAt !== null && LetterRules::linked($party),
            ];
        }

        return $rows;
    }

    public function manualDelivery(string $status, array $parties): bool
    {
        if ($status !== Correspondence::STATUS_APPROVED) {
            return false;
        }

        $external = false;
        foreach ($parties as $party) {
            if (!is_array($party) || filled($party['user_id'] ?? null)) {
                continue;
            }
            if (filled($party['sent_at'] ?? null)) {
                return false;
            }
            $external = true;
        }

        return $external;
    }

    public function wordDiff(array $leftBlocks, array $rightBlocks): array
    {
        $leftWords = $this->words($leftBlocks);
        $rightWords = $this->words($rightBlocks);
        $truncated = count($leftWords) > self::DIFF_WORDS || count($rightWords) > self::DIFF_WORDS;
        $left = array_slice($leftWords, 0, self::DIFF_WORDS);
        $right = array_slice($rightWords, 0, self::DIFF_WORDS);
        $n = count($left);
        $m = count($right);
        $dp = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $dp[$i][$j] = $left[$i] === $right[$j] ? $dp[$i + 1][$j + 1] + 1 : max($dp[$i + 1][$j], $dp[$i][$j + 1]);
            }
        }

        $segments = [];
        $push = function (string $kind, string $word) use (&$segments): void {
            $last = array_key_last($segments);
            if ($last !== null && $segments[$last]['kind'] === $kind) {
                $segments[$last]['text'] .= ' ' . $word;

                return;
            }
            $segments[] = ['kind' => $kind, 'text' => $word];
        };
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($left[$i] === $right[$j]) {
                $push('same', $left[$i]);
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $push('del', $left[$i]);
                $i++;
            } else {
                $push('add', $right[$j]);
                $j++;
            }
        }
        while ($i < $n) {
            $push('del', $left[$i++]);
        }
        while ($j < $m) {
            $push('add', $right[$j++]);
        }

        return ['segments' => $segments, 'truncated' => $truncated];
    }

    public function pageEstimate(array $blocks, ?string $size, array $attachments = []): array
    {
        $limits = LetterForm::limits();
        $perLine = max(1, intdiv((int) ($limits['page_chars']['a4'] ?? 2400), self::LINES_PER_PAGE));
        $chars = 0;
        $lines = 0;
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? null) === 'image') {
                $lines++;
                continue;
            }
            $heading = mb_substr(stripHtml((string) ($block['heading'] ?? '')), 0, 200);
            $html = substr((string) ($block['html'] ?? $block['text'] ?? ''), 0, Correspondence::HTML_BLOCK_BYTES);
            $chars += mb_strlen($heading) + mb_strlen(stripHtml($html));
            $lines += ($heading === '' ? 0 : (int) ceil(mb_strlen($heading) / $perLine)) + $this->bodyLines($html, $perLine);
        }
        $per = (int) ($limits['page_chars'][$size ?: 'a4'] ?? 2400);
        $pages = max(1, (int) ceil($chars / max(1, $per)));
        $suggest = $lines > 0 && $lines <= $limits['suggest_lines'] ? 'a5' : 'a4';

        return [
            'pages' => $pages,
            'label' => '≈ ' . convertToPersian($pages) . ' صفحه',
            'warn' => $pages > $limits['warn_pages'],
            'suggest' => $suggest,
            'suggest_label' => __('resources/correspondence/strings.letterhead.size.' . $suggest),
            'attachment' => $this->attachmentLabel($attachments, $blocks),
        ];
    }

    public function attachmentLabel(array $attachments, array $blocks = []): string
    {
        foreach ($attachments as $file) {
            if (is_array($file) && filled($file['path'] ?? null)) {
                return 'دارد';
            }
        }

        return $this->hasAttachment($blocks) ? 'دارد' : 'ندارد';
    }

    public function routeSummary(array $parties, string $classification, string $signMode = Correspondence::SIGN_MODE_ELECTRONIC, string $routeMode = Correspondence::ROUTE_SEQUENTIAL): array
    {
        $approvers = [];
        $fyi = [];
        $cc = [];
        $external = [];
        foreach ($parties as $party) {
            if (!is_array($party)) {
                continue;
            }
            $print = filter_var($party['print_in_cc'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!filled($party['user_id'] ?? null)) {
                $label = trim(($party['name'] ?? '') . ' ' . ($party['org'] ?? '')) ?: (string) ($party['email'] ?? '');
                $external[] = $label . ' — ' . __('resources/correspondence/strings.delivery_modes.' . ($party['delivery'] ?? 'link'));
                $print && $cc[] = $label;
                continue;
            }
            $name = $this->name($party['user_id']);
            if (($party['role'] ?? null) === LetterForm::ROLE_APPROVER) {
                $approvers[] = $name;
                continue;
            }
            $fyi[] = $name;
            $print && $cc[] = $name;
        }

        return [
            'approvers' => $approvers,
            'signer' => $approvers === [] ? '' : end($approvers),
            'fyi' => $fyi,
            'cc' => $cc,
            'external' => $external,
            'classification' => __('resources/correspondence/strings.classification.' . $classification),
            'sign_mode' => __('resources/correspondence/strings.sign_mode.' . $signMode),
            'route_mode' => __('resources/correspondence/strings.route_mode.' . $routeMode),
        ];
    }

    public function hasUnfilled(Correspondence $letter): bool
    {
        $needles = [...LetterForm::MERGE_TOKENS, LetterForm::limits()['placeholder']];
        $hay = [(string) $letter->subject];
        foreach ($letter->spec['blocks'] ?? [] as $block) {
            if (!is_array($block)) {
                continue;
            }
            foreach (['html', 'heading', 'caption', 'text'] as $key) {
                if (is_string($block[$key] ?? null)) {
                    $hay[] = $block[$key];
                }
            }
        }
        $blob = implode("\n", $hay);
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($blob, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function typeFrame(string $type): array
    {
        $config = (array) config("ai.correspondence.types.{$type}");

        return [
            'opening' => (string) ($config['opening'] ?? ''),
            'closing' => (string) ($config['closing'] ?? ''),
        ];
    }

    public function paperData(Correspondence $letter, string $mode = 'screen'): array
    {
        $snap = $this->letterSnapshot($letter);
        $head = $snap === null && $letter->letterhead_id ? $letter->letterhead : null;
        $config = $this->overlay(Letterhead::defaultConfig(), $snap['config'] ?? $head?->config);
        $rawSize = $snap !== null ? ($snap['size'] ?? null) : $head?->size;
        $size = $rawSize === Letterhead::SIZE_A5 ? Letterhead::SIZE_A5 : Letterhead::SIZE_A4;
        $margins = $this->overlay(Letterhead::defaultMargins($size), $snap['margins'] ?? $head?->margins);
        $style = $this->overlay(Letterhead::defaultStyle(), $snap['style'] ?? $head?->style);
        $style['font'] = CorrespondenceFonts::pick($style['font'], 'fa');
        $style['latin_font'] = CorrespondenceFonts::pick($style['latin_font'], 'latin');
        $box = $this->overlay(self::FIELDS_BOX, $snap['fields_box'] ?? $head?->fields_box);
        [$width, $height] = self::PAPER_MM[$size];
        $spec = is_array($letter->spec) ? $letter->spec : [];
        $recipient = is_array($spec['recipient'] ?? null) ? $spec['recipient'] : [];
        $attachments = is_array($spec['attachments'] ?? null) ? $spec['attachments'] : [];
        $blocks = is_array($spec['blocks'] ?? null) ? $spec['blocks'] : [];
        $parties = is_array($letter->parties) ? $letter->parties : [];
        $allowed = $mode !== 'screen' || $this->mayView($letter);
        $signer = $this->signerOf($parties);
        $wet = $letter->sign_mode === Correspondence::SIGN_MODE_WET;
        $pdf = $mode === 'pdf';
        $letterId = (int) $letter->id;
        $pageNumbers = (bool) ($snap['page_numbers'] ?? $head?->page_numbers);
        $footMm = (float) ($config['footer_height_mm'] ?? 0);

        if ($snap !== null) {
            $images = is_array($snap['images'] ?? null) ? $snap['images'] : [];
            $headSrc = $allowed ? $this->privateSrc($images['header'] ?? null, $mode, $letterId) : '';
            $footSrc = $allowed ? $this->privateSrc($images['footer'] ?? null, $mode, $letterId) : '';
            $stampSrc = $allowed ? $this->privateSrc($images['stamp'] ?? null, $mode, $letterId) : '';
            $watermark = (string) ($snap['watermark'] ?? '');
        } else {
            $borrowed = $this->borrowedHeads($head, $config);
            $headSrc = $this->publicSrc($borrowed->get((int) ($config['header_from'] ?? 0))?->header_image ?? $head?->header_image, $pdf);
            $footSrc = $this->publicSrc($borrowed->get((int) ($config['footer_from'] ?? 0))?->footer_image ?? $head?->footer_image, $pdf);
            $stampSrc = $this->publicSrc($head?->stamp_image, $pdf);
            $watermark = (string) ($head?->watermark ?? '');
        }

        $copies = array_values(array_filter($parties, fn ($party) => is_array($party) && filter_var($party['print_in_cc'] ?? false, FILTER_VALIDATE_BOOLEAN)));
        usort($copies, fn (array $a, array $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));
        $frozen = is_array($snap['frame'] ?? null) ? $snap['frame'] : null;
        $frame = $frozen === null ? $this->typeFrame((string) $letter->type) : [
            'opening' => (string) ($frozen['opening'] ?? ''),
            'closing' => (string) ($frozen['closing'] ?? ''),
        ];
        $counterparty = $frozen === null
            ? config("ai.correspondence.types.{$letter->type}.counterparty_signature") === true
            : ($frozen['counterparty'] ?? false) === true;

        return [
            'mode' => $mode,
            'browser' => !$pdf,
            'size' => $size,
            'page' => strtoupper($size),
            'width' => $width,
            'height' => $height,
            'flow_mm' => max(50, $height - ($margins['top'] + $margins['bottom']) * 10 - 1),
            'margins' => $margins,
            'style' => $style,
            'fonts' => CorrespondenceFonts::used($style, $blocks),
            'config' => $config,
            'box' => $box,
            'head_src' => $headSrc,
            'foot_src' => $footSrc,
            'stamp_src' => $stampSrc,
            'watermark' => $this->latinText($watermark),
            'page_numbers' => $pageNumbers,
            'foot_gap' => $pageNumbers ? max(0.0, round((float) $margins['bottom'] - $footMm / 10, 2)) : 0.0,
            'draft' => in_array($letter->status, Correspondence::DRAFT_LIKE, true),
            'frame' => [
                'opening' => $this->latinText($frame['opening']),
                'closing' => $this->latinText($frame['closing']),
            ],
            'counterparty' => $counterparty,
            'recipient' => array_values(array_filter(array_map(
                fn (string $key) => $this->latinText(trim((string) ($recipient[$key] ?? ''))),
                ['organization', 'person', 'title']
            ), fn (string $line) => $line !== '')),
            'subject' => $this->latinText((string) $letter->subject),
            'blocks' => $this->latinBlocks($this->paperBlocks($letter, $blocks, $mode, $allowed, ($config['table']['repeat_head'] ?? true) === true), $pdf),
            'signer' => $signer === null ? null : [
                'name' => $this->latinText($this->partyName($signer)),
                'title' => $this->latinText((string) ($signer['title'] ?? '')),
                'src' => $wet || !$allowed ? '' : $this->privateSrc($signer['signature_path'] ?? null, $mode, $letterId),
            ],
            'cc' => array_map(fn (array $party) => $this->latinText(trim($this->partyName($party) . ' ' . ($party['org'] ?? ''))), $copies),
            'attachment' => $this->attachmentLabel($attachments, $blocks),
            'files' => array_values(array_filter(array_map(
                fn ($file) => is_array($file) ? $this->latinText(trim((string) ($file['name'] ?? ''))) : '',
                $attachments
            ), fn (string $name) => $name !== '')),
            'number' => $this->latinText(convertToPersian($letter->number) ?? ''),
            'date' => convertToPersian($letter->letter_date) ?? '',
        ];
    }

    public function viewerState(Correspondence $letter, int $userId): array
    {
        $user = auth()->user();
        $registry = $user instanceof User && CorrespondenceAccessPolicy::isRegistry($user);
        $status = (string) $letter->status;
        $mine = (int) $letter->user_id === $userId;
        $parties = is_array($letter->parties) ? $letter->parties : [];
        $summary = $this->routeSummary($parties, (string) $letter->classification, (string) $letter->sign_mode, (string) $letter->route_mode);
        $parallel = $letter->route_mode === Correspondence::ROUTE_PARALLEL;
        $waiting = self::waitingForMe($letter, $userId);
        $awaiting = (int) $letter->awaiting_user_id;
        $other = $status === Correspondence::STATUS_SHARED && !$waiting && !$parallel && $awaiting > 0;
        $approvers = array_values(array_filter($parties, fn ($party) => is_array($party) && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER && filled($party['user_id'] ?? null)));
        $last = $approvers === [] ? 0 : (int) end($approvers)['user_id'];
        $signed = [];
        $pending = [];
        foreach ($approvers as $party) {
            if (filled($party['signed_at'] ?? null)) {
                $signed[] = $this->name($party['user_id']);
                continue;
            }
            $pending[] = $this->name($party['user_id']);
        }
        $unsigned = count($pending);
        $targets = [];
        foreach ($parallel && $status === Correspondence::STATUS_SHARED ? $approvers : [] as $party) {
            if (blank($party['signed_at'] ?? null) && (int) $party['user_id'] !== $userId) {
                $targets[(int) $party['user_id']] = $this->name($party['user_id']);
            }
        }
        $approve = $waiting || $other || $targets !== [];
        $unopened = $mine ? count(array_filter($parties, fn ($party) => is_array($party)
            && filled($party['user_id'] ?? null)
            && (int) $party['user_id'] !== $userId
            && blank($party['opened_at'] ?? null)
            && blank($party['signed_at'] ?? null))) : 0;
        $events = is_array($letter->events) ? $letter->events : [];
        $acts = array_column($events, 'action');
        $incoming = $letter->direction === Correspondence::DIRECTION_IN;
        $steward = $user instanceof User && CorrespondenceAccessPolicy::canSteward($user, $letter);
        $linkable = $letter->classification === Correspondence::CLASSIFICATION_NORMAL
            && ($letter->sign_mode !== Correspondence::SIGN_MODE_WET || in_array('wet_signed', $acts, true));
        $replace = $mine && $status === Correspondence::STATUS_APPROVED && $letter->direction === Correspondence::DIRECTION_OUT
            && filled($letter->supersedes_id)
            && Correspondence::query()->whereKey($letter->supersedes_id)->where('user_id', $userId)->where('status', Correspondence::STATUS_APPROVED)->exists();
        $versions = array_values(array_filter(is_array($letter->versions) ? $letter->versions : [], fn ($row) => is_array($row)));
        $delivery = $this->deliveryRows($parties, $linkable);
        $close = $mine && $status === Correspondence::STATUS_APPROVED;

        return [
            'registry' => $registry,
            'mine' => $mine,
            'waiting' => $waiting,
            'approve' => $approve,
            'instead' => $other,
            'instead_name' => $other ? $this->name($awaiting) : '',
            'targets' => $targets,
            'approve_label' => $other ? 'تأیید به‌جای ' . $this->name($awaiting) : ($waiting ? 'تأیید' : 'تأیید به‌جای دیگران'),
            'final' => $approve && ($parallel ? $unsigned === 1 : $awaiting === $last),
            'signer' => $summary['signer'],
            'manage' => $mine && $status === Correspondence::STATUS_SHARED,
            'close' => $close,
            'wet' => $steward && $status === Correspondence::STATUS_APPROVED && $letter->sign_mode === Correspondence::SIGN_MODE_WET,
            'printed' => in_array('printed', $acts, true),
            'wet_signed' => in_array('wet_signed', $acts, true),
            'receive' => $incoming && $registry && in_array($status, [...Correspondence::DRAFT_LIKE, Correspondence::STATUS_SAVED], true),
            'route' => $incoming && $registry && $steward && in_array($status, [Correspondence::STATUS_RECEIVED, Correspondence::STATUS_ROUTED], true),
            'correct' => $incoming && $registry && $steward && $status === Correspondence::STATUS_RECEIVED,
            'pdf' => $status === Correspondence::STATUS_APPROVED,
            'manual' => $steward && $this->manualDelivery($status, $parties),
            'comment' => in_array($status, Correspondence::COMMENTABLE, true),
            'delivery' => $delivery,
            'next' => $this->nextStep($status, $parallel, $awaiting, $pending, $delivery, $close),
            'mail' => correspondenceMailOn(),
            'replace' => $replace,
            'progress' => $status === Correspondence::STATUS_SHARED ? [
                'remaining' => $parallel ? $unsigned : null,
                'signed' => $signed,
                'pending' => $pending,
                'unopened' => $unopened,
            ] : null,
            'deadline' => $this->deadlineDate($letter),
            'deadline_note' => $this->deadlineNote($letter),
            'link' => route('correspondence', ['r' => $letter->id]),
            'versions' => $versions,
            'diff' => $this->memoDiff($letter, $versions),
            'events' => $this->timeline(array_slice($events, -self::TIMELINE_LATEST)),
            'events_hidden' => max(0, count($events) - self::TIMELINE_LATEST),
            'files' => $this->attachmentNames($letter),
            'reply' => $this->replyLink($letter, $user),
            'related' => $this->relatedLink($letter),
            'tones' => [
                'opened' => 'text-[var(--md-sys-color-primary)]',
                'not_opened' => 'text-[var(--md-sys-color-error)]',
                'default' => 'text-[var(--md-sys-color-on-surface-variant)]',
            ],
            'refineBlocks' => $this->refineBlocks($letter),
        ];
    }

    public function editorTools(array $people = []): array
    {
        $list = [];
        foreach ($people as $id => $name) {
            if (!is_numeric($id)) {
                continue;
            }
            $list[] = ['value' => (int) $id, 'label' => (string) $name];
        }

        return [
            'tokens' => LetterForm::MERGE_TOKENS,
            'fonts' => CorrespondenceFonts::options(),
            'sizes' => [11, 12, 13, 14, 16],
            'colors' => ['#000000' => 'مشکی', '#1f3a8a' => 'آبی', '#b91c1c' => 'قرمز', '#166534' => 'سبز', '#6b7280' => 'خاکستری'],
            'fills' => ['#e5e7eb' => 'خاکستری', '#dbeafe' => 'آبی', '#fef3c7' => 'زرد'],
            'people' => $list,
            'buttons' => [
                [
                    ['bold()', 'format_bold', 'درشت', "isActive('bold')"],
                    ['italic()', 'format_italic', 'کج', "isActive('italic')"],
                    ['underline()', 'format_underlined', 'زیرخط', "isActive('underline')"],
                    ['strike()', 'strikethrough_s', 'خط‌خورده', "isActive('strike')"],
                    ['superscript()', 'superscript', 'بالانویس', "isActive('superscript')"],
                    ['subscript()', 'subscript', 'زیرنویس', "isActive('subscript')"],
                ],
                [
                    ["list('bullet')", 'format_list_bulleted', 'فهرست', "isActive('bulletList')"],
                    ["list('ordered')", 'format_list_numbered', 'فهرست شماره‌دار', "isActive('orderedList')"],
                    ['quote()', 'format_quote', 'نقل‌قول', "isActive('blockquote')"],
                    ['outdent()', 'format_indent_decrease', 'کاهش تورفتگی', 'false'],
                    ['indent()', 'format_indent_increase', 'افزایش تورفتگی', 'false'],
                    ['rule()', 'horizontal_rule', 'خط افقی', 'false'],
                    ['pageBreak()', 'insert_page_break', 'شکست صفحه', 'false'],
                ],
                [
                    ["align('right')", 'format_align_right', 'راست‌چین', 'false'],
                    ["align('center')", 'format_align_center', 'وسط‌چین', 'false'],
                    ["align('left')", 'format_align_left', 'چپ‌چین', 'false'],
                    ["align('justify')", 'format_align_justify', 'تراز دوطرفه', 'false'],
                    ["dir('ltr')", 'format_textdirection_l_to_r', 'چپ‌به‌راست', 'false'],
                    ["dir('rtl')", 'format_textdirection_r_to_l', 'راست‌به‌چپ', 'false'],
                ],
                [
                    ['halfSpace()', 'space_bar', 'نیم‌فاصله (Ctrl+Shift+2)', 'false'],
                    ['clearFormat()', 'format_clear', 'پاک‌کردن قالب‌بندی', 'false'],
                ],
            ],
            'table' => [
                ['addRow()', 'add_row_below', 'افزودن ردیف'],
                ['addCol()', 'add_column_right', 'افزودن ستون'],
                ['delRow()', 'remove', 'حذف ردیف'],
                ['delCol()', 'remove', 'حذف ستون'],
                ['mergeCells()', 'call_merge', 'ادغام خانه‌ها'],
                ['toggleHeaderRow()', 'table_rows', 'ردیف سرستون'],
                ['delTable()', 'delete', 'حذف جدول'],
            ],
        ];
    }

    public function attachmentNames(?Correspondence $letter): array
    {
        if ($letter === null || !$letter->exists || !$this->mayView($letter)) {
            return [];
        }

        $names = [];
        foreach (array_values(is_array($letter->spec['attachments'] ?? null) ? $letter->spec['attachments'] : []) as $index => $file) {
            if (is_array($file) && LetterRules::ownsPath((string) ($file['path'] ?? ''), (int) $letter->id)) {
                $names[$index] = trim((string) ($file['name'] ?? '')) ?: 'پیوست';
            }
        }

        return $names;
    }

    public function blockImage(?Correspondence $letter, array $block): string
    {
        return $letter !== null && $letter->exists && $this->mayView($letter)
            ? $this->privateSrc($block['path'] ?? null, 'screen', (int) $letter->id)
            : '';
    }

    public function editorDir(?string $html): string
    {
        $plain = mb_substr(stripHtml($html), 0, 4000);

        return preg_match('/\p{Arabic}/u', $plain) === 1 && preg_match('/[A-Za-z]/', $plain) === 1 ? 'auto' : 'rtl';
    }

    public function name(mixed $id): string
    {
        if (!is_numeric($id)) {
            return '';
        }

        return (string) ($this->options()->get((int) $id) ?: 'کاربر حذف‌شده');
    }

    public function failToast(?string $code): string
    {
        return match ($code) {
            'quota' => __('resources/correspondence/strings.errors.quota'),
            'too_large' => __('resources/correspondence/strings.errors.body_too_large'),
            'blocked' => 'درخواست مسدود شد.',
            'stale' => 'تولید نامه ناتمام ماند.',
            'timeout' => 'پاسخ هوش مصنوعی دیر رسید. دوباره تلاش کنید.',
            'empty', 'invalid_json', 'invalid_output' => 'خروجی هوش مصنوعی قابل‌استفاده نبود. دوباره تلاش کنید.',
            'disabled' => 'نوشتن هوشمند در حال حاضر غیرفعال است.',
            default => 'ذخیره ناموفق بود. دوباره تلاش کنید.',
        };
    }

    public function limits(): array
    {
        return array_map(
            fn ($value) => is_int($value) ? convertToPersian($value) : $value,
            LetterForm::limits()
        );
    }

    public function viewModes(): array
    {
        return [
            ['value' => 'compose', 'icon' => 'edit_note', 'title' => 'فشرده'],
            ['value' => 'report', 'match' => ['report', 'table'], 'icon' => 'table_rows', 'title' => 'کلاسیک'],
        ];
    }

    public function listTabs(): array
    {
        $user = auth()->user();
        $registry = $user instanceof User && CorrespondenceAccessPolicy::isRegistry($user)
            ? ['registry' => __('resources/correspondence/strings.tabs.registry')]
            : [];

        return [
            'waiting' => __('resources/correspondence/strings.tabs.waiting'),
            'mine' => __('resources/correspondence/strings.tabs.mine'),
            'incoming' => __('resources/correspondence/strings.tabs.incoming'),
            'all' => __('resources/correspondence/strings.tabs.all'),
            ...$registry,
            'templates' => __('resources/correspondence/strings.tabs.templates'),
        ];
    }

    public function classicTabs(): array
    {
        return [
            'report' => 'فرم مکاتبات',
            'table' => 'جدول مکاتبات',
        ];
    }

    public function filterLabels(): array
    {
        return [
            'all' => 'همه',
            'draft' => __('resources/correspondence/strings.status.draft'),
            'shared' => __('resources/correspondence/strings.status.shared'),
            'approved' => __('resources/correspondence/strings.status.approved'),
            'overdue' => 'مهلت گذشته',
        ];
    }

    public function roleLabels(): array
    {
        return $this->labels('roles', LetterForm::ROLES);
    }

    public function deliveryLabels(): array
    {
        return $this->labels('delivery_modes', LetterForm::DELIVERIES);
    }

    public function routeModeLabels(): array
    {
        return $this->labels('route_mode', LetterForm::ROUTES);
    }

    public function scopeLabels(): array
    {
        return $this->labels('template_scope', LetterForm::SCOPES);
    }

    private function replyLink(Correspondence $letter, mixed $user): ?array
    {
        if (blank($letter->in_reply_to_id) || !$user instanceof User) {
            return null;
        }

        if ($this->replyParent?->getKey() !== (int) $letter->in_reply_to_id) {
            $letter->loadMissing('replyTo:id,user_id,status,classification,subject,number,recipients,parties,deleted_at');
            $this->replyParent = $letter->replyTo;
        }
        $parent = $this->replyParent;
        if ($parent === null || !CorrespondenceAccessPolicy::canView($user, $parent)) {
            return null;
        }
        $hidden = $parent->classification !== Correspondence::CLASSIFICATION_NORMAL && !CorrespondenceAccessPolicy::isAuthor($user, $parent);

        return [
            'label' => $hidden ? $this->confidentialLabel() : ($parent->subject ?: 'بدون موضوع'),
            'url' => route('correspondence', ['r' => $parent->id]),
        ];
    }

    private function relatedLink(Correspondence $letter): ?array
    {
        $type = (string) $letter->related_type;
        if (!isset(self::RELATED_ROUTES[$type]) || !is_numeric($letter->related_id)) {
            return null;
        }

        return [
            'label' => __("resources/correspondence/strings.related.{$type}") . ' ' . convertToPersian((int) $letter->related_id),
            'url' => route(self::RELATED_ROUTES[$type], ['open' => (int) $letter->related_id]),
        ];
    }

    private function deadlineDate(Correspondence $letter): string
    {
        return $letter->status === Correspondence::STATUS_SHARED && $letter->deadline_at !== null ? toJalali($letter->deadline_at, 'Y/m/d') : '';
    }

    private function deadlineNote(Correspondence $letter): string
    {
        if ($letter->status !== Correspondence::STATUS_SHARED || $letter->deadline_at === null) {
            return '';
        }

        $days = (int) now()->startOfDay()->diffInDays($letter->deadline_at->copy()->startOfDay(), false);

        return $days === 0 ? 'امروز' : convertToPersian(abs($days)) . ' روز ' . ($days < 0 ? 'گذشته' : 'مانده');
    }

    private function nextStep(string $status, bool $parallel, int $awaiting, array $pending, array $delivery, bool $close): string
    {
        if ($status === Correspondence::STATUS_SHARED) {
            $who = $parallel ? implode('، ', $pending) : ($awaiting > 0 ? $this->name($awaiting) : '');

            return $who === '' ? '' : 'در انتظار امضای ' . $who;
        }
        if ($status === Correspondence::STATUS_REJECTED) {
            return 'رد شده';
        }
        if ($status !== Correspondence::STATUS_APPROVED) {
            return '';
        }

        $failed = array_filter($delivery, fn (array $row) => $row['error'] !== '');
        if ($failed === []) {
            return $close && array_filter($delivery, fn (array $row) => $row['can_send']) !== [] ? 'آماده ارسال' : 'تأیید شده';
        }

        return $close && array_filter($failed, fn (array $row) => $row['can_send'] || $row['can_remind']) !== [] ? 'ارسال ناموفق — تلاش دوباره' : 'ارسال ناموفق';
    }

    private function labels(string $group, array $keys): array
    {
        return array_combine($keys, array_map(fn (string $key) => __("resources/correspondence/strings.{$group}.{$key}"), $keys));
    }

    private function typeOptions(?Correspondence $letter, bool $registry): array
    {
        $options = [];
        foreach ((array) config('ai.correspondence.types') as $key => $type) {
            $usable = ($type['enabled'] ?? false) === true && ($registry || ($type['direction'] ?? null) !== Correspondence::DIRECTION_IN);
            if ($usable || $letter?->type === $key) {
                $options[$key] = __('resources/correspondence/strings.types.' . $key);
            }
        }

        return $options;
    }

    private function bodyLines(string $html, int $perLine): int
    {
        $lines = 0;
        foreach (preg_split('#</(?:p|li|tr|h[2-4])>|<br\s*/?>|\R#i', $html) ?: [] as $unit) {
            $length = mb_strlen(stripHtml($unit));
            $lines += $length === 0 ? 0 : (int) ceil($length / $perLine);
        }

        return $lines;
    }

    private function hasAttachment(array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'image') {
                return true;
            }
        }

        return false;
    }

    private function words(array $blocks): array
    {
        $parts = [];
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $parts[] = stripHtml((string) ($block['heading'] ?? '') . ' ' . (string) ($block['html'] ?? $block['text'] ?? '') . ' ' . (string) ($block['caption'] ?? ''));
        }
        $plain = trim(implode(' ', $parts));

        return $plain === '' ? [] : (preg_split('/\s+/u', $plain) ?: []);
    }

    private function overlay(array $defaults, mixed $stored): array
    {
        $stored = array_filter(is_array($stored) ? $stored : [], fn ($value) => $value !== null && $value !== '');

        return array_replace_recursive($defaults, $stored);
    }

    private function borrowedHeads(?Letterhead $head, array $config): Collection
    {
        $ids = [];
        foreach (['header_from', 'footer_from'] as $key) {
            if (is_numeric($config[$key] ?? null) && (int) $config[$key] !== (int) $head?->id) {
                $ids[] = (int) $config[$key];
            }
        }

        return $ids === []
            ? new Collection()
            : Letterhead::query()->whereIn('id', array_unique($ids))->get(['id', 'header_image', 'footer_image'])->keyBy('id');
    }

    private function mayView(Correspondence $letter): bool
    {
        $user = auth()->user();

        return $user instanceof User && CorrespondenceAccessPolicy::canView($user, $letter);
    }

    private function signerOf(array $parties): ?array
    {
        $signer = null;
        foreach ($parties as $party) {
            if (is_array($party) && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER) {
                $signer = $party;
            }
        }

        return $signer;
    }

    private function partyName(array $party): string
    {
        return filled($party['name'] ?? null) ? (string) $party['name'] : $this->name($party['user_id'] ?? null);
    }

    private function publicSrc(mixed $path, bool $pdf): string
    {
        if (!is_string($path) || !str_starts_with($path, 'letterheads/') || str_contains($path, '..') || preg_match('#^[A-Za-z0-9_./-]+$#', $path) !== 1) {
            return '';
        }

        $disk = Storage::disk('public');

        return $pdf ? $disk->path($path) : $disk->url($path);
    }

    private function privateSrc(mixed $path, string $mode, int $letterId): string
    {
        if (!is_string($path) || preg_match('#^[A-Za-z0-9_./-]+$#D', $path) !== 1 || !LetterRules::ownsPath($path, $letterId)) {
            return '';
        }

        $disk = Storage::disk('local');
        if ($mode === 'pdf') {
            return $disk->path($path);
        }

        try {
            return $disk->temporaryUrl($path, $mode === 'screen' ? now()->startOfHour()->addHours(2) : now()->addMinutes(10));
        } catch (Throwable) {
            return '';
        }
    }

    private function paperBlocks(Correspondence $letter, array $blocks, string $mode, bool $allowed, bool $repeatHead): array
    {
        $rows = [];
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $row = ['heading' => trim((string) ($block['heading'] ?? ''))];
            if (($block['type'] ?? null) === 'image') {
                $size = in_array($block['size'] ?? null, Correspondence::BLOCK_SIZES, true) ? $block['size'] : 'medium';
                $rows[] = $row + [
                    'type' => 'image',
                    'src' => $allowed ? $this->privateSrc($block['path'] ?? null, $mode, (int) $letter->id) : '',
                    'width' => self::IMAGE_WIDTHS[$size],
                    'align' => in_array($block['align'] ?? null, Correspondence::BLOCK_ALIGNS, true) ? $block['align'] : 'center',
                    'caption' => trim((string) ($block['caption'] ?? '')),
                ];
            } elseif (is_string($block['html'] ?? null)) {
                if (stripHtml($block['html']) === '' && !str_contains($block['html'], '<table') && $row['heading'] === '') {
                    continue;
                }
                $rows[] = $row + ['type' => 'html', 'html' => $repeatHead ? $this->headRows($block['html']) : $block['html']];
            } else {
                $rows[] = $row + ['type' => 'text', 'text' => (string) ($block['text'] ?? '')];
            }
        }

        return $rows;
    }

    private function headRows(string $html): string
    {
        if (!str_contains($html, '<table') || strlen($html) > 200000) {
            return $html;
        }

        return preg_replace_callback(
            '#(<table\b[^>]*>)(?:<tbody>)?(\s*<tr\b[^>]*>.*?</tr>)#s',
            function (array $m): string {
                if (str_contains($m[2], '<table') || str_contains($m[2], '<td') || !str_contains($m[2], '<th')) {
                    return $m[0];
                }

                return $m[1] . '<thead>' . $m[2] . '</thead><tbody>';
            },
            $html
        ) ?? $html;
    }

    private function letterSnapshot(Correspondence $letter): ?array
    {
        $id = (int) $letter->id;
        if ($this->snapId === $id) {
            return $this->snapMemo;
        }
        $this->snapId = $id;
        $this->snapMemo = $this->readSnapshot($letter);

        return $this->snapMemo;
    }

    private function readSnapshot(Correspondence $letter): ?array
    {
        $meta = is_array($letter->meta) ? $letter->meta : [];
        $snapshot = is_array($meta['snapshot'] ?? null) ? $meta['snapshot'] : null;
        if ($snapshot === null || $snapshot === []) {
            return null;
        }

        $file = $snapshot['file'] ?? null;
        if (!is_string($file)) {
            return $snapshot;
        }

        if (!LetterRules::ownsPath($file, (int) $letter->id) || !str_ends_with($file, '.json') || preg_match('#^[A-Za-z0-9_./-]+$#', $file) !== 1) {
            return $snapshot;
        }

        return $this->snapshotFile($file, $snapshot) ?? $snapshot;
    }

    private function snapshotFile(string $file, array $snapshot): ?array
    {
        $disk = Storage::disk('local');
        try {
            $bytes = $disk->size($file);
        } catch (Throwable) {
            return null;
        }
        if ($bytes > 262144) {
            return null;
        }

        $raw = $disk->get($file);
        $body = is_string($raw) ? json_decode($raw, true, 16) : null;
        if (!is_array($body)) {
            return null;
        }

        return array_replace($body, [
            'images' => is_array($snapshot['images'] ?? null) ? $snapshot['images'] : [],
            'taken_at' => $snapshot['taken_at'] ?? null,
            'frame' => $snapshot['frame'] ?? null,
        ]);
    }

    private function cacheTenant(): string
    {
        $tenant = preg_replace('/[^A-Za-z0-9_-]/', '', (string) config('app.tenant'));

        return is_string($tenant) && $tenant !== '' ? $tenant : '0';
    }

    private function pageParts(string $html): array
    {
        if (!str_contains($html, 'page-break-after') || strlen($html) > 200000) {
            return [$html];
        }

        $tokens = preg_split('/(<[^>]*+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($tokens)) {
            return [$html];
        }

        $parts = [''];
        $depth = 0;
        foreach ($tokens as $token) {
            if ($token !== '' && $token[0] === '<') {
                if ($depth === 0 && preg_match('/^<hr\b[^>]*page-break-after:\s*always/i', $token) === 1) {
                    $parts[] = '';

                    continue;
                }
                if (preg_match('/^<(\/?)([a-z][a-z0-9]*)/i', $token, $tag) === 1 && !in_array(strtolower($tag[2]), Correspondence::VOID_ELEMENTS, true)) {
                    $depth = max(0, $depth + ($tag[1] === '/' ? -1 : 1));
                }
            }
            $parts[count($parts) - 1] .= $token;
        }

        $parts = array_values(array_filter($parts, fn (string $part) => trim($part) !== ''));

        return $parts === [] ? [''] : $parts;
    }

    private function latinBlocks(array $blocks, bool $split = false): array
    {
        foreach ($blocks as $index => $block) {
            $block['heading'] = $this->latinText((string) ($block['heading'] ?? ''));
            if (($block['type'] ?? null) === 'html') {
                $block['html'] = $this->latinHtml((string) ($block['html'] ?? ''));
                $block['parts'] = $split ? $this->pageParts($block['html']) : [$block['html']];
            } elseif (($block['type'] ?? null) === 'text') {
                $block['text'] = $this->latinText((string) ($block['text'] ?? ''));
            } elseif (($block['type'] ?? null) === 'image') {
                $block['caption'] = $this->latinText((string) ($block['caption'] ?? ''));
            }
            $blocks[$index] = $block;
        }

        return $blocks;
    }

    private function latinText(string $text): string
    {
        return $this->latinHtml(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    private function latinHtml(string $html): string
    {
        if ($html === '' || strlen($html) > 200000 || !preg_match('/[A-Za-z]/', $html)) {
            return $html;
        }

        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return $html;
        }

        $out = '';
        foreach ($parts as $part) {
            if ($part === '' || $part[0] === '<') {
                $out .= $part;
                continue;
            }
            $out .= preg_replace('/&#?[A-Za-z0-9]+;(*SKIP)(*FAIL)|[A-Za-z]+/', '<span class="paper-latin">$0</span>', $part) ?? $part;
        }

        return $out;
    }

    private function memoDiff(Correspondence $letter, array $versions): ?array
    {
        if ($versions === []) {
            return null;
        }

        $hash = (string) $letter->content_hash;
        if (preg_match('/^[a-f0-9]{32,64}$/', $hash) !== 1) {
            return $this->diffOf($letter, $versions);
        }

        $id = (int) $letter->id;
        $last = end($versions);
        $stamp = preg_replace('/[^A-Za-z0-9]/', '', (string) (is_array($last) ? ($last['at'] ?? '') : ''));
        $key = 'correspondence-diff-' . $this->cacheTenant() . '-' . $id . '-' . $hash . '-' . count($versions) . '-' . hash('sha256', $stamp);
        $cached = Cache::get($key);
        if (is_array($cached) && (int) ($cached['letter_id'] ?? 0) === $id && ($cached['hash'] ?? null) === $hash && is_array($cached['diff'] ?? null)) {
            return $cached['diff'];
        }
        $diff = $this->diffOf($letter, $versions);
        Cache::put($key, ['letter_id' => $id, 'hash' => $hash, 'diff' => $diff], now()->addHour());

        return $diff;
    }

    private function diffOf(Correspondence $letter, array $versions): array
    {
        $latest = end($versions);

        return $this->wordDiff(
            is_array($latest['spec']['blocks'] ?? null) ? $latest['spec']['blocks'] : [],
            is_array($letter->spec['blocks'] ?? null) ? $letter->spec['blocks'] : []
        );
    }

    private function refineBlocks(Correspondence $letter): array
    {
        $blocks = is_array($letter->spec['blocks'] ?? null) ? $letter->spec['blocks'] : [];
        $rows = [];
        foreach ($blocks as $index => $block) {
            if (!is_array($block) || !in_array($block['type'] ?? null, self::REFINABLE, true) || count($rows) >= 40) {
                continue;
            }
            $label = trim((string) ($block['heading'] ?? ''));
            $rows[] = [
                'index' => (int) $index,
                'hash' => LetterRules::blockHash($block),
                'label' => mb_substr($label !== '' ? $label : 'بخش ' . convertToPersian((int) $index + 1), 0, 80),
            ];
        }

        return $rows;
    }

    private function options(): Collection
    {
        return $this->names ??= User::getCachedAllOptions();
    }

    private function rules(array $quota): array
    {
        $limits = LetterForm::limits();

        return [
            'سهمیه' => 'روزانه ' . convertToPersian((string) $quota['cap']) . ' بار می‌توانید نامهٔ هوشمند بسازید. ذخیرهٔ دستی و امضا سهمیه مصرف نمی‌کنند.',
            'فایل‌ها' => 'حداکثر ' . convertToPersian((string) $limits['files']) . ' پیوست، هرکدام تا ' . convertToPersian((int) ($limits['per_file_kb'] / 1024)) . ' مگابایت.',
            'محرمانه' => 'برای نامهٔ محرمانه حالت هوشمند در دسترس نیست.',
            'الگو' => 'جاهای خالی ' . implode(' و ', LetterForm::MERGE_TOKENS) . '، مثل «[…]»، تا پر شدن مانع ارسال و تأیید می‌شوند.',
        ];
    }

    private function quotaTone(bool $exhausted, int $quotaPct): array
    {
        if ($exhausted) {
            return ['text' => 'text-[var(--md-sys-color-error)]', 'fill' => 'bg-[var(--md-sys-color-error)]'];
        }

        return $quotaPct >= 80
            ? ['text' => 'text-[var(--tool-gold-color)]', 'fill' => 'bg-[var(--tool-gold-color)]']
            : ['text' => 'text-[var(--md-sys-color-on-surface-variant)]', 'fill' => 'bg-[var(--md-sys-color-primary)]'];
    }

    private const DOTS = [
        'draft' => 'bg-[var(--md-sys-color-outline)]',
        'generating' => 'bg-[var(--md-sys-color-primary)]',
        'failed' => 'bg-[var(--md-sys-color-error)]',
        'saved' => 'bg-[var(--md-sys-color-primary)]',
        'shared' => 'bg-[var(--tool-gold-color)]',
        'approved' => 'bg-[var(--md-sys-color-primary)]',
        'rejected' => 'bg-[var(--md-sys-color-error)]',
        'withdrawn' => 'bg-[var(--md-sys-color-outline)]',
        'void' => 'bg-[var(--md-sys-color-outline)]',
        'superseded' => 'bg-[var(--md-sys-color-outline)]',
        'received' => 'bg-[var(--md-sys-color-primary)]',
        'routed' => 'bg-[var(--tool-gold-color)]',
        'done' => 'bg-[var(--md-sys-color-primary)]',
        'overdue' => 'bg-[var(--md-sys-color-error)]',
    ];
}
