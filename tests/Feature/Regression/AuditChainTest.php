<?php

namespace Tests\Feature\Regression;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditChainTest extends TestCase
{
    use RefreshDatabase;

    protected Agency $agency;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_audit_log_created_on_create()
    {
        $this->actingAs($this->user);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PersonalDataSheet::class,
            'auditable_id' => $pds->id,
            'event' => 'created',
        ]);
    }

    public function test_audit_log_created_on_update()
    {
        $this->actingAs($this->user);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $pds->update(['surname' => 'Updated']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PersonalDataSheet::class,
            'auditable_id' => $pds->id,
            'event' => 'updated',
        ]);
    }

    public function test_audit_chain_is_immutable()
    {
        $this->actingAs($this->user);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $auditLog = AuditLog::where('auditable_id', $pds->id)->first();

        $this->expectException(\Exception::class);
        $auditLog->update(['event' => 'tampered']);
    }

    public function test_audit_chain_includes_user_info()
    {
        $this->actingAs($this->user);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $pds->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_audit_chain_verification()
    {
        $this->actingAs($this->user);

        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $pds->update(['surname' => 'V1']);
        $pds->update(['surname' => 'V2']);

        $logs = AuditLog::where('auditable_id', $pds->id)
            ->orderBy('created_at')
            ->get();

        $this->assertGreaterThanOrEqual(3, $logs->count());
    }
}
