<?php

namespace App\Services\Search\Resources;

use App\Models\Credential;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class CredentialResource extends SearchResource
{
    protected string $type = 'credential';
    protected string $group = 'دسترسی‌های سازمانی';
    protected string $icon = 'vpn_key';
    protected string $model = Credential::class;
    protected array $columns = ['app_name', 'username', 'note', 'link'];
    protected ?string $titleField = 'app_name';
    protected ?string $subtitleField = 'username';

    public function action($row): string
    {
        return $this->url('/profile?tab=credentials&open=' . $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->where('user_id', $this->me());
    }
}
