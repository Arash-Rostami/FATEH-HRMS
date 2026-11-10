<?php

namespace App\Jobs;

use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Mail\NotifyMail;
use App\Models\Correspondence;
use App\Services\Notify\Message;
use App\Services\Correspondence\CorrespondencePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendCorrespondenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = self::WINDOW;

    public bool $failOnTimeout = true;

    private const WINDOW = 150;

    private const BATCH = 5;

    private const ATTACH_MAX = 15728640;

    private const DEFAULT_TTL_DAYS = 30;

    private const LOCK_COLUMNS = ['id', 'status', 'classification', 'sign_mode', 'parties', 'events', 'content_hash', 'pdf_hash'];

    private array $mailText = [];

    private const SUBJECT = 'نامه';

    private const NAME_MAX = 60;

    private const LINE = 'یک نامه برای شما ارسال شده است. برای دیدن آن از پیوند زیر استفاده کنید.';

    private const PDF_LINE = 'یک نامه برای شما ارسال شده است. برای دریافت فایل از پیوند زیر استفاده کنید.';

    private const PDF_FILE = 'یک نامه برای شما ارسال شده است. فایل نامه پیوست این پیام است.';

    public function __construct(
        public int $letterId,
        public array $partyIndexes = [],
        public bool $resend = false,
        public ?int $userId = null,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        try {
            $this->deliver();
        } catch (Throwable $e) {
            Log::warning('correspondence send failed', ['letter' => $this->letterId, 'error' => $e::class]);
            $this->stampError('send_failed');
        }
    }

    public function failed(Throwable $e): void
    {
        $this->stampError('send_failed');
    }

    public static function revoke(Correspondence $letter, int $partyIndex, ?int $userId = null): void
    {
        DB::transaction(function () use ($letter, $partyIndex, $userId): void {
            $row = Correspondence::query()->whereKey($letter->getKey())->lockForUpdate()->first(self::LOCK_COLUMNS);
            $parties = is_array($row?->parties) ? $row->parties : [];
            if (!$row || !isset($parties[$partyIndex]) || !is_array($parties[$partyIndex]) || filled($parties[$partyIndex]['user_id'] ?? null)) {
                return;
            }

            $parties[$partyIndex]['token_hash'] = null;
            $parties[$partyIndex]['expires_at'] = null;
            unset($parties[$partyIndex]['token_hash_next'], $parties[$partyIndex]['expires_at_next']);
            $events = is_array($row->events) ? $row->events : [];
            if (count($events) < 900) {
                $events[] = [
                    'at' => now()->toIso8601String(),
                    'user_id' => $userId,
                    'action' => 'link_revoked',
                    'target' => self::label($parties[$partyIndex], (string) ($parties[$partyIndex]['email'] ?? '')),
                    'text' => null,
                ];
            }
            self::writeQuiet($row, ['parties' => $parties, 'events' => $events]);
        });
    }

    public static function label(array $party, string $email = ''): string
    {
        $label = trim((string) ($party['name'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($party['org'] ?? ''));
        }
        if ($label === '') {
            $label = $email;
        }
        $label = mb_substr($label, 0, 500);
        $collapsed = preg_replace('/\s+/u', ' ', $label);

        return mb_substr(trim($collapsed ?? ''), 0, 120);
    }

    private function deliver(): void
    {
        if (tenantOff('correspondence') || !correspondenceMailOn()) {
            return;
        }

        if ($this->resend && $this->wanted() === []) {
            return;
        }

        $letter = Correspondence::query()->whereKey($this->letterId)->first([...CorrespondencePdf::renderColumns(), 'events', 'meta']);
        if (!$letter || $letter->status !== Correspondence::STATUS_APPROVED) {
            return;
        }
        $this->mailText = is_array($letter->meta['mail'] ?? null) ? $letter->meta['mail'] : [];

        if ($letter->classification !== Correspondence::CLASSIFICATION_NORMAL || !$this->wetReady($letter)) {
            $this->refuse($letter->classification === Correspondence::CLASSIFICATION_NORMAL ? 'wet_pending' : 'refused');

            return;
        }

        $indexes = $this->targets($letter);
        if ($indexes === []) {
            return;
        }

        $batch = array_slice($indexes, 0, self::BATCH);
        $rest = array_slice($indexes, self::BATCH);
        if ($rest !== []) {
            self::dispatch($this->letterId, array_values($rest), $this->resend, $this->userId);
        }
        $this->partyIndexes = array_values($batch);

        $pdf = $this->needsPdf($letter, $batch) ? $this->buildPdf($letter) : null;
        $plan = $this->claim($batch, $pdf);
        $failed = [];

        foreach ($plan as $item) {
            try {
                $this->mail($item);
            } catch (Throwable) {
                $failed[] = $item['index'];
            }
        }

        foreach ($plan as $key => $item) {
            unset($plan[$key]['token']);
        }

        if ($plan !== []) {
            $this->finish($plan, $failed);
        }
    }

    private function claim(array $indexes, ?array $pdf): array
    {
        return DB::transaction(function () use ($indexes, $pdf): array {
            $letter = Correspondence::query()->whereKey($this->letterId)->lockForUpdate()->first(self::LOCK_COLUMNS);
            if (!$letter || $letter->status !== Correspondence::STATUS_APPROVED || $letter->classification !== Correspondence::CLASSIFICATION_NORMAL || !$this->wetReady($letter)) {
                return [];
            }

            $parties = is_array($letter->parties) ? $letter->parties : [];
            $modes = array_values(array_filter((array) config('ai.correspondence.delivery_modes'), fn ($mode) => is_string($mode)));
            $days = config('ai.correspondence.link_ttl_days');
            $expires = $days === null ? null : now()->addDays(is_numeric($days) && (int) $days > 0 ? (int) $days : self::DEFAULT_TTL_DAYS)->toIso8601String();
            $cap = max(1, (int) config('ai.correspondence.max_recipients', 20));
            $used = 0;
            foreach ($parties as $existing) {
                if (!is_array($existing) || filled($existing['user_id'] ?? null)) {
                    continue;
                }
                if (filled($existing['sent_at'] ?? null) || ($existing['delivery_error'] ?? null) === 'sending') {
                    $used++;
                }
            }
            $plan = [];
            $changed = false;
            $pdfReady = is_array($pdf);

            foreach ($indexes as $index) {
                $party = $parties[$index] ?? null;
                if (!is_array($party) || filled($party['user_id'] ?? null)) {
                    continue;
                }
                if (self::sendingFresh($party)) {
                    continue;
                }
                if (filled($party['sent_at'] ?? null) && !$this->resend) {
                    continue;
                }

                $mode = (string) ($party['delivery'] ?? '');
                if (!in_array($mode, $modes, true)) {
                    continue;
                }
                $counted = filled($party['sent_at'] ?? null) || ($party['delivery_error'] ?? null) === 'sending';
                if (!$counted && $used >= $cap) {
                    $party['delivery_error'] = 'too_many';
                    $parties[$index] = $party;
                    $changed = true;
                    continue;
                }

                $email = trim((string) ($party['email'] ?? ''));
                if ($email === '') {
                    $party['delivery_error'] = 'no_email';
                    $parties[$index] = $party;
                    $changed = true;
                    continue;
                }
                if (mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $party['delivery_error'] = 'bad_email';
                    $parties[$index] = $party;
                    $changed = true;
                    continue;
                }
                if ($mode === 'pdf' && !$pdfReady) {
                    $party['delivery_error'] = 'pdf_failed';
                    $parties[$index] = $party;
                    $changed = true;
                    continue;
                }

                $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
                $priorSent = $party['sent_at'] ?? null;
                $resending = filled($priorSent);
                $party[$resending ? 'token_hash_next' : 'token_hash'] = hash('sha256', $token);
                $party[$resending ? 'expires_at_next' : 'expires_at'] = $expires;
                $party['delivery_error'] = 'sending';
                $party['sending_at'] = now()->toIso8601String();
                $parties[$index] = $party;
                $changed = true;
                if (!$counted) {
                    $used++;
                }
                $plan[] = [
                    'index' => $index,
                    'email' => $email,
                    'mode' => $mode,
                    'token' => $token,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => $expires,
                    'target' => self::label($party, $email),
                    'prior_sent_at' => $resending ? $priorSent : null,
                    'pdf_note' => $mode === 'both' && !$pdfReady ? 'pdf_failed' : null,
                    'attach' => ($mode === 'pdf' || $mode === 'both') ? ($pdf['attach'] ?? null) : null,
                ];
            }

            if ($changed) {
                self::writeQuiet($letter, ['parties' => $parties]);
            }

            return $plan;
        });
    }

    private function mail(array $item): void
    {
        $attached = is_string($item['attach'] ?? null) && $item['attach'] !== '' && is_file($item['attach']);
        $mode = (string) $item['mode'];
        $url = null;
        $action = null;
        $line = self::LINE;
        if ($mode === 'link' || $mode === 'both') {
            $url = route('correspondence.link', ['id' => $this->letterId, 'token' => $item['token']]);
            $action = 'مشاهده نامه';
        } elseif ($attached) {
            $line = self::PDF_FILE;
        } else {
            $url = route('correspondence.link.pdf', ['id' => $this->letterId, 'token' => $item['token']]);
            $action = 'دریافت PDF';
            $line = self::PDF_LINE;
        }

        Mail::to($item['email'])->send(new NotifyMail(new Message(
            subject: $this->subject(),
            lines: $this->lines($line),
            ruleName: 'correspondence',
            modelLabel: __('resources/correspondence/strings.label'),
            event: 'ارسال',
            template: 'styled',
            url: $url,
            action: $action,
            attachments: $attached ? [$item['attach']] : [],
        )));
    }

    private function subject(): string
    {
        $custom = $this->singleLine($this->mailText['subject'] ?? '', LetterRules::MAIL_SUBJECT_MAX);
        if ($custom !== '') {
            return $custom;
        }
        $name = $this->singleLine(config('app.name'), self::NAME_MAX);

        return $name === '' ? self::SUBJECT : self::SUBJECT . ' از ' . $name;
    }

    private function singleLine(mixed $value, int $cap): string
    {
        return is_scalar($value) ? mb_substr(trim((string) preg_replace('/\s+/u', ' ', (string) $value)), 0, $cap) : '';
    }

    private function lines(string $default): array
    {
        $body = $this->mailText['body'] ?? '';
        $lines = is_string($body) ? preg_split('/\R+/u', mb_substr($body, 0, LetterRules::MAIL_BODY_MAX)) : false;
        $lines = array_values(array_filter(array_map('trim', is_array($lines) ? $lines : []), fn (string $line) => $line !== ''));

        return $lines === [] ? [$default] : $lines;
    }

    private function finish(array $plan, array $failed): void
    {
        DB::transaction(function () use ($plan, $failed): void {
            $letter = Correspondence::query()->whereKey($this->letterId)->lockForUpdate()->first(self::LOCK_COLUMNS);
            if (!$letter) {
                return;
            }

            $parties = is_array($letter->parties) ? $letter->parties : [];
            $events = is_array($letter->events) ? $letter->events : [];

            foreach ($plan as $item) {
                if (!isset($parties[$item['index']]) || !is_array($parties[$item['index']])) {
                    continue;
                }
                unset($parties[$item['index']]['sending_at']);
                $resending = filled($item['prior_sent_at'] ?? null);
                if ($resending && ($parties[$item['index']]['token_hash_next'] ?? null) !== $item['token_hash']) {
                    continue;
                }
                if (in_array($item['index'], $failed, true)) {
                    if ($resending) {
                        unset($parties[$item['index']]['token_hash_next'], $parties[$item['index']]['expires_at_next']);
                    } else {
                        $parties[$item['index']]['sent_at'] = null;
                        $parties[$item['index']]['token_hash'] = null;
                        $parties[$item['index']]['expires_at'] = null;
                    }
                    $parties[$item['index']]['delivery_error'] = 'mail_failed';
                    continue;
                }
                $sentAt = now()->toIso8601String();
                $parties[$item['index']]['token_hash'] = $item['token_hash'];
                $parties[$item['index']]['expires_at'] = $item['expires_at'];
                unset($parties[$item['index']]['token_hash_next'], $parties[$item['index']]['expires_at_next']);
                $parties[$item['index']]['sent_at'] = $sentAt;
                $parties[$item['index']]['delivery_error'] = $item['pdf_note'];
                if ($this->resend) {
                    $parties[$item['index']]['opened_at'] = null;
                    $parties[$item['index']]['open_count'] = 0;
                    $parties[$item['index']]['last_opened_at'] = null;
                }
                if (count($events) < 900) {
                    $events[] = [
                        'at' => $sentAt,
                        'user_id' => $this->userId,
                        'action' => 'sent',
                        'target' => $item['target'],
                        'text' => $item['mode'],
                        'hash' => filled($letter->pdf_hash) ? $letter->pdf_hash : $letter->content_hash,
                    ];
                }
            }

            self::writeQuiet($letter, ['parties' => $parties, 'events' => $events]);
        });
    }

    private function refuse(string $code): void
    {
        DB::transaction(function () use ($code): void {
            $letter = Correspondence::query()->whereKey($this->letterId)->lockForUpdate()->first(self::LOCK_COLUMNS);
            if (!$letter) {
                return;
            }

            $parties = is_array($letter->parties) ? $letter->parties : [];
            $wanted = $this->wanted();
            foreach ($parties as $index => $party) {
                if (!is_array($party) || filled($party['user_id'] ?? null)) {
                    continue;
                }
                if ($wanted !== [] && !in_array((int) $index, $wanted, true)) {
                    continue;
                }
                $party['token_hash'] = null;
                $party['expires_at'] = null;
                unset($party['token_hash_next'], $party['expires_at_next']);
                $party['delivery_error'] = $code;
                $parties[$index] = $party;
            }

            self::writeQuiet($letter, ['parties' => $parties]);
        });
    }

    private function targets(Correspondence $letter): array
    {
        $wanted = $this->wanted();
        $indexes = [];
        $seen = 0;
        foreach (is_array($letter->parties) ? $letter->parties : [] as $index => $party) {
            if (++$seen > 100) {
                break;
            }
            if (!is_array($party) || filled($party['user_id'] ?? null)) {
                continue;
            }
            if ($wanted === [] || in_array((int) $index, $wanted, true)) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private function wanted(): array
    {
        $indexes = [];
        foreach ($this->partyIndexes as $index) {
            if (is_numeric($index)) {
                $indexes[] = (int) $index;
            }
        }

        return $indexes;
    }

    private function needsPdf(Correspondence $letter, array $indexes): bool
    {
        $parties = is_array($letter->parties) ? $letter->parties : [];
        foreach ($indexes as $index) {
            $mode = (string) ($parties[$index]['delivery'] ?? '');
            if ($mode === 'pdf' || $mode === 'both') {
                return true;
            }
        }

        return false;
    }

    private function buildPdf(Correspondence $letter): ?array
    {
        try {
            $relative = CorrespondencePdf::path($letter);
            $file = CorrespondencePdf::absolute($relative);
            if ($file === null) {
                return ['attach' => null];
            }
            $bytes = filesize($file);

            return ['attach' => $bytes !== false && $bytes <= self::ATTACH_MAX ? $file : null];
        } catch (RuntimeException) {
            return null;
        }
    }

    private function wetReady(Correspondence $letter): bool
    {
        if ($letter->sign_mode !== Correspondence::SIGN_MODE_WET) {
            return true;
        }

        foreach (is_array($letter->events) ? $letter->events : [] as $event) {
            if (is_array($event) && ($event['action'] ?? null) === 'wet_signed') {
                return true;
            }
        }

        return false;
    }

    public static function sendingFresh(array $party): bool
    {
        if (($party['delivery_error'] ?? null) !== 'sending') {
            return false;
        }
        $at = $party['sending_at'] ?? null;
        if (!is_string($at) || strlen($at) > 40) {
            return false;
        }
        $stamp = strtotime($at);

        return $stamp !== false && $stamp > time() - self::WINDOW;
    }

    private function stampError(string $code): void
    {
        DB::transaction(function () use ($code): void {
            $letter = Correspondence::query()->whereKey($this->letterId)->lockForUpdate()->first(self::LOCK_COLUMNS);
            if (!$letter) {
                return;
            }

            $parties = is_array($letter->parties) ? $letter->parties : [];
            $wanted = $this->wanted();
            $changed = false;
            foreach ($parties as $index => $party) {
                if (!is_array($party) || filled($party['user_id'] ?? null)) {
                    continue;
                }
                if ($wanted !== [] && !in_array((int) $index, $wanted, true)) {
                    continue;
                }
                $error = $party['delivery_error'] ?? null;
                $sent = filled($party['sent_at'] ?? null);
                if ($error !== 'sending' && ($sent || $error !== null)) {
                    continue;
                }
                unset($party['sending_at']);
                if ($sent) {
                    $party['delivery_error'] = 'mail_failed';
                } else {
                    $party['token_hash'] = null;
                    $party['expires_at'] = null;
                    $party['delivery_error'] = $code;
                }
                $parties[$index] = $party;
                $changed = true;
            }

            if ($changed) {
                self::writeQuiet($letter, ['parties' => $parties]);
            }
        });
    }

    public static function writeQuiet(Correspondence $letter, array $columns): void
    {
        $payload = [];
        foreach ($columns as $column => $value) {
            $payload[$column] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        if ($payload === []) {
            return;
        }

        $letter->newQuery()->whereKey($letter->getKey())->toBase()->update($payload);
    }
}
