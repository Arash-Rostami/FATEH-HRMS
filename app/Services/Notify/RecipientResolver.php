<?php

namespace App\Services\Notify;

use App\Models\NotifyRule;
use App\Models\User;

final class RecipientResolver
{
    public function resolve(NotifyRule $rule): array
    {
        return $this->resolveWithSkips($rule)[0];
    }

    public function resolveWithSkips(NotifyRule $rule): array
    {
        $channels = [];

        foreach ($rule->channels ?? [] as $key) {
            if ($channel = NotifyRegistry::channel($key)) {
                $channels[$key] = $channel;
            }
        }

        $picked = array_map('intval', $rule->user_ids ?? []);
        $resolved = array_fill_keys(array_keys($channels), []);
        $skipped = $resolved;
        $seen = [];

        foreach (array_chunk($this->audienceIds($rule, $picked), (int) config('notify.resolve_chunk', 500)) as $chunk) {
            $users = User::active()
                ->whereIn('id', $chunk)
                ->orderBy('id')
                ->when(isset($channels['sms']), fn ($q) => $q->with('profile:id,user_id,cellphone'))
                ->get(['id', 'name', 'email']);

            foreach ($users as $user) {
                $seen[$user->id] = true;

                foreach ($channels as $key => $channel) {
                    $address = $channel->addressFor($user);

                    if (! $address) {
                        $skipped[$key][] = $this->skip($user->id, $user->name, __('resources/notify_rule/strings.batch.skip_missing', ['channel' => $channel->label()]));
                    } elseif (! $channel->accepts($address)) {
                        $skipped[$key][] = $this->skip($user->id, $user->name, __('resources/notify_rule/strings.batch.skip_invalid', ['channel' => $channel->label()]));
                    } else {
                        $normalized = $channel->normalize($address);

                        if (isset($resolved[$key][$normalized]) && $resolved[$key][$normalized] !== $user->id) {
                            $skipped[$key][] = $this->skip($user->id, $user->name, __('resources/notify_rule/strings.batch.skip_duplicate', ['channel' => $channel->label()]));
                        } else {
                            $resolved[$key][$normalized] ??= $user->id;
                        }
                    }
                }
            }
        }

        $missing = array_diff($picked, array_keys($seen));

        if ($missing !== []) {
            $names = User::getCachedAllOptions();

            foreach ($missing as $id) {
                foreach (array_keys($channels) as $key) {
                    $skipped[$key][] = $this->skip($id, (string) $names->get($id, "#{$id}"), __('resources/notify_rule/strings.batch.skip_inactive'));
                }
            }
        }

        $contacts = array_filter($rule->extra['contacts'] ?? [], 'is_string');

        foreach ($channels as $key => $channel) {
            foreach ($contacts as $contact) {
                if ($channel->accepts($contact)) {
                    $resolved[$key][$channel->normalize($contact)] ??= null;
                }
            }
        }

        return [$resolved, $skipped];
    }

    private function audienceIds(NotifyRule $rule, array $picked): array
    {
        $ids = collect($picked);

        switch ($rule->extra['audience'] ?? 'users') {
            case 'all':
                $ids = $ids->merge(User::visibleOnBoard()->pluck('id'));
                break;
            case 'departments':
                foreach ($rule->extra['departments'] ?? [] as $code) {
                    $ids = $ids->merge(User::getCachedDepartmentOptions((string) $code)->keys());
                }
                break;
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function skip(int $userId, string $name, string $reason): array
    {
        return ['user_id' => $userId, 'name' => $name, 'reason' => $reason];
    }
}
