<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\PersonalDataSheet;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $encoder;
    protected User $reviewer;
    protected User $approver;
    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        
        $this->agency = Agency::factory()->create();
        
        $this->encoder = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->encoder->assignRole('hr_encoder');
        
        $this->reviewer = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->reviewer->assignRole('hr_reviewer');
        
        $this->approver = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->approver->assignRole('agency_admin');
    }

    public function test_encoder_can_submit_for_review()
    {
        Sanctum::actingAs($this->encoder, ['*']);
        
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/pds/{$pds->id}/submit");

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_data_sheets', [
            'id' => $pds->id,
            'status' => 'pending_review',
        ]);
    }

    public function test_reviewer_can_approve_or_reject()
    {
        Sanctum::actingAs($this->reviewer, ['*']);
        
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending_review',
        ]);

        $response = $this->postJson("/api/pds/{$pds->id}/review", [
            'action' => 'approve',
            'comments' => 'Looks good',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_data_sheets', [
            'id' => $pds->id,
            'status' => 'pending_approval',
        ]);
    }

    public function test_reviewer_can_reject_with_comments()
    {
        Sanctum::actingAs($this->reviewer, ['*']);
        
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending_review',
        ]);

        $response = $this->postJson("/api/pds/{$pds->id}/review", [
            'action' => 'reject',
            'comments' => 'Missing required documents',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_data_sheets', [
            'id' => $pds->id,
            'status' => 'rejected',
        ]);
    }

    public function test_approver_can_final_approve()
    {
        Sanctum::actingAs($this->approver, ['*']);
        
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->postJson("/api/pds/{$pds->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('personal_data_sheets', [
            'id' => $pds->id,
            'status' => 'approved',
        ]);
    }

    public function test_cannot_approve_without_permission()
    {
        Sanctum::actingAs($this->encoder, ['*']);
        
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending_review',
        ]);

        $response = $this->postJson("/api/pds/{$pds->id}/approve");

        $response->assertStatus(403);
    }

    public function test_approval_creates_audit_trail()
    {
        Sanctum::actingAs($this->reviewer, ['*']);
        
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending_review',
        ]);

        $this->postJson("/api/pds/{$pds->id}/review", [
            'action' => 'approve',
            'comments' => 'Test approval',
        ]);

        $this->assertDatabaseHas('approval_histories', [
            'personal_data_sheet_id' => $pds->id,
            'user_id' => $this->reviewer->id,
            'action' => 'approve',
        ]);
    }

    public function test_cannot_approve_other_agency_pds()
    {
        Sanctum::actingAs($this->reviewer, ['*']);
        
        $otherAgency = Agency::factory()->create();
        $otherPds = PersonalDataSheet::factory()->create([
            'agency_id' => $otherAgency->id,
            'status' => 'pending_review',
        ]);

        $response = $this->postJson("/api/pds/{$otherPds->id}/review", [
            'action' => 'approve',
        ]);

        $response->assertStatus(403);
    }
}
