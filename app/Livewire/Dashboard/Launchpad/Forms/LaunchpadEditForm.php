<?php

namespace App\Livewire\Dashboard\Launchpad\Forms;

use App\Models\Launchpad;
use Illuminate\Support\Arr;

class LaunchpadEditForm extends LaunchpadForm
{
    public string $storedAuth = '';

    public array $storedDates = [];

    public function rules(): array
    {
        $rules = Arr::except(parent::rules(), ['title', 'department_id', 'kind', 'site', 'path', 'files', 'files.*']);

        foreach (['accessible_to', 'expire_at'] as $field) {
            if ($this->{$field} !== null && $this->{$field} === ($this->storedDates[$field] ?? null)) {
                $rules[$field] = array_values(array_diff($rules[$field], ['after_or_equal:today']));
            }
        }

        if ($this->storedAuth !== '' && $this->storedAuth === $this->sourceAuth) {
            foreach ($this->sourceFields() as $field => $spec) {
                if ($spec['secret'] && isset($rules["sourceEnv.{$field}"])) {
                    $rules["sourceEnv.{$field}"] = array_map(fn ($rule) => $rule === 'required' ? 'nullable' : $rule, $rules["sourceEnv.{$field}"]);
                }
            }
        }

        return $rules;
    }

    public function load(Launchpad $row): void
    {
        $stored = (array) $row->source;
        $this->title = (string) $row->title;
        $this->department_id = (string) $row->department_id;
        $this->kind = (string) $row->kind;
        $this->description = (string) $row->description;
        $this->audience = array_map('intval', (array) $row->audience);
        $this->public = $row->isPublic();
        $this->accessible_from = $row->accessible_from?->toDateString();
        $this->accessible_to = $row->accessible_to?->toDateString();
        $this->expire_at = $row->expire_at?->toDateString();
        $this->storedAuth = $this->kind === 'static' ? (string) ($stored['auth'] ?? '') : '';
        $this->sourceAuth = $this->storedAuth !== '' ? $this->storedAuth : 'off';
        $this->sourceUrl = (string) ($stored['url'] ?? '');
        $this->sourceMethod = (string) ($stored['method'] ?? 'GET');
        $this->sourceBody = (string) ($stored['body'] ?? '');
        $this->sourceEnv = [];

        foreach (self::authMatrix()[$this->storedAuth] ?? [] as $field => $spec) {
            if (!$spec['secret']) {
                $this->sourceEnv[$field] = (string) ($stored['env'][$field] ?? '');
            }
        }
    }
}
