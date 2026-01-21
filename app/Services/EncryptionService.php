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
            // Throw exception instead of returning potentially encrypted data
            throw new \RuntimeException('Failed to decrypt sensitive field. Data may be corrupted.');
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

    /**
     * Rotate encryption key for a model's encrypted fields
     * NOTE: This should be used with caution and requires temporary configuration
     * Use Laravel's proper key rotation process instead for production
     */
    public function rotateKey(string $oldKey, string $newKey, \Illuminate\Database\Eloquent\Model $model, array $fields): void
    {
        // Create temporary encryptors with specific keys
        $oldCrypt = new \Illuminate\Encryption\Encrypter($oldKey, config('app.cipher'));
        $newCrypt = new \Illuminate\Encryption\Encrypter($newKey, config('app.cipher'));
        
        foreach ($fields as $field) {
            $value = $model->$field;
            
            if ($value) {
                try {
                    // Decrypt with old key
                    $decrypted = $oldCrypt->decryptString($value);
                    
                    // Re-encrypt with new key
                    $model->$field = $newCrypt->encryptString($decrypted);
                } catch (DecryptException $e) {
                    \Log::error("Failed to rotate key for field {$field}: " . $e->getMessage());
                    throw new \RuntimeException("Key rotation failed for field {$field}");
                }
            }
        }

        $model->save();
    }
}
