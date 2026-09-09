<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLaravelWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('services.whatsapp.webhook_secret');
        $providedSecret = (string) $request->bearerToken();

        abort_unless(
            $expectedSecret !== ''
                && $providedSecret !== ''
                && hash_equals($expectedSecret, $providedSecret),
            401,
        );

        return $next($request);
    }
}