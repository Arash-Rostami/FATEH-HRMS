<?php

namespace App\Services\ProjectTask\Contracts;

use App\Models\Reply;

interface ActivityLogRenderer
{
    public function getIcon(Reply $reply): string;
    public function getLabel(): string;
    public function getBody(Reply $reply): string;
}
