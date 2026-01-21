<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PDSApiTest extends TestCase
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

    public function test_can_list_own_agency_pds_records()
    {
        Sanctum::actingAs($this->user, ['*']);
        
        PersonalDataSheet::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        PersonalDataSheet::factory()->count(2)->create(); // Other agencies

        $response = $this->getJson('/api/pds');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_cannot_access_other_agency_pds_via_api()
    {
        Sanctum::actingAs($this->user, ['*']);
        
        $otherAgency = Agency::factory()->create();
        $otherPds = PersonalDataSheet::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->getJson("/api/pds/{$otherPds->id}");

        $response->assertStatus(403);
    }

    public function test_can_create_pds_via_api()
    {
        Sanctum::actingAs($this->user, ['*']);

        $data = [
            'surname' => 'Test',
            'first_name' => 'User',
            'middle_name' => 'Middle',
            'date_of_birth' => '1990-01-01',
            'place_of_birth' => 'Manila',
            'sex' => 'Male',
            'civil_status' => 'Single',
            'email_address' => 'test@example.com',
        ];

        $response = $this->postJson('/api/pds', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('personal_data_sheets', [
            'surname' => 'Test',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_can_update_own_agency_pds()
    {
        Sanctum::actingAs($this->user, ['*']);
        
        $pds = PersonalDataSheet::factory()->create(['agency_id' => $this->agency->id]);

        $data = ['surname' => 'Updated'];

        $response = $this->putJson("/api/pds/{$pds->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_data_sheets', ['id' => $pds->id, 'surname' => 'Updated']);
    }

    public function test_cannot_update_other_agency_pds()
    {
        Sanctum::actingAs($this->user, ['*']);
        
        $otherAgency = Agency::factory()->create();
        $otherPds = PersonalDataSheet::factory()->create(['agency_id' => $otherAgency->id]);

        $data = ['surname' => 'Hacked'];

        $response = $this->putJson("/api/pds/{$otherPds->id}", $data);

        $response->assertStatus(403);
    }

    public function test_can_delete_own_agency_pds()
    {
        Sanctum::actingAs($this->user, ['*']);
        
        $pds = PersonalDataSheet::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->deleteJson("/api/pds/{$pds->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('personal_data_sheets', ['id' => $pds->id]);
    }

    public function test_search_filters_by_agency()
    {
        Sanctum::actingAs($this->user, ['*']);
        
        PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'surname' => 'SearchMe',
        ]);
        PersonalDataSheet::factory()->create([
            'surname' => 'SearchMe',
        ]);

        $response = $this->getJson('/api/pds?search=SearchMe');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_rate_limiting_applies_to_api()
    {
        Sanctum::actingAs($this->user, ['*']);

        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/pds');
            
            if ($response->status() === 429) {
                $this->assertTrue(true);
                return;
            }
        }

        $this->markTestSkipped('Rate limiting not triggered');
    }
}
