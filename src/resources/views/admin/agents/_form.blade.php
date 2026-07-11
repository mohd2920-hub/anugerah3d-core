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

    <div class="flex flex-col gap-3 pt-4 sm:flex-row">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-6 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
