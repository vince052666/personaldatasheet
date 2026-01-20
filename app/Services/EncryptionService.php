<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptionService
{
    private const ENCRYPTED_FIELDS = [
        'tin',
        'sss_no',
        'pagibig_no',
        'philhealth_no',
        'residential_address',
        'permanent_address',
    ];

    public function encrypt($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            \Log::error('Encryption failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to encrypt sensitive data. Data not stored.', 0, $e);
        }
    }

    public function decrypt($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            \Log::error('Decryption failed: ' . $e->getMessage());
            return $value; // Return as-is if decryption fails (might be unencrypted)
        }
    }

    public function encryptArray(array $data, array $fields = null): array
    {
        $fields = $fields ?? self::ENCRYPTED_FIELDS;
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    public function decryptArray(array $data, array $fields = null): array
    {
        $fields = $fields ?? self::ENCRYPTED_FIELDS;
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }

        return $data;
    }

    public function isEncrypted($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }

    public function rotateKey(string $oldKey, string $newKey, \Illuminate\Database\Eloquent\Model $model, array $fields): void
    {
        foreach ($fields as $field) {
            $value = $model->$field;
            
            if ($value) {
                // Decrypt with old key
                config(['app.key' => $oldKey]);
                $decrypted = $this->decrypt($value);
                
                // Re-encrypt with new key
                config(['app.key' => $newKey]);
                $model->$field = $this->encrypt($decrypted);
            }
        }

        $model->save();
    }
}
