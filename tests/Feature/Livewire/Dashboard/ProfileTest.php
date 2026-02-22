<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Profile;
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

    public function test_profile_info_can_be_updated()
    {
        $user = User::factory()->create();

        // Profile created in mount

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('profileData.personnel_id', 'TEST1234')
            ->set('profileData.gender', 'male')
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
            ->test(Profile::class)
            ->set('customType', 'National ID')
            ->set('customFile', $file)
            ->call('uploadCustom')
            ->assertHasNoErrors();

        // Check if file exists in storage
        // The path is not deterministic due to hashName, but we can check if attachments array has it
        $attachments = $user->fresh()->profile->attachments;
        $this->assertCount(1, $attachments);
        $this->assertEquals('National ID', $attachments[0]['type']);
        Storage::disk('public')->assertExists($attachments[0]['path']);
    }

    public function test_duplicate_document_type_prevention()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->profile()->create([
            'attachments' => [
                ['type' => 'Existing Doc', 'path' => 'path/to/doc', 'name' => 'doc.pdf', 'uploaded_at' => now()]
            ]
        ]);

        $file = UploadedFile::fake()->create('new.pdf', 1000);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('customType', 'Existing Doc')
            ->set('customFile', $file)
            ->call('uploadCustom')
            ->assertHasErrors(['customType']);

        $this->assertCount(1, $user->fresh()->profile->attachments);
    }
}
