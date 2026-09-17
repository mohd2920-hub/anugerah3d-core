<?php

namespace App\Support;

use App\Models\AdminUser;
use Illuminate\Http\Request;

class AdminAccess
{
    /** @return array<string, array{label: string, route: string, actions: array<string, string>}> */
    public static function modules(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'actions' => []],
            'customer-orders' => ['label' => 'Customer Orders', 'route' => 'admin.customer-orders.index', 'actions' => ['edit' => 'Manage orders / payment / refunds', 'commission' => 'Record commission payments']],
            'orders' => ['label' => 'Orders', 'route' => 'admin.orders.index', 'actions' => ['edit' => 'Update payment', 'process' => 'Process', 'complete' => 'Complete', 'cancel' => 'Cancel', 'print' => 'Print']],
            'sales' => ['label' => 'Sales', 'route' => 'admin.sales.index', 'actions' => ['create' => 'Add Missing Sale', 'edit' => 'Edit Sale', 'void' => 'Void Sale']],
            'products' => ['label' => 'Products', 'route' => 'admin.products.index', 'actions' => ['create' => 'Create', 'edit' => 'Edit / Agent visibility', 'delete' => 'Delete']],
            'business-sites' => ['label' => 'Business Sites', 'route' => 'admin.business-sites.index', 'actions' => ['create' => 'Create', 'edit' => 'Edit', 'operate' => 'Open / Close Session', 'delete' => 'Delete']],
            'agents' => ['label' => 'Agents', 'route' => 'admin.agents.index', 'actions' => ['create' => 'Create', 'edit' => 'Edit / Profile picture', 'approve' => 'Approve registration', 'password' => 'Reset password', 'resend' => 'Send registration info', 'delete' => 'Delete']],
            'agent-email-templates' => ['label' => 'Email to Agen', 'route' => 'admin.agent-email-templates.index', 'actions' => ['manage' => 'Manage Templates', 'send' => 'Send Email']],
            'salary-management' => ['label' => 'Salary Management', 'route' => 'admin.salary-management.index', 'actions' => ['create' => 'Record historical paid salary', 'draft' => 'Manage staff attendance and salary drafts', 'confirm' => 'Confirm staff salary payment and email slips']],
            'weekly-closings' => ['label' => 'Weekly Closing', 'route' => 'admin.weekly-closings.index', 'actions' => ['payment' => 'Record Payment']],
        ];
    }

    /** @return list<string> */
    public static function permissions(): array
    {
        $permissions = [];
        foreach (self::modules() as $module => $details) {
            foreach (['view', ...array_keys($details['actions'])] as $action) {
                $permissions[] = "$module.$action";
            }
        }

        return $permissions;
    }

    public static function allows(?AdminUser $user, string $permission): bool
    {
        if (! $user || $user->status !== AdminUser::StatusActive || $user->invitationPending()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (! in_array($permission, self::permissions(), true)) {
            return false;
        }
        $permissions = $user->accessRoles->flatMap(fn ($role) => $role->permissions ?? [])->unique();
        $module = explode('.', $permission)[0];

        return $permissions->contains($module.'.view') && $permissions->contains($permission);
    }

    public static function landingRoute(AdminUser $user): string
    {
        foreach (self::modules() as $module => $details) {
            if (self::allows($user, $module.'.view')) {
                return $details['route'];
            }
        }

        return 'admin.profile.show';
    }

    public static function routePermission(string $route): ?string
    {
        if (str_starts_with($route, 'admin.system.')) {
            return 'superadmin';
        }
        if (in_array($route, ['admin.profile.show', 'admin.profile.update', 'admin.profile.password.update', 'admin.logout'], true)) {
            return 'self';
        }
        if (in_array($route, ['admin.dashboard', 'admin.dashboard.data', 'admin.dashboard.inventory', 'admin.dashboard.export'], true)) {
            return 'dashboard.view';
        }
        $special = [
            'admin.salary-management.confirm' => 'salary-management.confirm',
            'admin.salary-management.email' => 'salary-management.confirm',
            'admin.salary-management.sessions' => 'salary-management.view',
            'admin.salary-management.sessions.preview' => 'salary-management.draft',
            'admin.salary-management.sessions.store' => 'salary-management.draft',
            'admin.salary-management.summary' => 'salary-management.view',
            'admin.salary-management.proof' => 'salary-management.view',
            'admin.salary-management.daily' => 'salary-management.view',
            'admin.salary-management.settings' => 'salary-management.view',
            'admin.salary-management.payslips' => 'salary-management.view',
            'admin.customer-orders.commission-proof' => 'customer-orders.view',
            'admin.customer-orders.payout' => 'customer-orders.commission',
            'admin.customer-orders.proof' => 'customer-orders.view',
            'admin.orders.payment.update' => 'orders.edit',
            'admin.orders.discount-settings.update' => 'superadmin',
            'admin.orders.print.full' => 'orders.print',
            'admin.orders.print.order' => 'orders.print',
            'admin.orders.process' => 'orders.process',
            'admin.orders.complete' => 'orders.complete',
            'admin.orders.cancel' => 'orders.cancel',
            'admin.sales.transactions' => 'sales.view',
            'admin.sales.add' => 'sales.create',
            'admin.sales.preview-missing' => 'sales.create',
            'admin.products.balance.show' => 'products.edit',
            'admin.products.balance.update' => 'products.edit',
            'admin.products.statistics' => 'products.view',
            'admin.products.agent-visibility.toggle' => 'products.edit',
            'admin.products.discontinuation.update' => 'products.edit',
            'admin.business-site-operations.closure-preview' => 'superadmin',
            'admin.business-site-operations.closure-store' => 'superadmin',
            'admin.business-site-operations.show' => 'business-sites.view',
            'admin.business-site-operations.destroy' => 'business-sites.delete',
            'admin.business-sites.statistics' => 'business-sites.view',
            'admin.business-sites.summaries' => 'business-sites.view',
            'admin.business-sites.summary' => 'business-sites.view',
            'admin.business-sites.start' => 'business-sites.operate',
            'admin.business-sites.stop' => 'business-sites.operate',
            'admin.agents.profile-picture.update' => 'agents.edit',
            'admin.agents.approve' => 'agents.approve',
            'admin.agents.password.update' => 'agents.password',
            'admin.agents.registration-info.resend' => 'agents.resend',
            'admin.agent-email-templates.create' => 'agent-email-templates.manage',
            'admin.agent-email-templates.store' => 'agent-email-templates.manage',
            'admin.agent-email-templates.edit' => 'agent-email-templates.manage',
            'admin.agent-email-templates.update' => 'agent-email-templates.manage',
            'admin.agent-email-templates.send' => 'agent-email-templates.send',
            'admin.weekly-closings.payments.update' => 'weekly-closings.payment',
        ];
        if (isset($special[$route])) {
            return $special[$route];
        }
        foreach (array_keys(self::modules()) as $module) {
            foreach (['index' => 'view', 'show' => 'view', 'create' => 'create', 'store' => 'create', 'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete'] as $suffix => $action) {
                if ($route === "admin.$module.$suffix" && in_array("$module.$action", self::permissions(), true)) {
                    return "$module.$action";
                }
            }
        }

        return null;
    }

    public static function canRoute(?AdminUser $user, string $route): bool
    {
        $permission = self::routePermission($route);

        return $permission === 'self' ? $user?->status === AdminUser::StatusActive : ($permission !== null && self::allows($user, $permission));
    }

    public static function requestPermission(Request $request): ?string
    {
        $route = (string) $request->route()?->getName();
        if ($route === 'admin.sales.preview' || $route === 'admin.sale-corrections.store') {
            $token = $request->input('token');
            $action = $route === 'admin.sales.preview' ? $request->input('action') : (is_string($token) ? $request->session()->get('sale_corrections.'.$token.'.data.action') : null);

            return match ($action) {
                'correct' => 'sales.edit', 'missing' => 'sales.create', 'void' => 'sales.void', default => null,
            };
        }

        return self::routePermission($route);
    }
}
