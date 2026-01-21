<?php

namespace Tests\Feature\Regression;

use App\Models\Agency;
use App\Models\PersonalDataSheet;
use App\Models\RecordLock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordLockingTest extends TestCase
{
    use RefreshDatabase;

    protected Agency $agency;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->agency = Agency::factory()->create();
        $this->user1 = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->user2 = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_record_gets_locked_on_edit()
    {
        $this->actingAs($this->user1);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->get(route('pds.edit', $pds));

        $this->assertDatabaseHas('record_locks', [
            'lockable_type' => PersonalDataSheet::class,
            'lockable_id' => $pds->id,
            'user_id' => $this->user1->id,
        ]);
    }

    public function test_second_user_cannot_edit_locked_record()
    {
        RecordLock::create([
            'lockable_type' => PersonalDataSheet::class,
            'lockable_id' => 1,
            'user_id' => $this->user1->id,
            'locked_at' => now(),
        ]);

        $this->actingAs($this->user2);

        $pds = PersonalDataSheet::factory()->create([
            'id' => 1,
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->get(route('pds.edit', $pds));

        $response->assertStatus(423); // Locked
    }

    public function test_lock_expires_after_timeout()
    {
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        RecordLock::create([
            'lockable_type' => PersonalDataSheet::class,
            'lockable_id' => $pds->id,
            'user_id' => $this->user1->id,
            'locked_at' => now()->subMinutes(20),
        ]);

        $this->actingAs($this->user2);

        $response = $this->get(route('pds.edit', $pds));

        $response->assertStatus(200);
    }

    public function test_user_can_release_own_lock()
    {
        $this->actingAs($this->user1);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        RecordLock::create([
            'lockable_type' => PersonalDataSheet::class,
            'lockable_id' => $pds->id,
            'user_id' => $this->user1->id,
            'locked_at' => now(),
        ]);

        $response = $this->delete("/api/locks/{$pds->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('record_locks', [
            'lockable_id' => $pds->id,
        ]);
    }
}
