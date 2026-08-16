<?php

namespace App\Services\Authentication;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateActiveUser
{
    public function __invoke(Request $request): ?User
    {
        $email = mb_strtolower(trim((string) $request->input('email')));

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user?->status !== UserStatus::Active) {
            return null;
        }

        return Hash::check((string) $request->input('password'), $user->password)
            ? $user
            : null;
    }
}
