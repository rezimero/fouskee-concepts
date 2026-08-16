<?php

namespace App\Http\Middleware;

use App\Services\Authorization\PermissionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $permission,
    ): Response {
        $user = $request->user();
        $organization = $user?->defaultOrganization;

        if ($user === null || ! $this->permissions->allows(
            $user,
            $permission,
            $organization,
        )) {
            return new JsonResponse([
                'message' => 'You are not authorized for this action.',
                'code' => 'permission_denied',
            ], 403);
        }

        return $next($request);
    }
}
