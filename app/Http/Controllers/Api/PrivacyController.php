<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataConsent;
use App\Models\DataSubjectRequest;
use App\Models\PersonalDataSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PrivacyController extends Controller
{
    public function giveConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|in:data_processing,data_sharing,privacy_policy',
            'version' => 'nullable|string',
        ]);

        $consent = DataConsent::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'consent_type' => $validated['consent_type'],
            ],
            [
                'consented' => true,
                'consented_at' => now(),
                'withdrawn_at' => null,
                'ip_address' => $request->ip(),
                'version' => $validated['version'] ?? '1.0',
            ]
        );

        return response()->json([
            'message' => 'Consent recorded successfully',
            'data' => $consent,
        ]);
    }

    public function withdrawConsent(string $type): JsonResponse
    {
        $consent = DataConsent::where('user_id', auth()->id())
            ->where('consent_type', $type)
            ->firstOrFail();

        $consent->withdraw();

        return response()->json([
            'message' => 'Consent withdrawn successfully',
        ]);
    }

    public function submitRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_type' => 'required|in:access,rectification,erasure,portability',
            'description' => 'nullable|string|max:1000',
        ]);

        $dataRequest = DataSubjectRequest::create([
            'user_id' => auth()->id(),
            'request_type' => $validated['request_type'],
            'description' => $validated['description'] ?? null,
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Data subject request submitted successfully',
            'data' => $dataRequest,
        ], 201);
    }

    public function downloadMyData(): JsonResponse
    {
        $user = auth()->user();
        
        // Gather all user data
        $data = [
            'user' => $user->toArray(),
            'personal_data_sheets' => PersonalDataSheet::where('user_id', $user->id)->get()->toArray(),
            'consents' => DataConsent::where('user_id', $user->id)->get()->toArray(),
            'requests' => DataSubjectRequest::where('user_id', $user->id)->get()->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = "user-data-{$user->id}-" . now()->format('Y-m-d-His') . '.json';
        $path = "exports/{$filename}";

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));

        return response()->json([
            'message' => 'Your data has been prepared for download',
            'download_url' => Storage::url($path),
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ]);
    }
}
