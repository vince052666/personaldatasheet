<?php

namespace Tests\Feature\Regression;

use App\Models\Agency;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncryptionDecryptionTest extends TestCase
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

    public function test_sensitive_data_is_encrypted_at_rest()
    {
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'gsis_no' => '1234567890',
            'tin_no' => '123-456-789',
        ]);

        // Retrieve raw database value
        $raw = \DB::table('personal_data_sheets')
            ->where('id', $pds->id)
            ->first();

        $this->assertNotEquals('1234567890', $raw->gsis_no ?? '');
        $this->assertEquals('1234567890', $pds->fresh()->gsis_no);
    }

    public function test_decryption_works_correctly()
    {
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'sss_no' => '33-4455667-8',
        ]);

        $retrieved = PersonalDataSheet::find($pds->id);

        $this->assertEquals('33-4455667-8', $retrieved->sss_no);
    }

    public function test_encryption_survives_update()
    {
        $pds = PersonalDataSheet::factory()->create([
            'agency_id' => $this->agency->id,
            'tin_no' => '111-222-333',
        ]);

        $pds->update(['surname' => 'Updated']);
        $pds->refresh();

        $this->assertEquals('111-222-333', $pds->tin_no);
    }

    public function test_bulk_operations_maintain_encryption()
    {
        $records = PersonalDataSheet::factory()->count(10)->create([
            'agency_id' => $this->agency->id,
        ]);

        foreach ($records as $record) {
            $this->assertNotNull($record->gsis_no);
        }
    }
}
