<?php

namespace Tests\Feature\Web;

use App\Models\Agency;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PDSControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->user->assignRole('hr_encoder');
    }

    public function test_index_displays_pds_list()
    {
        PersonalDataSheet::factory()->count(5)->create(['agency_id' => $this->agency->id]);
        
        $response = $this->actingAs($this->user)->get(route('pds.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('pds.index');
        $response->assertViewHas('pdsList');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get(route('pds.create'));
        
        $response->assertStatus(200);
        $response->assertViewIs('pds.create');
    }

    public function test_store_creates_new_pds()
    {
        $data = [
            'surname' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'name_extension' => '',
            'date_of_birth' => '1990-01-15',
            'place_of_birth' => 'Manila',
            'sex' => 'Male',
            'civil_status' => 'Single',
            'height' => '170',
            'weight' => '70',
            'blood_type' => 'O',
            'citizenship' => 'Filipino',
            'residential_address' => '123 Main St, Manila',
            'permanent_address' => '123 Main St, Manila',
            'telephone_no' => '123-4567',
            'mobile_no' => '09171234567',
            'email_address' => 'juan.delacruz@example.com',
        ];

        $response = $this->actingAs($this->user)->post(route('pds.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('personal_data_sheets', [
            'surname' => 'Dela Cruz',
            'first_name' => 'Juan',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_show_displays_pds_details()
    {
        $pds = PersonalDataSheet::factory()->create(['agency_id' => $this->agency->id]);
        
        $response = $this->actingAs($this->user)->get(route('pds.show', $pds));
        
        $response->assertStatus(200);
        $response->assertViewIs('pds.show');
        $response->assertViewHas('pds', $pds);
    }

    public function test_edit_displays_form()
    {
        $pds = PersonalDataSheet::factory()->create(['agency_id' => $this->agency->id]);
        
        $response = $this->actingAs($this->user)->get(route('pds.edit', $pds));
        
        $response->assertStatus(200);
        $response->assertViewIs('pds.edit');
    }

    public function test_update_modifies_existing_pds()
    {
        $pds = PersonalDataSheet::factory()->create(['agency_id' => $this->agency->id]);
        
        $data = ['surname' => 'Updated Surname'];
        
        $response = $this->actingAs($this->user)->put(route('pds.update', $pds), $data);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('personal_data_sheets', [
            'id' => $pds->id,
            'surname' => 'Updated Surname',
        ]);
    }

    public function test_destroy_deletes_pds()
    {
        $pds = PersonalDataSheet::factory()->create(['agency_id' => $this->agency->id]);
        
        $response = $this->actingAs($this->user)->delete(route('pds.destroy', $pds));
        
        $response->assertRedirect();
        $this->assertSoftDeleted('personal_data_sheets', ['id' => $pds->id]);
    }

    public function test_cannot_access_other_agency_pds()
    {
        $otherAgency = Agency::factory()->create();
        $otherPds = PersonalDataSheet::factory()->create(['agency_id' => $otherAgency->id]);
        
        $response = $this->actingAs($this->user)->get(route('pds.show', $otherPds));
        
        $response->assertStatus(403);
    }

    public function test_validation_fails_with_invalid_data()
    {
        $data = [
            'surname' => '',
            'email_address' => 'invalid-email',
        ];

        $response = $this->actingAs($this->user)->post(route('pds.store'), $data);

        $response->assertSessionHasErrors(['surname', 'email_address']);
    }
}
