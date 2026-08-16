<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePrivilegedReason
{
    public function handle(Request $request, Closure $next): Response
    {
        $reason = trim((string) $request->header('X-Privileged-Reason'));

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            return new JsonResponse([
                'message' => 'A privileged-action reason of 10 to 500 characters is required.',
                'code' => 'privileged_reason_required',
            ], 422);
        }

        $request->attributes->set('privileged_reason', $reason);

        return $next($request);
    }
}
