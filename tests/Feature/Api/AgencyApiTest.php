<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgencyApiTest extends TestCase
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
        $this->user->assignRole('agency_admin');
    }

    public function test_can_list_agencies_with_authentication()
    {
        Sanctum::actingAs($this->user, ['*']);
        Agency::factory()->count(3)->create();

        $response = $this->getJson('/api/agencies');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'created_at']
            ]
        ]);
    }

    public function test_cannot_list_agencies_without_authentication()
    {
        $response = $this->getJson('/api/agencies');

        $response->assertStatus(401);
    }

    public function test_can_create_agency_with_permission()
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->user->givePermissionTo('manage-agencies');

        $data = [
            'name' => 'Department of Test',
            'code' => 'DOT',
            'address' => '123 Test Street',
            'contact_number' => '123-4567',
        ];

        $response = $this->postJson('/api/agencies', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('agencies', ['name' => 'Department of Test']);
    }

    public function test_cannot_create_agency_without_permission()
    {
        Sanctum::actingAs($this->user, ['*']);

        $data = ['name' => 'Test Agency', 'code' => 'TA'];

        $response = $this->postJson('/api/agencies', $data);

        $response->assertStatus(403);
    }

    public function test_can_show_own_agency()
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson("/api/agencies/{$this->agency->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => $this->agency->name]);
    }

    public function test_can_update_own_agency()
    {
        Sanctum::actingAs($this->user, ['*']);

        $data = ['name' => 'Updated Agency Name'];

        $response = $this->putJson("/api/agencies/{$this->agency->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('agencies', ['id' => $this->agency->id, 'name' => 'Updated Agency Name']);
    }

    public function test_validation_fails_for_duplicate_code()
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->user->givePermissionTo('manage-agencies');

        Agency::factory()->create(['code' => 'DUPLICATE']);

        $data = ['name' => 'New Agency', 'code' => 'DUPLICATE'];

        $response = $this->postJson('/api/agencies', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('code');
    }

    public function test_can_delete_agency()
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->user->givePermissionTo('manage-agencies');

        $agency = Agency::factory()->create();

        $response = $this->deleteJson("/api/agencies/{$agency->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('agencies', ['id' => $agency->id]);
    }
}
