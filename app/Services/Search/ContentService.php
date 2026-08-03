<?php

namespace App\Services\Search;

use App\Services\Search\Contracts\SearchContext;
use App\Services\Search\Resources\AdResource;
use App\Services\Search\Resources\ChannelMessageResource;
use App\Services\Search\Resources\ChannelResource;
use App\Services\Search\Resources\CredentialResource;
use App\Services\Search\Resources\DmsResource;
use App\Services\Search\Resources\EventResource;
use App\Services\Search\Resources\FaqResource;
use App\Services\Search\Resources\FeedResource;
use App\Services\Search\Resources\GalleryResource;
use App\Services\Search\Resources\LinkResource;
use App\Services\Search\Resources\MessageResource;
use App\Services\Search\Resources\OnboardingResource;
use App\Services\Search\Resources\PeopleResource;
use App\Services\Search\Resources\PostResource;
use App\Services\Search\Resources\ReportResource;
use App\Services\Search\Resources\ReservationResource;
use App\Services\Search\Resources\SkillSearchResource;
use App\Services\Search\Resources\SuggestionResource;
use App\Services\Search\Resources\TaskResource;
use App\Services\Search\Resources\TicketResource;


class ContentService
{
    /**
     *
     * @var array<int, class-string<\App\Services\Search\Contracts\Searchable>>
     */
    protected array $resources = [
        // ===== TAB modules (/dashboard?tab=...) =====
        PostResource::class,
        FeedResource::class,
        EventResource::class,
        ReportResource::class,
        GalleryResource::class,
        FaqResource::class,
        LinkResource::class,

        // ===== PAGE modules (own route ?open=...) =====
        DmsResource::class,
        TicketResource::class,
        TaskResource::class,
        SuggestionResource::class,
        AdResource::class,
        ReservationResource::class,
        ChannelResource::class,
        ChannelMessageResource::class,

        // ===== Users & profile-area modules =====
        PeopleResource::class,
        CredentialResource::class,
        OnboardingResource::class,
        MessageResource::class,
        SkillSearchResource::class,
    ];

    public function search(string $query): array
    {
        $context = SearchContext::for($query);

        if (!$context) return [];


        return collect($this->resources)
            ->map(fn(string $resource) => app($resource)->search($context))
            ->filter(fn(array $group) => $group !== [])
            ->sortByDesc('score')
            ->flatMap(fn(array $group) => $group['items'])
            ->values()
            ->toArray();
    }
}
