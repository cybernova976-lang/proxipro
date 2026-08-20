<?php

namespace App\Http\Controllers;

use App\Notifications\PushTestNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', Rule::in(['aesgcm', 'aes128gcm'])],
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications activées sur cet appareil.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json([
            'success' => true,
            'message' => 'Notifications désactivées sur cet appareil.',
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        if (! $request->user()->pushSubscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Activez d’abord les notifications sur cet appareil.',
            ], 422);
        }

        $request->user()->notify(new PushTestNotification);

        return response()->json([
            'success' => true,
            'message' => 'Notification de test envoyée.',
        ]);
    }
}
