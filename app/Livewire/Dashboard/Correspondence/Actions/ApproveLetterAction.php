<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class ApproveLetterAction
{
    private const CODE_BYTES = 72;

    public function execute(int $id, array $payload = []): void
    {
        $copied = [];
        $strikes = [];

        LetterFlow::run($id, function (Correspondence $row, User $actor) use ($payload, &$copied, &$strikes) {
            $this->approve($row, $actor, $payload, $copied, $strikes);
        }, $copied, $strikes);
    }

    private function approve(Correspondence $row, User $actor, array $payload, array &$copied, array &$strikes): void
    {
        CorrespondenceAccessPolicy::canView($actor, $row) || LetterRules::deny();

        $parties = LetterFlow::parties($row);
        $me = (int) $actor->id;
        $mine = LetterFlow::approverIndex($parties, $me);
        $blankCode = trim((string) (is_scalar($payload['code'] ?? null) ? $payload['code'] : '')) === '';
        $parallel = $row->route_mode === Correspondence::ROUTE_PARALLEL;
        $wanted = $parallel && is_numeric($payload['target_id'] ?? null) ? (int) $payload['target_id'] : 0;

        if ($mine !== null && $blankCode && $wanted <= 0 && filled($parties[$mine]['signed_at'] ?? null)
            && in_array($row->status, [Correspondence::STATUS_SHARED, Correspondence::STATUS_APPROVED], true)) {
            return;
        }

        if ($row->status !== Correspondence::STATUS_SHARED || $row->direction !== Correspondence::DIRECTION_OUT) {
            throw LetterRules::fail('status', 'not_awaiting');
        }
        LetterFlow::expect($row, $payload);
        if (LetterFlow::presenter()->hasUnfilled($row)) {
            throw LetterRules::fail('form', 'placeholders');
        }

        $awaiting = (int) $row->awaiting_user_id;
        $targetId = $me;
        $index = $mine;

        if ($mine === null || filled($parties[$mine]['signed_at'] ?? null) || (!$parallel && $awaiting !== $me) || ($wanted > 0 && $wanted !== $me)) {
            $targetId = $wanted > 0 ? $wanted : ($parallel ? 0 : $awaiting);
            $index = $targetId > 0 && $targetId !== $me ? LetterFlow::approverIndex($parties, $targetId) : null;

            if ($index === null || filled($parties[$index]['signed_at'] ?? null)) {
                throw LetterRules::fail('status', 'not_awaiting');
            }

            $this->verifyCode($actor, $targetId, $payload['code'] ?? null, $strikes);
        }

        $parties[$index]['signed_at'] = now()->toIso8601String();
        $row->parties = $parties;
        $targetId === $me
            ? LetterFlow::log($row, $actor, 'approved')
            : LetterFlow::log($row, $actor, 'approved_instead', ['target_id' => $targetId]);
        LetterFlow::writeSteps($row);

        if (LetterFlow::unsignedApprovers($row->parties) === 0) {
            LetterSeal::finalize($row, $copied);
        }

        $row->save();
    }

    private function verifyCode(User $actor, int $targetId, mixed $code, array &$strikes): void
    {
        $keys = ["sign-code:pair:{$actor->id}:{$targetId}", "sign-code:actor:{$actor->id}"];

        if (RateLimiter::tooManyAttempts($keys[0], (int) config('ai.correspondence.sign_code_pair_attempts', 5))
            || RateLimiter::tooManyAttempts($keys[1], (int) config('ai.correspondence.sign_code_actor_attempts', 15))) {
            throw LetterRules::fail('signCode', 'too_many_attempts');
        }

        $code = is_string($code) ? $code : '';
        if ($code === '') {
            throw LetterRules::fail('signCode', 'wrong_code');
        }

        $strikes = ['keys' => $keys, 'decay' => max(1, (int) config('ai.correspondence.sign_code_minutes', 15)) * 60];

        $hash = strlen($code) > self::CODE_BYTES
            ? null
            : User::query()->active()->whereKey($targetId)->first(['id', 'extra'])?->getExtraValue('admin.sign_code_hash');

        if (is_string($hash) && $hash !== '') {
            $valid = Hash::check($code, $hash);
        } else {
            Hash::make(substr($code, 0, self::CODE_BYTES));
            $valid = false;
        }

        if (!$valid) {
            foreach ($keys as $key) {
                RateLimiter::hit($key, $strikes['decay']);
            }
            $strikes = [];

            throw LetterRules::fail('signCode', 'wrong_code');
        }

        $strikes = [];
        RateLimiter::clear($keys[0]);
    }
}
