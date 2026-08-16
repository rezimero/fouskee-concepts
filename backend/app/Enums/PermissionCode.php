<?php

namespace App\Enums;

enum PermissionCode: string
{
    case OrganizationView = 'organization.view';
    case OrganizationManage = 'organization.manage';
    case UserView = 'identity.users.view';
    case UserManage = 'identity.users.manage';
    case RoleView = 'identity.roles.view';
    case RoleManage = 'identity.roles.manage';
    case AuditView = 'audit.view';
    case AuditExport = 'audit.export';
    case Impersonate = 'security.impersonate';
    case BreakGlass = 'security.break_glass';

    public function domain(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    public function label(): string
    {
        return str($this->value)
            ->replace('.', ' ')
            ->headline()
            ->toString();
    }
}
