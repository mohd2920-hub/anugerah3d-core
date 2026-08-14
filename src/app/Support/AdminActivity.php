<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminActivity
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(
        Request $request,
        string $event,
        string $description,
        ?AdminUser $adminUser = null,
        array $properties = [],
    ): void {
        $routeName = $request->route()?->getName();
        $page = $properties['page'] ?? self::pageFromRoute($routeName, $request->path());

        $properties = array_filter(array_merge([
            'page' => $page,
            'route' => $routeName,
            'method' => $request->method(),
        ], $properties), static fn ($value): bool => $value !== null && $value !== '');

        ActivityLog::query()->create([
            'admin_user_id' => $adminUser?->getKey(),
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'properties' => $properties === [] ? null : $properties,
        ]);
    }

    private static function pageFromRoute(?string $routeName, string $path): string
    {
        if ($routeName === null) {
            return Str::headline(str_replace(['admin/', '-'], ['', ' '], $path));
        }

        return match (true) {
            Str::contains($routeName, 'password') => 'Password Recovery',
            Str::contains($routeName, 'login') => 'Login',
            Str::contains($routeName, 'logout') => 'Profile',
            Str::contains($routeName, 'profile') => 'Profile',
            Str::contains($routeName, 'agent-email-templates') => 'Email to Agen',
            Str::contains($routeName, 'agents') => 'Agents',
            Str::contains($routeName, 'products') => 'Products',
            Str::contains($routeName, 'system.manage-data') => 'Manage Data',
            Str::contains($routeName, 'system.activity-log') => 'Activity Log',
            Str::contains($routeName, 'dashboard') => 'Dashboard',
            default => Str::headline(str_replace(['admin.', '.', '-'], ['', ' ', ' '], $routeName)),
        };
    }
}
