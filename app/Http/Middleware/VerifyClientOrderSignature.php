<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies X-Signature is HMAC-SHA256 of the raw request body under the
 * partner's own secret, before anything in the request is trusted. Runs
 * ahead of route-model resolution and validation on purpose: an
 * unauthenticated payload doesn't get to influence either.
 */
class VerifyClientOrderSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $partner = (string) $request->route('partner');
        $config = config("client_order_partners.{$partner}");

        if ($config === null) {
            return response()->json(['message' => 'Unknown partner.'], Response::HTTP_NOT_FOUND);
        }

        $signature = $request->header('X-Signature');

        if (! is_string($signature) || $signature === '') {
            return response()->json(['message' => 'Missing signature.'], Response::HTTP_UNAUTHORIZED);
        }

        $expected = hash_hmac('sha256', $request->getContent(), (string) $config['secret']);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
