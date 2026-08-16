<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\RevokeSessionRequest;
use App\Services\Audit\AuditRecorder;
use App\Services\Security\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecuritySessionController extends Controller
{
    public function index(
        Request $request,
        SessionService $sessions,
    ): JsonResponse {
        return response()->json([
            'data' => $sessions->forUser(
                $request->user(),
                $this->sessionId($request),
            ),
        ]);
    }

    public function destroy(
        RevokeSessionRequest $request,
        string $sessionReference,
        SessionService $sessions,
        AuditRecorder $audit,
    ): Response {
        $user = $request->user();

        $sessions->revoke(
            $user,
            $sessionReference,
            $this->sessionId($request),
        );

        $audit->record(
            event: 'security.session_revoked',
            actor: $user,
            subject: $user,
            reason: (string) $request->attributes->get('privileged_reason'),
            context: ['session_reference' => $sessionReference],
        );

        return response()->noContent();
    }

    private function sessionId(Request $request): ?string
    {
        return $request->hasSession()
            ? $request->session()->getId()
            : null;
    }
}
