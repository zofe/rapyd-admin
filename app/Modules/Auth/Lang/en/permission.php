<?php

return [

    'company'                       => 'Company',
    'name'                          => 'Name',
    'permissions'                   => 'Permissions',

    'role_permissions_list'         => 'Role Permissions List',
    'role_permissions_view'         => 'View Role Permissions',
    'role_permissions_edit'         => 'Edit Role Permissions',
    'role_permissions_add'          => 'Add Role Permissions',
    'delete_role_permissions'       => 'Remove Role Permissions',

    'can view routers' => 'Allows you to view routers.',
    'can provision routers' => 'Allows provisioning of routers.',
    'can unprovision routers' => 'Allows routers to be removed from the system.',
    'can configure notifications' => 'Allows configuration of router event notifications.',
    'can manage lan/wan' => 'Allows management of the LAN/WAN configuration.',
    'can manage templates' => 'Allows management of templates for easier configuration and setup.',
    'can manage leases' => 'Allows management of leases.',
    'can manage reboots' => 'Allows management of reboots, allowing reboots to be scheduled in the future or immediately.',
    'can use interactive terminal' => 'Allows the use of an interactive terminal for command line operations.',
    'can view backups' => 'Allows you to view backups.',
    'can manage backups' => 'Allows management of backups, including running and restoring configuration.',
    'can manage continuity' => 'Allows management of the Continuity service.',
    'can activate continuity' => 'Allows activation of the Continuity service.',
    'can deactivate continuity' => 'Allows the Continuity service to be deactivated.',
    'can manage nat' => 'Allows management of NATs, SNATs and Bypass.',
    'can manage wallet' => 'Allows management of the wallet or financial aspects of the system.',
    'can view subscriptions' => 'Allows you to view subscription information for monitoring services.',
    'can manage customers' => 'Can manage customers.',
    'can view customers' => 'Allows you to view customer profiles and details.',
    'can view users' => 'Allows you to view the profiles of your company\'s users.',
    'can manage users' => 'Allows you to manage the profiles of your company\'s users.',
    'can view tickets' => 'Allows you to view support tickets and their status.',
    'can manage tickets' => 'Allows management of support tickets',
    'can manage ztna' => 'Allows management of the ZTNA',
    'can manage routers' => 'Allows management of routers',
    'can view logs' => 'Allows you to view user activity logs',
    'can manage clusters' => 'Allows the request of a new cluster on prem',
    'can manage apis' => 'Allows display and management of API token',
    'can manage dns' => 'Allows viewing and management of DNS protection via FlashStart®',
    'can view weathermaps' => 'Allow viewing the network Weathermap',
    'can manage weathermaps' => 'Allows management of network Weathermap',

    'explain_role_permissions'      => 'Each user can have a role; certain permissions are applied to each role.
You can edit the permissions enabled on default roles or create custom roles for your users.

The master/owner user necessarily has all permissions enabled, and cannot be removed.',

    'tip_role_permission'=>'<i class="fas fa-lightbulb"></i><b>TIP!</b>You can drag a <span class="badge rounded-pill bg-primary" style="opacity: 0.8;"><i class="fas fa-grip-vertical"></i> User</span> to another section to change their role! (admin cannot be moved).',

];
