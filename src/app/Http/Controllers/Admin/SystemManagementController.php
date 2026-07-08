<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SystemManagementController extends Controller
{
    public function manageData(): View
    {
        $lookupGroups = [
            ['name' => 'Status', 'description' => 'Central status values for operational records.', 'items' => ['Active', 'Inactive', 'Pending', 'Archived']],
            ['name' => 'Activity Type', 'description' => 'Audit action categories for activity tracking.', 'items' => ['Login', 'Logout', 'Password recovery', 'Data update']],
            ['name' => 'User Role', 'description' => 'Admin access role labels and grouping.', 'items' => ['Super admin', 'Admin', 'Support']],
            ['name' => 'Order Status', 'description' => 'Order workflow status values.', 'items' => ['Draft', 'Processing', 'Ready', 'Completed']],
            ['name' => 'Payment Status', 'description' => 'Payment collection status labels.', 'items' => ['Unpaid', 'Partial', 'Paid', 'Refunded']],
            ['name' => 'Product Material', 'description' => 'Material lookup used by product catalogue.', 'items' => ['PLA', 'PETG', 'ABS', 'TPU']],
        ];

        return view('admin.system.manage-data', [
            'lookupGroups' => $lookupGroups,
        ]);
    }

    public function activityLog(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $event = $request->string('event')->trim()->toString();
        $adminUserId = $request->integer('admin_user_id');

        $logs = ActivityLog::query()
            ->with('adminUser')
            ->when($search !== '', function (Builder $query) use ($search): Builder {
                return $query->where(function (Builder $query) use ($search): void {
                    $query->where('event', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('properties', 'like', "%{$search}%")
                        ->orWhereHas('adminUser', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn (Builder $query): Builder => $query->where('event', $event))
            ->when($adminUserId > 0, fn (Builder $query): Builder => $query->where('admin_user_id', $adminUserId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.system.activity-log', [
            'logs' => $logs,
            'events' => ActivityLog::query()->distinct()->orderBy('event')->pluck('event'),
            'adminUsers' => AdminUser::query()->orderBy('name')->get(['id', 'name', 'email']),
            'search' => $search,
            'selectedEvent' => $event,
            'selectedAdminUserId' => $adminUserId,
        ]);
    }
}
