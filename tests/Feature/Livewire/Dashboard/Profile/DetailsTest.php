<?php

namespace Tests\Feature\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Forms\DetailsForm;
use App\Livewire\Dashboard\Profile\Actions\SaveDetailsAction;
use App\Livewire\Dashboard\Profile\Details;
use App\Models\Profile;
use App\Models\ProfileDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Livewire\Livewire;
use Tests\TestCase;

class DetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure tables exist for our test
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->integer('maximum')->default(1);
                $table->string('type')->default('employee');
                $table->string('role')->default('user');
                $table->string('status')->default('active');
                $table->string('presence')->default('onsite');
                $table->json('booking')->nullable();
                $table->timestamp('last_seen')->nullable();
                $table->json('extra')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('department_id')->nullable();
                $table->string('personnel_code')->nullable();
                $table->string('personnel_id')->nullable();
                $table->string('position')->nullable();
                $table->string('phone')->nullable();
                $table->string('cellphone')->nullable();
                $table->string('image')->nullable();
                $table->text('bio')->nullable();
                $table->date('birthday')->nullable();

                $table->json('attachments')->nullable();
                $table->string('gender')->nullable();
                $table->string('employment_type')->nullable();
                $table->string('marital_status')->nullable();
                $table->integer('number_of_children')->default(0);
                $table->string('employment_status')->nullable();
                $table->date('birthdate')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('profile_details')) {
            Schema::create('profile_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
                $table->string('section', 64)->index();
                $table->string('key', 191)->index();
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['profile_id', 'key']);
            });
        }
    }

    public function test_component_mounts_correctly()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        ProfileDetail::create([
            'profile_id' => $profile->id,
            'key' => 'height',
            'value' => '180',
            'section' => 'health'
        ]);

        ProfileDetail::create([
            'profile_id' => $profile->id,
            'key' => 'marriage_date',
            'value' => '1399/05/12',
            'section' => 'family'
        ]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->assertSet('hasProfile', true)
            ->assertSet('form.values.height', '180')
            ->assertSet('form.values.marriage_date', '1399/05/12')
            ->assertSet('form.values.marriage_dateYear', 1399)
            ->assertSet('form.values.marriage_dateMonth', 5)
            ->assertSet('form.values.marriage_dateDay', 12);
    }

    public function test_component_validation_fails_for_invalid_data()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.height', 'too_tall') // should be integer
            ->set('form.values.blood_type', 'Z+') // invalid option
            ->call('save')
            ->assertHasErrors(['form.values.height', 'form.values.blood_type']);
    }

    public function test_component_saves_valid_data()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.height', '185')
            ->set('form.values.blood_type', 'A+')
            ->set('form.values.marriage_dateYear', '1400')
            ->set('form.values.marriage_dateMonth', '6')
            ->set('form.values.marriage_dateDay', '15')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'height',
            'value' => '185',
        ]);

        $this->assertDatabaseHas('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'marriage_date',
            'value' => '1400/06/15',
        ]);
    }

    public function test_cannot_save_without_profile()
    {
        $user = User::factory()->create();
        // No profile created

        Livewire::actingAs($user)
            ->test(Details::class)
            ->assertSet('hasProfile', false)
            ->set('form.values.height', '185')
            ->call('save')
            ->assertDispatched('toast', type: 'error', message: 'ابتدا «اطلاعات فردی» را تکمیل و ذخیره کنید.');

        $this->assertDatabaseMissing('profile_details', [
            'key' => 'height',
        ]);
    }

    public function test_can_save_details_action()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $form = new class extends DetailsForm {
            public function __construct() {}
            public function validate($rules = null, $messages = [], $attributes = []) { return true; }
            public function getPropertyName() { return 'form'; }
        };

        $form->values = [
            'height' => '180',
            'weight' => '75',
            'marriage_dateYear' => '1399',
            'marriage_dateMonth' => '5',
            'marriage_dateDay' => '12',
            'blood_type' => 'O+',
        ];

        $action = new SaveDetailsAction();
        $action->execute($form);

        $this->assertDatabaseHas('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'height',
            'value' => '180',
        ]);

        $this->assertDatabaseHas('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'marriage_date',
            'value' => '1399/05/12',
        ]);
    }

    public function test_can_update_existing_details_action()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        ProfileDetail::create([
            'profile_id' => $profile->id,
            'key' => 'height',
            'value' => '175',
            'section' => 'health'
        ]);

        $form = new class extends DetailsForm {
            public function __construct() {}
            public function validate($rules = null, $messages = [], $attributes = []) { return true; }
            public function getPropertyName() { return 'form'; }
        };

        $form->values = [
            'height' => '180',
            'weight' => '75',
        ];

        $action = new SaveDetailsAction();
        $action->execute($form);

        $this->assertDatabaseHas('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'height',
            'value' => '180',
        ]);
        $this->assertEquals(2, ProfileDetail::where('profile_id', $profile->id)->count());
    }

    public function test_can_remove_details_when_empty_action()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        ProfileDetail::create([
            'profile_id' => $profile->id,
            'key' => 'height',
            'value' => '175',
            'section' => 'health'
        ]);

        $form = new class extends DetailsForm {
            public function __construct() {}
            public function validate($rules = null, $messages = [], $attributes = []) { return true; }
            public function getPropertyName() { return 'form'; }
        };

        $form->values = [
            'height' => '',
            'weight' => '75',
        ];

        $action = new SaveDetailsAction();
        $action->execute($form);

        $this->assertDatabaseMissing('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'height',
        ]);
    }

    public function test_invalid_date_formatting_action()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $form = new class extends DetailsForm {
            public function __construct() {}
            public function validate($rules = null, $messages = [], $attributes = []) { return true; }
            public function getPropertyName() { return 'form'; }
        };

        // Missing month
        $form->values = [
            'marriage_dateYear' => '1399',
            'marriage_dateDay' => '12',
        ];

        $action = new SaveDetailsAction();
        $action->execute($form);

        $this->assertDatabaseMissing('profile_details', [
            'profile_id' => $profile->id,
            'key' => 'marriage_date',
        ]);
    }
}
