<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Authorization\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(
        Request $request,
        PermissionService $permissions,
    ): JsonResponse {
        $user = $request->user();

        $user->load([
            'defaultOrganization',
            'roleAssignments.role',
        ]);

        return response()->json([
            'data' => [
                'id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'mfa' => [
                    'required' => $user->mfa_required,
                    'enrolled' => $user->two_factor_confirmed_at !== null,
                ],
                'organization' => $user->defaultOrganization ? [
                    'id' => $user->defaultOrganization->public_id,
                    'code' => $user->defaultOrganization->code,
                    'name' => $user->defaultOrganization->trading_name
                        ?: $user->defaultOrganization->legal_name,
                ] : null,
                'roles' => $user->roleAssignments
                    ->pluck('role.code')
                    ->unique()
                    ->values()
                    ->all(),
                'permissions' => $permissions->codes(
                    $user,
                    $user->defaultOrganization,
                ),
            ],
        ]);
    }
}
