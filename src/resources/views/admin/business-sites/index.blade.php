@extends('admin.layouts.app')

@section('title', 'Business Sites | Anugerah3D Admin')
@section('page_title', 'Business Sites')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between gap-4">
        <div><h2 class="text-xl font-semibold text-slate-900">Business sites</h2><p class="text-sm text-slate-500">Manage POS locations and assigned agents.</p></div>
        <a href="{{ route('admin.business-sites.create') }}" class="rounded-lg bg-[#1a73e8] px-4 py-2.5 text-sm font-semibold text-white">Add site</a>
    </div>
    @if (session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>@endif
    @error('business_site')<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>@enderror
    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Site</th><th class="px-5 py-3">City</th><th class="px-5 py-3">Agents</th><th class="px-5 py-3">POS sales</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($businessSites as $site)
                        <tr><td class="px-5 py-4 font-semibold text-slate-900">{{ $site->site_name }}</td><td class="px-5 py-4 text-slate-600">{{ $site->city }}</td><td class="px-5 py-4">{{ $site->agents_count }}</td><td class="px-5 py-4">{{ $site->pos_sales_count }}</td><td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.business-sites.edit', $site) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-700">Edit</a><form method="POST" action="{{ route('admin.business-sites.destroy', $site) }}" onsubmit="return confirm('Delete this business site?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-1.5 font-semibold text-red-600">Delete</button></form></div></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No business sites yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $businessSites->links() }}
</div>
@endsection
