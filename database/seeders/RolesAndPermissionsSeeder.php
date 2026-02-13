<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'channels.view',
            'channels.create',
            'channels.update',
            'channels.delete',
            'conversations.view',
            'conversations.export',
            'knowledge.view',
            'knowledge.upload',
            'knowledge.delete',
            'leads.view',
            'leads.update',
            'leads.export',
            'escalations.view',
            'escalations.assign',
            'escalations.resolve',
            'prompts.view',
            'prompts.update',
            'team.view',
            'team.invite',
            'team.remove',
            'integrations.view',
            'integrations.manage',
            'analytics.view',
            'billing.view',
            'billing.manage',
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $tenantOwner = Role::findOrCreate('tenant_owner', 'web');
        $tenantOwner->givePermissionTo($permissions);

        $tenantAdmin = Role::findOrCreate('tenant_admin', 'web');
        $tenantAdmin->givePermissionTo(array_filter($permissions, fn ($p) => ! in_array($p, [
            'team.remove',
            'billing.manage',
            'channels.delete',
        ])));

        $tenantMember = Role::findOrCreate('tenant_member', 'web');
        $tenantMember->givePermissionTo([
            'conversations.view',
            'knowledge.view',
            'leads.view',
            'leads.update',
            'escalations.view',
            'escalations.resolve',
            'prompts.view',
            'analytics.view',
        ]);
    }
}
