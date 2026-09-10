<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        abort_unless(
            $appSecret !== ''
                && str_starts_with($signature, 'sha256='),
            401,
        );

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $appSecret,
        );

        abort_unless(
            hash_equals($expectedSignature, $signature),
            401,
        );

        return $next($request);
    }
}