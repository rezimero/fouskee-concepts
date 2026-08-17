<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CurrentOrganization
{
    public function for(User $user): Organization
    {
        $organization = $user->defaultOrganization;

        if ($organization === null) {
            throw ValidationException::withMessages([
                'organization' => 'A default organization is required.',
            ]);
        }

        return $organization;
    }
}
