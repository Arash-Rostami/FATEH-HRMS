<?php

namespace App\Filament\Resources\SkillResource\Pages;

use App\Filament\Resources\SkillResource;
use App\Models\Skill;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSkill extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = SkillResource::class;

    protected function handleRecordCreation(array $data): Skill
    {
        return DB::transaction(function () use ($data) {
            $skill = Skill::matchingName($data['name'])->lockForUpdate()->first();

            if ($skill) {
                return $this->resolveCollision($skill, $data);
            }

            try {
                $data['is_ghost'] = false;

                return Skill::create($data);
            } catch (QueryException $e) {
                if (!Str::contains($e->getMessage(), ['Duplicate', '1062'])) {
                    throw $e;
                }

                $skill = Skill::matchingName($data['name'])->lockForUpdate()->first();

                if (!$skill) {
                    throw $e;
                }

                return $this->resolveCollision($skill, $data);
            }
        });
    }

    private function resolveCollision(Skill $skill, array $data): Skill
    {
        if ($skill->is_active) {
            throw ValidationException::withMessages([
                'name' => __('resources/skill/strings.errors.already_active'),
            ]);
        }

        return $this->promoteExisting($skill, $data);
    }

    private function promoteExisting(Skill $skill, array $data): Skill
    {
        $skill->fill(Arr::only($data, ['name_en', 'category', 'description', 'icon']));
        $skill->is_active = true;
        $skill->is_ghost = false;
        $skill->save();

        return $skill;
    }
}