@extends('admin.layouts.app')

@section('title', 'Activity Log | Anugerah3D Admin')

@section('page_title', 'Activity Log')

@section('content')
    @php
        $formatAction = static fn (string $event): string => str($event)->replace(['admin.', '_', '.'], ['', ' ', ' '])->headline()->toString();
        $pageName = static function ($log): string {
            $page = data_get($log->properties, 'page');

            if ($page) {
                return $page;
            }

            return match (true) {
                str_contains($log->event, 'password') => 'Password Recovery',
                str_contains($log->event, 'login') => 'Login',
                str_contains($log->event, 'logout') => 'Profile',
                str_contains($log->event, 'product') => 'Products',
                str_contains($log->event, 'profile') => 'Profile',
                default => '-',
            };
        };
        $eventBadge = static function (string $event): string {
            return match (true) {
                str_contains($event, 'failed') => 'bg-red-50 text-red-700 ring-red-100',
                str_contains($event, 'password') => 'bg-amber-50 text-amber-700 ring-amber-100',
                str_contains($event, 'login'), str_contains($event, 'logout') => 'bg-blue-50 text-blue-700 ring-blue-100',
                str_contains($event, 'deleted') => 'bg-red-50 text-red-700 ring-red-100',
                str_contains($event, 'updated') => 'bg-green-50 text-green-700 ring-green-100',
                default => 'bg-slate-100 text-slate-700 ring-slate-200',
            };
        };
    @endphp

    <div class="space-y-5">
        <div class="flex justify-end">
            <span class="inline-flex w-fit items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ $logs->total() }} records</span>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <form method="GET" action="{{ route('admin.system.activity-log') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user, action, page or IP..." class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">

                <select name="event" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="">All actions</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}" @selected($selectedEvent === $event)>{{ $formatAction($event) }}</option>
                    @endforeach
                </select>

                <select name="admin_user_id" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    <option value="0">All users</option>
                    @foreach ($adminUsers as $adminUser)
                        <option value="{{ $adminUser->id }}" @selected($selectedAdminUserId === $adminUser->id)>{{ $adminUser->name }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#1a73e8] px-3 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">Filter</button>
                    @if ($search !== '' || $selectedEvent !== '' || $selectedAdminUserId > 0)
                        <a href="{{ route('admin.system.activity-log') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
            <table class="admin-data-table w-full text-xs">
                <colgroup>
                    <col style="width: 15%;">
                    <col style="width: 18%;">
                    <col style="width: 14%;">
                    <col style="width: 13%;">
                    <col style="width: 27%;">
                    <col style="width: 13%;">
                </colgroup>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="rounded-tl-lg px-3 py-3 text-left font-semibold text-slate-700">Date / Time</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">User</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Action</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Page</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Activity</th>
                        <th class="rounded-tr-lg px-3 py-3 text-left font-semibold text-slate-700">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($logs as $log)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-3 py-3 text-slate-600">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900">{{ $log->adminUser?->name ?: data_get($log->properties, 'actor_name', 'System') }}</div>
                                <div class="mt-0.5 truncate text-[0.72rem] text-slate-500">{{ $log->adminUser?->email ?: data_get($log->properties, 'email', '-') }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-[0.7rem] font-semibold ring-1 {{ $eventBadge($log->event) }}">{{ $formatAction($log->event) }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-700">{{ $pageName($log) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ $log->description }}</td>
                            <td class="px-3 py-3 font-mono text-[0.72rem] text-slate-600">{{ $log->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-600">No activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            @forelse ($logs as $log)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-950">{{ $log->adminUser?->name ?: data_get($log->properties, 'actor_name', 'System') }}</p>
                            <p class="mt-0.5 break-all text-xs text-slate-500">{{ $log->adminUser?->email ?: data_get($log->properties, 'email', '-') }}</p>
                        </div>
                        <span class="inline-flex flex-none rounded-lg px-2.5 py-1 text-[0.7rem] font-semibold ring-1 {{ $eventBadge($log->event) }}">{{ $formatAction($log->event) }}</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-700">{{ $log->description }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="font-semibold uppercase text-slate-500">Page</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $pageName($log) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="font-semibold uppercase text-slate-500">Date / Time</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $log->created_at->format('d M Y, h:i A') }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="font-semibold uppercase text-slate-500">IP</dt>
                            <dd class="mt-1 break-all font-mono text-slate-900">{{ $log->ip_address ?: '-' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="font-semibold uppercase text-slate-500">Route</dt>
                            <dd class="mt-1 break-all font-semibold text-slate-900">{{ data_get($log->properties, 'route', '-') }}</dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-6 text-center text-slate-600 shadow-sm">No activity found.</div>
            @endforelse
        </div>

        <div class="flex justify-center">
            {{ $logs->links('pagination::tailwind') }}
        </div>
    </div>
@endsection
