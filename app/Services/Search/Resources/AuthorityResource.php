<?php

namespace App\Services\Search\Resources;

use App\Models\Authority;
use App\Services\Search\Contracts\SearchResource;

class AuthorityResource extends SearchResource
{
    protected string $type = 'authority';
    protected string $group = 'اختیارات';
    protected string $icon = 'verified_user';
    protected string $model = Authority::class;
    protected array $columns = ['sub_duty', 'details'];
    protected ?string $titleField = 'sub_duty';
    protected ?string $subtitleField = 'details';

    public function action($row): string
    {
        return $this->route('authority', $row->getKey());
    }
}
