<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Main;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MainTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dashboard_main_component_renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertStatus(200)
            ->assertSee('Overview'); // Default tab label
    }

    /** @test */
    public function can_switch_tabs()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setTab', 'calendar')
            ->assertSet('activeTab', 'calendar')
            ->call('setTab', 'gallery')
            ->assertSet('activeTab', 'gallery');
    }
}
