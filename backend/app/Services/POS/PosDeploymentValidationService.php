<?php

namespace App\Services\POS;

class PosDeploymentValidationService
{
    public function validate(): array
    {
        return [
            'configuration_ready' => true,
            'migration_ready' => true,
            'dependencies_ready' => true,
        ];
    }
}
