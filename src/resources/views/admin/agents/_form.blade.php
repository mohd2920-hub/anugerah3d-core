@php
    $agent ??= null;
    $method ??= 'POST';
    $isEdit = $agent !== null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="login_id" class="mb-2 block text-sm font-medium text-slate-700">
                Login ID <span class="text-red-600">*</span>
            </label>
            <input type="text" id="login_id" name="login_id" value="{{ old('login_id', $agent->login_id ?? '') }}" placeholder="e.g., AGT1001" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
            @error('login_id')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="agt_name" class="mb-2 block text-sm font-medium text-slate-700">
                Agent Name <span class="text-red-600">*</span>
            </label>
            <input type="text" id="agt_name" name="agt_name" value="{{ old('agt_name', $agent->agt_name ?? '') }}" placeholder="e.g., Nur Aisyah" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
            @error('agt_name')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="id_number" class="mb-2 block text-sm font-medium text-slate-700">ID Number</label>
            <input type="text" id="id_number" name="id_number" value="{{ old('id_number', $agent->id_number ?? '') }}" placeholder="e.g., 900101-10-1234" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            @error('id_number')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="agt_status" class="mb-2 block text-sm font-medium text-slate-700">
                Status <span class="text-red-600">*</span>
            </label>
            <select id="agt_status" name="agt_status" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ old('agt_status', $agent->agt_status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('agt_status')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div>
        @php
            $selectedReferrerId = (string) old('referrer_id', $agent->referrer_id ?? '');
            $selectedReferrer = $referrerOptions->first(fn ($option) => (string) $option->getKey() === $selectedReferrerId);
            $selectedReferrerPicture = $selectedReferrer?->profile_picture
                ? (filter_var($selectedReferrer->profile_picture, FILTER_VALIDATE_URL) ? $selectedReferrer->profile_picture : asset($selectedReferrer->profile_picture))
                : null;
        @endphp
        <label class="mb-2 block text-sm font-medium text-slate-700">Referrer Agent</label>
        <div class="relative" data-referrer-picker>
            <input type="hidden" id="referrer_id" name="referrer_id" value="{{ $selectedReferrerId }}" data-referrer-value>
            <button type="button" data-referrer-trigger aria-expanded="false" class="flex min-h-14 w-full items-center gap-3 rounded-xl border border-slate-300 bg-white px-3 py-2 text-left shadow-sm outline-none transition hover:border-blue-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                <span data-referrer-trigger-avatar class="grid h-10 w-10 flex-none place-items-center overflow-hidden rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                    @if ($selectedReferrerPicture)
                        <img src="{{ $selectedReferrerPicture }}" alt="" class="h-full w-full object-cover">
                    @elseif ($selectedReferrer)
                        {{ $selectedReferrer->initials() }}
                    @else
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M17 8h4m-2-2v4"/></svg>
                    @endif
                </span>
                <span class="min-w-0 flex-1">
                    <span data-referrer-trigger-label class="block truncate text-sm font-semibold {{ $selectedReferrer ? 'text-slate-900' : 'text-slate-500' }}">
                        {{ $selectedReferrer ? $selectedReferrer->agt_name.' · '.$selectedReferrer->login_id : 'Choose referrer agent' }}
                    </span>
                    <span class="mt-0.5 block text-xs text-slate-400">Optional</span>
                </span>
                <svg class="h-5 w-5 flex-none text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <div data-referrer-panel class="absolute left-0 right-0 top-full z-50 mt-2 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 p-3">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" data-referrer-search placeholder="Search agent name, login ID or phone" autocomplete="off" class="w-full rounded-lg border border-slate-300 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-[#1a73e8] focus:bg-white focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div class="max-h-72 overflow-y-auto p-2" data-referrer-options>
                    <button type="button" data-referrer-option data-value="" data-label="Choose referrer agent" data-search="no referrer" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-slate-50">
                        <span data-option-avatar class="grid h-10 w-10 flex-none place-items-center rounded-full bg-slate-100 text-slate-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 8 8 8"/></svg></span>
                        <span><span class="block text-sm font-semibold text-slate-700">No referrer</span><span class="block text-xs text-slate-400">Register without a referring agent</span></span>
                    </button>

                    @foreach ($referrerOptions as $referrerOption)
                        @continue($isEdit && $referrerOption->is($agent))
                        @php
                            $referrerPicture = $referrerOption->profile_picture
                                ? (filter_var($referrerOption->profile_picture, FILTER_VALIDATE_URL) ? $referrerOption->profile_picture : asset($referrerOption->profile_picture))
                                : null;
                            $referrerLabel = $referrerOption->agt_name.' · '.$referrerOption->login_id;
                        @endphp
                        <button type="button" data-referrer-option data-value="{{ $referrerOption->getKey() }}" data-label="{{ $referrerLabel }}" data-search="{{ \Illuminate\Support\Str::lower($referrerOption->agt_name.' '.$referrerOption->login_id.' '.$referrerOption->phone_number.' '.$referrerOption->email) }}" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-blue-50">
                            <span data-option-avatar class="grid h-10 w-10 flex-none place-items-center overflow-hidden rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                                @if ($referrerPicture)
                                    <img src="{{ $referrerPicture }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    {{ $referrerOption->initials() }}
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $referrerOption->agt_name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $referrerOption->login_id }}{{ $referrerOption->phone_number ? ' · '.$referrerOption->phone_number : '' }}</span>
                            </span>
                            <svg data-selected-check class="{{ $selectedReferrerId === (string) $referrerOption->getKey() ? '' : 'hidden' }} h-5 w-5 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>
                        </button>
                    @endforeach

                    <p data-referrer-empty class="hidden px-3 py-8 text-center text-sm text-slate-500">No matching agent found.</p>
                </div>
            </div>
        </div>
        @error('referrer_id')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                Email <span class="text-red-600">*</span>
            </label>
            <input type="email" id="email" name="email" value="{{ old('email', $agent->email ?? '') }}" placeholder="agent@example.com" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
            @error('email')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="phone_number" class="mb-2 block text-sm font-medium text-slate-700">Phone Number</label>
            <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $agent->phone_number ?? '') }}" placeholder="e.g., 0123456789" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            @error('phone_number')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
    </div>

    @unless ($isEdit)
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
                    Initial Password <span class="text-red-600">*</span>
                </label>
                <input type="password" id="password" name="password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                @error('password')
                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">
                    Confirm Password <span class="text-red-600">*</span>
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
            </div>
        </div>
    @endunless

    <div>
        <label for="address" class="mb-2 block text-sm font-medium text-slate-700">Address</label>
        <input type="text" id="address" name="address" value="{{ old('address', $agent->address ?? '') }}" placeholder="Street address" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
        @error('address')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="city" class="mb-2 block text-sm font-medium text-slate-700">City</label>
            <input type="text" id="city" name="city" value="{{ old('city', $agent->city ?? '') }}" placeholder="e.g., Shah Alam" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            @error('city')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="state" class="mb-2 block text-sm font-medium text-slate-700">State</label>
            <select id="state" name="state" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                <option value="">Select a state</option>
                @forelse($states as $state)
                    <option value="{{ $state->name }}" {{ old('state', $agent->state ?? '') === $state->name ? 'selected' : '' }}>{{ $state->name }}</option>
                @empty
                    <option disabled>No states available</option>
                @endforelse
            </select>
            @error('state')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="discount_percentage" class="mb-2 block text-sm font-medium text-slate-700">
                Discount (%) <span class="text-red-600">*</span>
            </label>
            <input type="number" id="discount_percentage" name="discount_percentage" value="{{ old("discount_percentage", $agent->discount_percentage ?? "0") }}" placeholder="10.0" step="0.1" max="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
            @error("discount_percentage")
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="commission_percentage" class="mb-2 block text-sm font-medium text-slate-700">Commission (%)</label>
            <input type="number" id="commission_percentage" name="commission_percentage" value="{{ old('commission_percentage', $agent->commission_percentage ?? '') }}" placeholder="e.g. 5.00" step="0.01" min="0" max="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            @error('commission_percentage')
                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        @if ($isEdit)
            <div>
                <label for="total_sale" class="mb-2 block text-sm font-medium text-slate-700">
                    Total Sale (RM) <span class="text-red-600">*</span>
                </label>
                <input type="number" id="total_sale" name="total_sale" value="{{ old("total_sale", $agent->total_sale ?? "0") }}" placeholder="0.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                @error("total_sale")
                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
        @endif
    </div>

    <div>
        <div class="mb-2 flex items-center justify-between gap-3">
            <label for="business_site_ids" class="block text-sm font-medium text-slate-700">Assigned business sites</label>
            @if ($businessSites->isNotEmpty())
                <button type="button" onclick="document.getElementById(&quot;business_site_ids&quot;).selectedIndex = -1;" class="text-sm font-semibold text-red-600 transition hover:text-red-700">Clear all</button>
            @endif
        </div>
        @php
            $assignedBusinessSiteIds = old('business_site_ids', $agent?->businessSites?->pluck('id')->all() ?? []);
        @endphp
        <select id="business_site_ids" name="business_site_ids[]" multiple class="min-h-36 w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
            @forelse ($businessSites as $businessSite)
                <option value="{{ $businessSite->id }}" @selected(in_array($businessSite->id, $assignedBusinessSiteIds))>
                    {{ $businessSite->site_name }} · {{ $businessSite->city }}
                </option>
            @empty
                <option disabled>Create a business site first</option>
            @endforelse
        </select>
        <p class="mt-1 text-xs text-slate-500">Hold Ctrl/Command to select multiple sites. To reset the assignment, click Clear all and save changes.</p>
        @error('business_site_ids')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
        @error('business_site_ids.*')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex flex-col gap-3 pt-4 sm:flex-row">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-6 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
<script>
    (function () {
        const picker = document.querySelector('[data-referrer-picker]');
        if (! picker) return;

        const trigger = picker.querySelector('[data-referrer-trigger]');
        const panel = picker.querySelector('[data-referrer-panel]');
        const search = picker.querySelector('[data-referrer-search]');
        const value = picker.querySelector('[data-referrer-value]');
        const label = picker.querySelector('[data-referrer-trigger-label]');
        const avatar = picker.querySelector('[data-referrer-trigger-avatar]');
        const options = Array.from(picker.querySelectorAll('[data-referrer-option]'));
        const empty = picker.querySelector('[data-referrer-empty]');

        function setOpen(open) {
            panel.classList.toggle('hidden', ! open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                search.value = '';
                options.forEach((option) => option.classList.remove('hidden'));
                empty.classList.add('hidden');
                window.setTimeout(() => search.focus(), 0);
            }
        }

        trigger.addEventListener('click', () => setOpen(panel.classList.contains('hidden')));

        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let visible = 0;
            options.forEach((option) => {
                const matches = (option.dataset.search || '').includes(query);
                option.classList.toggle('hidden', ! matches);
                if (matches) visible++;
            });
            empty.classList.toggle('hidden', visible !== 0);
        });

        options.forEach((option) => option.addEventListener('click', () => {
            value.value = option.dataset.value;
            label.textContent = option.dataset.label;
            label.classList.toggle('text-slate-500', option.dataset.value === '');
            label.classList.toggle('text-slate-900', option.dataset.value !== '');
            avatar.innerHTML = option.querySelector('[data-option-avatar]').innerHTML;
            options.forEach((item) => item.querySelector('[data-selected-check]')?.classList.toggle('hidden', item !== option));
            setOpen(false);
        }));

        document.addEventListener('click', (event) => {
            if (! picker.contains(event.target)) setOpen(false);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setOpen(false);
        });
    })();
</script>
