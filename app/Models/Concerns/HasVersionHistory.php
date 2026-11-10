<?php

namespace App\Models\Concerns;

trait HasVersionHistory
{
    public function pushVersion(array $version): void
    {
        $all = [...($this->versions ?? []), $version];
        if (count($all) > self::maxVersions()) {
            $all = array_slice($all, -self::maxVersions());
            $all[0]['trimmed'] = true;
        }
        $this->versions = $all;
    }

    public function lastVersion(): array
    {
        return collect($this->versions ?? [])->last() ?: [];
    }

    public function lastSpecVersion(): ?array
    {
        foreach (array_reverse($this->versions ?? []) as $version) {
            if (isset($version['spec'])) {
                return $version;
            }
        }

        return null;
    }

    public static function maxVersions(): int
    {
        return (int) config('ai.' . static::quotaFeature() . '.max_versions');
    }
}
