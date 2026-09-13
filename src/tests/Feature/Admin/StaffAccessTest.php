<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureAdminAccess;
use App\Mail\Admin\StaffInvitationMail;
use App\Models\ActivityLog;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Product;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_superadmin_can_create_roles_and_invite_multi_role_staff(): void
    {
        Mail::fake();
        $super = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($super, 'admin')->get(route('admin.system.staff.index'))->assertOk()->assertSeeText('User Management');
        $this->get(route('admin.system.roles.create'))->assertOk()->assertSeeText('Show Menu / View');
        $this->post(route('admin.system.roles.store'), ['name' => 'Sales Viewer', 'permissions' => ['sales.view']])->assertSessionHasNoErrors()->assertRedirect();
        $sales = AdminRole::query()->sole();
        $products = $this->role('Products', ['products.view', 'products.edit']);
        $this->get(route('admin.system.staff.create'))->assertOk()->assertSeeText('Effective Access');
        $this->post(route('admin.system.staff.store'), [
            'name' => 'New Staff', 'email' => 'staff@example.com', 'phone' => '0123456789', 'status' => 'active', 'roles' => [$sales->id, $products->id],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $staff = AdminUser::query()->where('email', 'staff@example.com')->sole();
        $this->assertFalse($staff->isSuperAdmin());
        $this->assertCount(2, $staff->accessRoles);
        $this->assertTrue($staff->invitationPending());
        $this->assertNotNull($staff->invitation_token_hash);
        $this->assertDatabaseHas('activity_logs', ['event' => 'admin.staff.created', 'admin_user_id' => $super->id]);
        Mail::assertSent(StaffInvitationMail::class, 1);
        $this->get(route('admin.system.staff.edit', $staff))->assertOk()->assertSeeText('Resend Invitation');
        $url = Mail::sent(StaffInvitationMail::class)->sole()->invitationUrl;
        $this->assertStringNotContainsString($staff->invitation_token_hash, $url);
        $this->post($url, ['password' => 'StaffPassword123!', 'password_confirmation' => 'StaffPassword123!'])->assertRedirect(route('admin.login'));
        $this->assertFalse($staff->fresh()->invitationPending());
        $this->assertTrue(Hash::check('StaffPassword123!', $staff->fresh()->password));
        $this->post($url, ['password' => 'StaffPassword456!', 'password_confirmation' => 'StaffPassword456!'])->assertForbidden();
    }

    public function test_staff_cannot_appoint_users_manage_roles_or_escalate_profile(): void
    {
        $staff = $this->staff(['products.view', 'products.edit']);
        $super = AdminUser::factory()->superAdmin()->create();
        $role = $staff->accessRoles()->first();
        $this->actingAs($staff, 'admin');
        $this->get(route('admin.system.staff.index'))->assertForbidden();
        $this->post(route('admin.system.staff.store'), [])->assertForbidden();
        $this->put(route('admin.system.staff.update', $super), [])->assertForbidden();
        $this->post(route('admin.system.roles.store'), [])->assertForbidden();
        $this->put(route('admin.system.roles.update', $role), [])->assertForbidden();
        $this->delete(route('admin.system.roles.destroy', $role))->assertForbidden();
        $this->post(route('admin.system.staff.invitation', $staff))->assertForbidden();
        $this->put(route('admin.profile.update'), ['name' => $staff->name, 'email' => $staff->email, 'role' => 'super_admin', 'roles' => [$role->id]])->assertRedirect();
        $this->assertFalse($staff->fresh()->isSuperAdmin());
    }

    public function test_hidden_menus_and_read_only_actions_are_blocked_server_side(): void
    {
        $staff = $this->staff(['products.view']);
        $product = Product::factory()->create();
        $this->actingAs($staff, 'admin')->get(route('admin.products.index'))->assertOk()
            ->assertDontSee(route('admin.system.staff.index'), false)
            ->assertDontSee(route('admin.orders.index'), false)
            ->assertDontSee(route('admin.products.create'), false)
            ->assertDontSee(route('admin.products.edit', $product), false)
            ->assertDontSee('data-action="'.route('admin.products.destroy', $product).'"', false);
        $this->get(route('admin.products.show', $product))->assertOk()->assertSeeText($product->prd_name);
        $this->get(route('admin.products.create'))->assertForbidden();
        $this->get(route('admin.products.edit', $product))->assertForbidden();
        $this->put(route('admin.products.update', $product), [])->assertForbidden();
        $this->delete(route('admin.products.destroy', $product))->assertForbidden();
        $this->get(route('admin.sales.index'))->assertForbidden();
        $this->get(route('admin.sales.transactions'))->assertForbidden();
        $this->get(route('admin.system.manage-data'))->assertForbidden();
    }

    public function test_role_union_and_revocation_apply_to_an_existing_session(): void
    {
        $staff = $this->staff(['products.view']);
        $sales = $this->role('Sales', ['sales.view']);
        $staff->accessRoles()->attach($sales);
        $this->actingAs($staff, 'admin')->get(route('admin.sales.index'))->assertOk();
        $this->get(route('admin.sales.transactions'))->assertOk();
        $sales->update(['permissions' => []]);
        $this->get(route('admin.sales.index'))->assertForbidden();
        $this->get(route('admin.sales.transactions'))->assertForbidden();
        $this->get(route('admin.products.index'))->assertOk();
        $staff->update(['status' => 'inactive']);
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_login_lands_on_first_allowed_page_or_profile(): void
    {
        $staff = $this->staff(['sales.view']);
        $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.sales.index'));
        $this->post(route('admin.logout'));
        $staff->accessRoles()->detach();
        $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.profile.show'));
    }

    public function test_invalid_permissions_and_actions_without_view_are_rejected(): void
    {
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->post(route('admin.system.roles.store'), ['name' => 'Escalation', 'permissions' => ['superadmin']])->assertSessionHasErrors('permissions.0');
        $this->post(route('admin.system.roles.store'), ['name' => 'No View', 'permissions' => ['sales.edit']])->assertSessionHasErrors('permissions');
        $this->assertDatabaseCount('admin_roles', 0);
    }

    public function test_superadmin_is_protected_and_stale_updates_do_not_overwrite_access(): void
    {
        $super = AdminUser::factory()->superAdmin()->create();
        $staff = $this->staff(['sales.view']);
        $role = $staff->accessRoles()->first();
        $this->actingAs($super, 'admin');
        $data = ['name' => $staff->name, 'email' => $staff->email, 'status' => 'inactive', 'roles' => [$role->id], 'version' => 0];
        $this->put(route('admin.system.staff.update', $super), array_replace($data, ['email' => $super->email]))->assertForbidden();
        $this->put(route('admin.system.staff.update', $staff), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->put(route('admin.system.staff.update', $staff), array_replace($data, ['status' => 'active']))->assertSessionHasErrors('version');
        $this->assertSame('inactive', $staff->fresh()->status);
        $log = ActivityLog::query()->where('event', 'admin.staff.updated')->sole();
        $this->assertSame('active', $log->properties['before']['status']);
        $this->assertSame('inactive', $log->properties['after']['status']);
        $this->assertSame('active', $super->fresh()->status);
    }

    public function test_roles_in_use_cannot_be_deleted_and_permission_edits_are_audited(): void
    {
        $staff = $this->staff(['sales.view']);
        $role = $staff->accessRoles()->first();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->delete(route('admin.system.roles.destroy', $role))->assertSessionHasErrors('role');
        $this->put(route('admin.system.roles.update', $role), ['name' => $role->name, 'permissions' => ['sales.view', 'sales.edit'], 'version' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['event' => 'admin.role.updated']);
        $this->put(route('admin.system.roles.update', $role), ['name' => $role->name, 'permissions' => [], 'version' => 0])->assertSessionHasErrors('version');
    }

    public function test_expired_inactive_and_replaced_invitations_are_rejected(): void
    {
        Mail::fake();
        $staff = $this->staff(['sales.view']);
        $staff->forceFill(['invited_at' => now(), 'invitation_token_hash' => hash('sha256', 'oldtoken'), 'invitation_expires_at' => now()->subMinute()])->save();
        $this->get(route('admin.invitation.show', ['staff' => $staff, 'token' => 'oldtoken']))->assertForbidden();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin')->post(route('admin.system.staff.invitation', $staff))->assertRedirect();
        $url = Mail::sent(StaffInvitationMail::class)->sole()->invitationUrl;
        $this->get($url)->assertOk()->assertSeeText('Set password');
        $this->get(route('admin.invitation.show', ['staff' => $staff, 'token' => 'oldtoken']))->assertForbidden();
        $staff->update(['status' => 'inactive']);
        $this->get($url)->assertForbidden();
    }

    public function test_sale_edit_permission_does_not_allow_void_or_commit_a_void_preview(): void
    {
        $staff = $this->staff(['sales.view', 'sales.edit']);
        $token = (string) str()->uuid();
        $this->actingAs($staff, 'admin')->withSession(['sale_corrections' => [$token => ['data' => ['action' => 'void']]]])
            ->post(route('admin.sale-corrections.store'), ['token' => $token])->assertForbidden();
    }

    public function test_every_protected_admin_route_has_an_explicit_permission_mapping(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! in_array(EnsureAdminAccess::class, $route->gatherMiddleware(), true)) {
                continue;
            }
            if (in_array($route->getName(), ['admin.sales.preview', 'admin.sale-corrections.store'], true)) {
                continue;
            }
            $this->assertNotNull(AdminAccess::routePermission($route->getName()), $route->getName());
        }
    }

    public function test_editor_can_open_product_edit_but_cannot_delete(): void
    {
        $staff = $this->staff(['products.view', 'products.edit']);
        $product = Product::factory()->create();
        $this->actingAs($staff, 'admin')->get(route('admin.products.edit', $product))->assertOk();
        $this->delete(route('admin.products.destroy', $product))->assertForbidden();
    }

    public function test_failed_invitation_delivery_keeps_staff_pending_and_reports_a_warning(): void
    {
        $role = $this->role('Viewer', ['sales.view']);
        $super = AdminUser::factory()->superAdmin()->create();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail transport unavailable'));
        $this->actingAs($super, 'admin')->post(route('admin.system.staff.store'), [
            'name' => 'Pending Staff', 'email' => 'pending@example.com', 'status' => 'active', 'roles' => [$role->id],
        ])->assertRedirect()->assertSessionHas('warning');
        $this->assertTrue(AdminUser::query()->where('email', 'pending@example.com')->sole()->invitationPending());
        $this->assertDatabaseHas('activity_logs', ['event' => 'admin.staff.invitation_failed']);
    }

    public function test_changed_email_requires_invitation_and_invalidates_previous_password_session(): void
    {
        Mail::fake();
        $staff = $this->staff(['sales.view']);
        $oldPasswordHash = $staff->getAuthPassword();
        $role = $staff->accessRoles()->first();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin')->put(route('admin.system.staff.update', $staff), [
            'name' => $staff->name, 'email' => 'newidentity@example.com', 'status' => 'active', 'roles' => [$role->id], 'version' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $staff->refresh();
        $this->assertTrue($staff->invitationPending());
        $url = Mail::sent(StaffInvitationMail::class)->sole()->invitationUrl;
        $this->post($url, ['password' => 'Replacement123!', 'password_confirmation' => 'Replacement123!'])->assertRedirect();
        $this->actingAs($staff->fresh(), 'admin')->withSession(['password_hash_admin' => $oldPasswordHash])
            ->get(route('admin.sales.index'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    private function role(string $name, array $permissions): AdminRole
    {
        return AdminRole::factory()->create(['name' => $name, 'permissions' => $permissions]);
    }

    private function staff(array $permissions): AdminUser
    {
        $staff = AdminUser::factory()->create();
        $staff->accessRoles()->attach($this->role('Role '.str()->uuid(), $permissions));

        return $staff;
    }
}
