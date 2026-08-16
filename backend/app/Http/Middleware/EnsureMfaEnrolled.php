<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->mfa_required && $user->two_factor_confirmed_at === null) {
            return new JsonResponse([
                'message' => 'Multi-factor authentication enrollment is required.',
                'code' => 'mfa_enrollment_required',
            ], 428);
        }

        return $next($request);
    }
}
