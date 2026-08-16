<?php

namespace App\Console\Commands;

use App\Enums\RoleScope;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Authorization\RoleAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BootstrapSuperadmin extends Command
{
    protected $signature = 'fouskee:bootstrap-superadmin';

    protected $description = 'Create the first Fouskee Concepts superadmin account';

    public function handle(
        RoleAssignmentService $assignments,
        AuditRecorder $audit,
    ): int {
        $name = trim((string) $this->ask('Full name'));
        $email = trim((string) $this->ask('Email address'));
        $password = (string) $this->secret('Password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'confirmation'),
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12', 'same:confirmation'],
            ],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password, $assignments, $audit): void {
            $organization = Organization::query()->firstOrCreate(
                ['code' => 'FOUSKEE'],
                [
                    'legal_name' => 'Fouskee Concepts',
                    'trading_name' => 'Fouskee Concepts',
                ],
            );

            $role = Role::query()
                ->where('code', 'superadmin')
                ->where('scope', RoleScope::Global->value)
                ->firstOrFail();

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'status' => UserStatus::Active,
                'default_organization_id' => $organization->id,
                'email_verified_at' => now(),
                'mfa_required' => true,
            ]);

            $assignments->assign($user, $role);

            $audit->record(
                event: 'identity.superadmin_bootstrapped',
                subject: $user,
                organizationId: $organization->id,
                context: ['source' => 'console'],
            );
        });

        $this->info('Superadmin created.');
        $this->warn('MFA is mandatory and will be enrolled before privileged UI access is enabled.');

        return self::SUCCESS;
    }
}
