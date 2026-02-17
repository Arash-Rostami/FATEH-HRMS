<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Posts extends Component
{
    #[Locked]
    public array $postIds = [];

    public int $perPage = 3;
    public bool $hasMorePages = true;

    public $selectedPost = null;

    protected $listeners = ['select-post' => 'selectPost'];

    public function mount()
    {
        $this->loadInitialPosts();
    }

    public function loadInitialPosts()
    {
        $posts = Post::where('pinned', '<>', 1)
            ->latest()
            ->take($this->perPage)
            ->get();

        $this->postIds = $posts->pluck('id')->toArray();
        $this->hasMorePages = $posts->count() >= $this->perPage;

        unset($this->posts);
    }

    public function loadMore()
    {
        if (!$this->hasMorePages) return;

        $newIds = Post::where('pinned', '<>', 1)
            ->latest()
            ->skip(count($this->postIds))
            ->take($this->perPage)
            ->pluck('id')
            ->toArray();

        if (empty($newIds)) {
            $this->hasMorePages = false;
            return;
        }

        $this->postIds = array_merge($this->postIds, $newIds);
        unset($this->posts);
    }

    #[Computed(persist: true, seconds: 7200)]
    public function pins()
    {
        return Post::where('pinned', 1)
            ->latest()
            ->take(1)
            ->get();
    }

    #[Computed]
    public function posts()
    {
        if (empty($this->postIds)) return collect();

        $idsString = implode(',', $this->postIds);

        $query = Post::whereIn('id', $this->postIds);

        if (DB::connection()->getDriverName() === 'sqlite') {
             return $query->get()->sortBy(function($model) {
                return array_search($model->id, $this->postIds);
            });
        }

        return $query->orderByRaw("FIELD(id, $idsString)")->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.posts');
    }

    public function selectPost($id)
    {
        $this->selectedPost = Cache::remember('dashboard.posts.item.' . $id, 3600, function () use ($id) {
            return Post::find($id);
        });
        $this->dispatch('open-post-panel');
    }
}
