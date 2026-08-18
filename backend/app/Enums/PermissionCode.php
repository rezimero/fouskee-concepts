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
    case CustomerView = 'customers.view';
    case CustomerManage = 'customers.manage';
    case SupplierView = 'suppliers.view';
    case SupplierManage = 'suppliers.manage';
    case CatalogView = 'catalog.view';
    case CatalogManage = 'catalog.manage';
    case PricingView = 'pricing.view';
    case PricingManage = 'pricing.manage';
    case InventoryView = 'inventory.view';
    case InventoryLocationManage = 'inventory.locations.manage';
    case InventoryPolicyManage = 'inventory.policy.manage';
    case InventoryReceive = 'inventory.receive';
    case InventoryTransfer = 'inventory.transfer';
    case InventoryReserve = 'inventory.reserve';
    case InventoryCount = 'inventory.count';
    case InventoryAdjust = 'inventory.adjust';
    case InventoryAdjustApprove = 'inventory.adjust.approve';
    case InventoryReverse = 'inventory.reverse';
    case InventoryBackdate = 'inventory.backdate';
    case InventoryNegativeStockOverride = 'inventory.negative_stock.override';
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
