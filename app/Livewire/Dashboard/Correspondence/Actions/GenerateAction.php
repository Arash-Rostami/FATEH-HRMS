<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Jobs\GenerateCorrespondenceJob;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Support\CorrespondenceAccessPolicy;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GenerateAction
{
    private const IN_PLACE = [Correspondence::STATUS_DRAFT, Correspondence::STATUS_SAVED, Correspondence::STATUS_FAILED];

    private const REFINABLE = ['text', 'html'];

    public function __construct(private readonly SaveLetterAction $update, private readonly TemplateAction $templates)
    {
    }

    public function execute(User $user, LetterForm $form, ?Correspondence $letter, array $changes = []): Correspondence
    {
        $field = 'form.prompt';
        $form->prompt = trim($form->prompt);
        $form->validate(Arr::only($form->rules(), ['type', 'prompt', 'audience', 'tone', 'length']));

        $source = $letter !== null ? Correspondence::query()->find($letter->id) : null;
        if ($letter !== null && !$source) {
            LetterRules::deny();
        }
        $this->assertAllowed($form->type, $form->classification, $field);
        if ($source !== null) {
            $this->assertAllowed((string) $source->type, (string) $source->classification, $field);
        }

        $fork = $source !== null && !$this->inPlace($source, $user);
        $resolve = fn () => $fork
            ? [$this->templates->duplicate($user, (int) $source->id), (int) $source->id]
            : [$this->update->execute($user, [
                'payload' => $form->payload(),
                'expected_hash' => $changes['expected_hash'] ?? null,
                'reply_to' => $changes['reply_to'] ?? null,
            ], $letter), null];

        return $this->start($user, $field, ['kind' => 'generate', ...$this->settings($form->payload())], $resolve);
    }

    public function refine(User $user, Correspondence $letter, int $block, string $instruction, ?string $blockHash = null): Correspondence
    {
        $field = 'refineText';
        $cap = (int) config('ai.correspondence.prompt.instruction_max');
        $text = trim(strip_tags(mb_substr($instruction, 0, $cap + 1)));
        if ($text === '') {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.text_required')]);
        }
        if (mb_strlen($text) > $cap) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.instruction_too_long')]);
        }

        $source = Correspondence::query()->find($letter->id);
        if (!$source || !CorrespondenceAccessPolicy::canView($user, $source)) {
            LetterRules::deny();
        }
        $this->assertAllowed((string) $source->type, (string) $source->classification, $field);

        $original = $source->spec['blocks'][$block] ?? null;
        if (!is_array($original) || !in_array($original['type'] ?? null, self::REFINABLE, true)) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.block_invalid')]);
        }
        $hash = LetterRules::blockHash($original);
        if ($blockHash !== null && !hash_equals($hash, $blockHash)) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.block_changed')]);
        }

        $fork = !$this->inPlace($source, $user);
        $resolve = fn () => $fork
            ? [$this->templates->duplicate($user, (int) $source->id), (int) $source->id]
            : [$source, null];

        return $this->start($user, $field, ['kind' => 'refine', 'block' => $block, 'block_hash' => $hash, 'instruction' => $text, ...$this->settings($source->lastVersion())], $resolve);
    }

    public function retry(User $user, Correspondence $letter): Correspondence
    {
        $row = Correspondence::query()->find($letter->id);
        $version = $row ? $this->lastRun($row) : [];
        if (!$row
            || $row->status !== Correspondence::STATUS_FAILED
            || !$this->inPlace($row, $user)
            || in_array($row->fail_reason, (array) config('ai.correspondence.no_refund'), true)
            || !in_array($version['kind'] ?? null, ['generate', 'refine'], true)) {
            LetterRules::deny();
        }

        $refine = $version['kind'] === 'refine';
        $field = $refine ? 'refineText' : 'form.prompt';
        $this->assertAllowed((string) $row->type, (string) $row->classification, $field);

        $run = ['kind' => $version['kind'], ...$this->settings($version)];
        if ($refine) {
            $block = (int) ($version['block'] ?? -1);
            $current = $row->spec['blocks'][$block] ?? null;
            if (!is_array($current) || !in_array($current['type'] ?? null, self::REFINABLE, true)) {
                throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.block_invalid')]);
            }
            $run += ['block' => $block, 'block_hash' => LetterRules::blockHash($current), 'instruction' => (string) ($version['instruction'] ?? '')];
        }

        $forked = is_numeric($version['forked_from'] ?? null) ? (int) $version['forked_from'] : null;

        return $this->start($user, $field, $run, fn () => [$row, $forked]);
    }

    private function start(User $user, string $field, array $run, Closure $resolve): Correspondence
    {
        if (AiClient::cooldownActive('correspondence', $user->id)) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.busy')]);
        }

        $token = Str::random(16);

        try {
            $letter = Cache::lock('ai-correspondence:generate:' . $user->id, (int) config('ai.correspondence.lock.ttl'))
                ->block((int) config('ai.correspondence.lock.wait'), function () use ($user, $run, $field, $token, $resolve) {
                    $this->assertQuota($user, $field);
                    $locked = DB::transaction(function () use ($user, $run, $field, $token, $resolve) {
                        $this->assertIdle($user->id, $field);
                        [$target, $forkedFrom] = $resolve();
                        $row = Correspondence::query()->whereKey($target->id)->lockForUpdate()->first();
                        if (!$row || !$this->inPlace($row, $user)) {
                            LetterRules::deny();
                        }
                        $this->assertAllowed((string) $row->type, (string) $row->classification, $field);
                        if ($run['kind'] === 'refine') {
                            $current = $row->spec['blocks'][$run['block']] ?? null;
                            if (!is_array($current) || !hash_equals($run['block_hash'], LetterRules::blockHash($current))) {
                                throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.block_changed')]);
                            }
                        }

                        $version = [
                            ...$run,
                            'ai' => true,
                            'run' => $token,
                            'user_id' => $user->id,
                            'at' => now()->toIso8601String(),
                            'quota_day' => now()->format('Y-m-d'),
                            'from' => $row->status === Correspondence::STATUS_SAVED ? Correspondence::STATUS_SAVED : Correspondence::STATUS_DRAFT,
                        ];
                        if ($forkedFrom !== null) {
                            $version['forked_from'] = $forkedFrom;
                        }

                        $row->forceFill(['status' => Correspondence::STATUS_GENERATING, 'mode' => Correspondence::MODE_SMART, 'fail_reason' => null]);
                        $row->pushVersion($version);
                        $row->save();

                        return $row;
                    });

                    try {
                        $used = Correspondence::consumeQuota($user->id);
                    } catch (Throwable) {
                        Correspondence::query()->whereKey($locked->id)->where('status', Correspondence::STATUS_GENERATING)
                            ->update(['status' => Correspondence::STATUS_FAILED, 'fail_reason' => 'exception']);

                        throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.busy')]);
                    }
                    if ($used > Correspondence::quotaCap()) {
                        $locked->markFailed('quota');

                        throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.quota')]);
                    }

                    return $locked;
                });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.busy')]);
        }

        try {
            GenerateCorrespondenceJob::dispatch($letter->id, $token);
            AiClient::startCooldown('correspondence', $user->id);
        } catch (Throwable $e) {
            $letter->markFailed('exception');

            throw $e;
        }

        return $letter;
    }

    private function assertAllowed(string $type, string $classification, string $field): void
    {
        abort_unless(correspondenceAiOn(), 404);

        if ($classification !== Correspondence::CLASSIFICATION_NORMAL) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.confidential_smart')]);
        }

        $config = config("ai.correspondence.types.{$type}");
        if (!is_array($config) || ($config['enabled'] ?? false) !== true || ($config['direction'] ?? null) !== Correspondence::DIRECTION_OUT || config("ai.correspondence.prompt.type_frames.{$type}") === null) {
            LetterRules::invalid($field);
        }
    }

    private function assertQuota(User $user, string $field): void
    {
        if (Correspondence::quotaUsed($user->id) >= Correspondence::quotaCap()) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.quota')]);
        }
    }

    private function assertIdle(int $userId, string $field): void
    {
        if (Correspondence::query()->freshGenerating($userId)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.busy')]);
        }
    }

    private function inPlace(Correspondence $letter, User $user): bool
    {
        return $letter->isOwnedBy($user)
            && in_array($letter->status, self::IN_PLACE, true)
            && CorrespondenceAccessPolicy::canEdit($user, $letter);
    }

    private function lastRun(Correspondence $letter): array
    {
        foreach (array_reverse($letter->versions ?? []) as $version) {
            if (is_array($version) && ($version['ai'] ?? false) === true) {
                return $version;
            }
        }

        return [];
    }

    private function settings(array $version): array
    {
        $settings = [
            'prompt' => is_string($version['prompt'] ?? null) ? mb_substr($version['prompt'], 0, LetterForm::limits()['prompt_max']) : '',
            'audience' => LetterForm::AUDIENCES[0],
            'tone' => LetterForm::TONES[0],
            'length' => LetterForm::LENGTHS[1],
        ];
        foreach (['audience' => LetterForm::AUDIENCES, 'tone' => LetterForm::TONES, 'length' => LetterForm::LENGTHS] as $key => $allowed) {
            if (in_array($version[$key] ?? null, $allowed, true)) {
                $settings[$key] = $version[$key];
            }
        }

        return $settings;
    }
}
