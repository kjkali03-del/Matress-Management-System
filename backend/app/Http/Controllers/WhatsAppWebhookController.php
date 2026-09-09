<?php

namespace App\Http\Controllers;

use App\Services\IncomingWhatsAppMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly IncomingWhatsAppMessageService $messageService) {}

    public function verify(Request $request): Response
    {
        $validated = Validator::make([
            'mode' => $request->query('hub.mode', $request->query('hub_mode')),
            'verify_token' => $request->query('hub.verify_token', $request->query('hub_verify_token')),
            'challenge' => $request->query('hub.challenge', $request->query('hub_challenge')),
        ], [
            'mode' => ['required', 'string'],
            'verify_token' => ['required', 'string'],
            'challenge' => ['required', 'string'],
        ])->validate();

        abort_unless(
            $validated['mode'] === 'subscribe'
                && hash_equals((string) config('services.whatsapp.verify_token'), $validated['verify_token']),
            403,
        );

        return response($validated['challenge']);
    }

    public function receive(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'object' => ['required', 'string', 'in:whatsapp_business_account'],
            'entry' => ['required', 'array'],
        ]);

        $result = $this->messageService->process($payload);

        Log::info('WhatsApp webhook processed.', $result);

        return response()->json(['received' => true]);
    }
}
