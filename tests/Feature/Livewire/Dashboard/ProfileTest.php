<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Profile;
use App\Livewire\Dashboard\Tab\Profile\Info;
use App\Livewire\Dashboard\Tab\Profile\Documents;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_mounts_correctly()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->assertStatus(200);
    }

    public function test_info_component_update()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('profileData.personnel_id', 'TEST1234')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('TEST1234', $user->fresh()->profile->personnel_id);
    }

    public function test_document_upload()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('customType', 'National ID')
            ->set('customFile', $file)
            ->call('uploadCustom')
            ->assertHasNoErrors();

        $attachments = $user->fresh()->profile->attachments;
        $this->assertCount(1, $attachments);
    }
}
