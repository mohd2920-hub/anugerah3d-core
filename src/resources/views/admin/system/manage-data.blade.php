@extends('admin.layouts.app')

@section('title', 'Manage Data | Anugerah3D Admin')

@section('page_title', 'Manage Data')

@section('content')
    <div class="space-y-5">
        <div class="flex justify-end">
            <span class="inline-flex w-fit items-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">Coming soon</span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($lookupGroups as $group)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-slate-950">{{ $group['name'] }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $group['description'] }}</p>
                        </div>
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Soon</span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($group['items'] as $item)
                            <span class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $item }}</span>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endsection
