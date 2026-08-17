<?php

namespace Database\Seeders;

use App\Enums\PermissionCode;
use App\Enums\RoleScope;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = $this->role('superadmin', 'Superadmin', RoleScope::Global);
        $administrator = $this->role(
            'administrator',
            'Administrator',
            RoleScope::Organization,
        );
        $auditor = $this->role('auditor', 'Auditor', RoleScope::Organization);

        $administrator->permissions()->sync($this->ids([
            PermissionCode::OrganizationView,
            PermissionCode::OrganizationManage,
            PermissionCode::UserView,
            PermissionCode::UserManage,
            PermissionCode::RoleView,
            PermissionCode::RoleManage,
            PermissionCode::CustomerView,
            PermissionCode::CustomerManage,
            PermissionCode::SupplierView,
            PermissionCode::SupplierManage,
            PermissionCode::CatalogView,
            PermissionCode::CatalogManage,
            PermissionCode::PricingView,
            PermissionCode::PricingManage,
            PermissionCode::AuditView,
        ]));

        $auditor->permissions()->sync($this->ids([
            PermissionCode::OrganizationView,
            PermissionCode::CustomerView,
            PermissionCode::SupplierView,
            PermissionCode::CatalogView,
            PermissionCode::PricingView,
            PermissionCode::AuditView,
            PermissionCode::AuditExport,
        ]));

        $superadmin->permissions()->sync([]);
    }

    private function role(string $code, string $name, RoleScope $scope): Role
    {
        return Role::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'scope' => $scope,
                'is_system' => true,
            ],
        );
    }

    private function ids(array $permissions): array
    {
        return Permission::query()
            ->whereIn('code', array_map(fn ($item) => $item->value, $permissions))
            ->pluck('id')
            ->all();
    }
}
