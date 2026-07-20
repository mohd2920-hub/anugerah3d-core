@php
    $selectedAgentIds = old('agent_ids', $businessSite?->agents?->pluck('id')->all() ?? []);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="site_name" class="mb-2 block text-sm font-medium text-slate-700">Site name <span class="text-red-600">*</span></label>
        <input id="site_name" name="site_name" value="{{ old('site_name', $businessSite?->site_name) }}" maxlength="150" required class="w-full rounded-lg border border-slate-300 px-4 py-2 outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
        @error('site_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="city" class="mb-2 block text-sm font-medium text-slate-700">City <span class="text-red-600">*</span></label>
        <input id="city" name="city" value="{{ old('city', $businessSite?->city) }}" maxlength="100" required class="w-full rounded-lg border border-slate-300 px-4 py-2 outline-none focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
        @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <fieldset>
        <legend class="mb-2 text-sm font-medium text-slate-700">Assigned agents</legend>
        <div class="max-h-80 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
            @forelse ($agents as $agent)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-slate-50">
                    <input type="checkbox" name="agent_ids[]" value="{{ $agent->id }}" @checked(in_array($agent->id, $selectedAgentIds)) class="h-4 w-4 rounded border-slate-300 text-[#1a73e8]">
                    <span><span class="block text-sm font-semibold text-slate-800">{{ $agent->agt_name }}</span><span class="text-xs text-slate-500">{{ $agent->login_id }}</span></span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No active agents available.</p>
            @endforelse
        </div>
        @error('agent_ids.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </fieldset>

    <div class="flex gap-3">
        <button class="rounded-lg bg-[#1a73e8] px-5 py-2.5 text-sm font-semibold text-white">{{ $submitLabel }}</button>
        <a href="{{ route('admin.business-sites.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
    </div>
</form>
