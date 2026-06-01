<?php

namespace App\Services\Search\Contracts;

interface Searchable
{
    /**
     *
     * @return array{type:string,group:string,icon:string,score:int,items:array<int,array>}|array{}
     */
    public function search(SearchContext $context): array;
}
