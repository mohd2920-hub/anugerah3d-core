@extends('agent.layouts.app')
@section('title', 'Profile | Anugerah3D Agent')
@section('page_title', 'Profile')

@section('content')
@php
    $profileUrl = $agent->profile_picture
        ? (filter_var($agent->profile_picture, FILTER_VALIDATE_URL) ? $agent->profile_picture : asset($agent->profile_picture))
        : null;
    $initialModal = match (true) {
        $errors->profileUpdate->any() => 'profile-edit-modal',
        $errors->passwordUpdate->any() => 'password-modal',
        $errors->pictureUpdate->any() => 'picture-modal',
        default => null,
    };
    $inputClass = 'mt-1.5 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-orange-300 focus:bg-white focus:ring-4 focus:ring-orange-100';
@endphp

<div class="space-y-5">
    @if (session('success'))
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">
            <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="rounded-[1.75rem] bg-[#17324d] p-5 text-center text-white shadow-xl shadow-slate-900/10">
        <div class="relative mx-auto w-fit">
            <div class="grid h-24 w-24 place-items-center overflow-hidden rounded-[2rem] bg-orange-100 text-2xl font-black text-[#e7682b] ring-4 ring-white/15">
                @if ($profileUrl)
                    <img src="{{ $profileUrl }}" alt="{{ $agent->agt_name }}" class="h-full w-full object-cover">
                @else
                    {{ $agent->initials() }}
                @endif
            </div>
            <button type="button" data-modal-open="picture-modal" class="absolute -bottom-2 -right-2 grid h-10 w-10 place-items-center rounded-full bg-[#f36b2f] text-white shadow-lg ring-4 ring-[#17324d]" aria-label="Change profile picture">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L8 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3l-1.5-2Z"/><circle cx="12" cy="13" r="3"/></svg>
            </button>
        </div>
        <h2 class="mt-5 text-xl font-extrabold">{{ $agent->agt_name }}</h2>
        <p class="mt-1 break-all font-mono text-xs tracking-wider text-slate-300">{{ $agent->email }}</p>
        <span class="mt-4 inline-flex rounded-full bg-emerald-400/15 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-emerald-300">{{ $agent->agt_status }} agent</span>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-extrabold text-[#17324d]">Personal details</h2>
            <button type="button" data-modal-open="profile-edit-modal" class="grid h-10 w-10 place-items-center rounded-full bg-orange-50 text-[#e7682b] transition active:scale-95 active:bg-orange-100" aria-label="Edit personal details">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
            </button>
        </div>
        <dl class="divide-y divide-slate-100 px-5">
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</dt><dd class="mt-1 break-all text-sm font-semibold text-slate-700">{{ $agent->email }}</dd></div>
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Phone number</dt><dd class="mt-1 text-sm font-semibold text-slate-700">{{ $agent->phone_number ?: 'Not provided' }}</dd></div>
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ID number</dt><dd class="mt-1 text-sm font-semibold text-slate-700">{{ $agent->id_number ?: 'Not provided' }}</dd></div>
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Address</dt><dd class="mt-1 text-sm font-semibold leading-6 text-slate-700">{{ $agent->address ?: 'Not provided' }}@if($agent->city || $agent->state)<br>{{ collect([$agent->city, $agent->state])->filter()->implode(', ') }}@endif</dd></div>
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Bank</dt><dd class="mt-1 text-sm font-semibold text-slate-700">{{ $agent->bank_name ?: 'Not provided' }}</dd></div>
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Account name</dt><dd class="mt-1 text-sm font-semibold text-slate-700">{{ $agent->bank_account_name ?: 'Not provided' }}</dd></div>
            <div class="py-4"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Account number</dt><dd class="mt-1 text-sm font-semibold text-slate-700">{{ $agent->bank_account_number ?: 'Not provided' }}</dd></div>
        </dl>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-extrabold text-[#17324d]">Security</h2></div>
        <button type="button" data-modal-open="password-modal" class="flex w-full items-center gap-4 p-5 text-left transition active:bg-slate-50">
            <span class="grid h-11 w-11 flex-none place-items-center rounded-2xl bg-orange-100 text-[#e7682b]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <span class="min-w-0 flex-1"><span class="block text-sm font-extrabold text-[#17324d]">Change password</span><span class="mt-0.5 block text-xs text-slate-500">Use at least 8 characters</span></span>
            <svg class="h-5 w-5 flex-none text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between"><div><p class="text-xs font-semibold text-slate-500">Member since</p><p class="mt-1 font-extrabold text-[#17324d]">{{ $agent->created_at?->format('d M Y') ?: '-' }}</p></div><span class="grid h-11 w-11 place-items-center rounded-2xl bg-orange-100 text-[#e7682b]"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 3 7l9 4 9-4-9-4Z"/><path d="m3 12 9 4 9-4m-18 5 9 4 9-4"/></svg></span></div>
    </section>

    <form method="post" action="{{ route('agent.logout') }}">
        @csrf
        <button class="flex h-13 w-full items-center justify-center gap-2 rounded-2xl border border-red-200 bg-white text-sm font-extrabold text-red-600 shadow-sm transition active:bg-red-50"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/></svg>Sign out</button>
    </form>
</div>

<div id="profile-edit-modal" data-profile-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/55 p-0 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="profile-edit-title">
    <div class="max-h-[92dvh] w-full max-w-xl overflow-y-auto rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white/95 px-5 py-4 backdrop-blur">
            <div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#e7682b]">Your information</p><h2 id="profile-edit-title" class="mt-0.5 text-lg font-extrabold text-[#17324d]">Edit personal details</h2></div>
            <button type="button" data-modal-close class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600" aria-label="Close"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12M6 6l12 12"/></svg></button>
        </div>
        <form method="post" action="{{ route('agent.profile.update') }}" class="space-y-4 p-5">
            @csrf
            @method('PUT')
            <div><label for="agt_name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Full name</label><input id="agt_name" name="agt_name" value="{{ old('agt_name', $agent->agt_name) }}" autocomplete="name" required class="{{ $inputClass }}">@error('agt_name', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="text-xs font-bold uppercase tracking-wider text-slate-500">Email</label><input id="email" type="email" name="email" value="{{ old('email', $agent->email) }}" autocomplete="email" required class="{{ $inputClass }}">@error('email', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><label for="phone_number" class="text-xs font-bold uppercase tracking-wider text-slate-500">Phone number</label><input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number', $agent->phone_number) }}" autocomplete="tel" inputmode="tel" class="{{ $inputClass }}">@error('phone_number', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="id_number" class="text-xs font-bold uppercase tracking-wider text-slate-500">ID number</label><input id="id_number" name="id_number" value="{{ old('id_number', $agent->id_number) }}" class="{{ $inputClass }}">@error('id_number', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div><label for="address" class="text-xs font-bold uppercase tracking-wider text-slate-500">Address</label><textarea id="address" name="address" rows="3" autocomplete="street-address" class="mt-1.5 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-orange-300 focus:bg-white focus:ring-4 focus:ring-orange-100">{{ old('address', $agent->address) }}</textarea>@error('address', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><label for="city" class="text-xs font-bold uppercase tracking-wider text-slate-500">City</label><input id="city" name="city" value="{{ old('city', $agent->city) }}" autocomplete="address-level2" class="{{ $inputClass }}">@error('city', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="state" class="text-xs font-bold uppercase tracking-wider text-slate-500">State</label><select id="state" name="state" autocomplete="address-level1" class="{{ $inputClass }}"><option value="">Select state</option>@foreach ($states as $state)<option value="{{ $state->name }}" @selected(old('state', $agent->state) === $state->name)>{{ $state->name }}</option>@endforeach</select>@error('state', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Payment account details</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label for="bank_name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Bank</label><input id="bank_name" name="bank_name" value="{{ old('bank_name', $agent->bank_name) }}" class="{{ $inputClass }}">@error('bank_name', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="bank_account_name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Account name</label><input id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $agent->bank_account_name) }}" class="{{ $inputClass }}">@error('bank_account_name', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                <div class="mt-4"><label for="bank_account_number" class="text-xs font-bold uppercase tracking-wider text-slate-500">Account number</label><input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $agent->bank_account_number) }}" class="{{ $inputClass }}">@error('bank_account_number', 'profileUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="sticky bottom-0 -mx-5 mt-2 border-t border-slate-100 bg-white px-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4"><button class="h-12 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white shadow-lg shadow-slate-900/15">Save changes</button></div>
        </form>
    </div>
</div>

<div id="password-modal" data-profile-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/55 p-0 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="password-title">
    <div class="w-full max-w-xl rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#e7682b]">Account security</p><h2 id="password-title" class="mt-0.5 text-lg font-extrabold text-[#17324d]">Change password</h2></div><button type="button" data-modal-close class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600" aria-label="Close"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12M6 6l12 12"/></svg></button></div>
        <form method="post" action="{{ route('agent.profile.password.update') }}" class="space-y-4 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
            @csrf
            @method('PUT')
            <div><label for="current_password" class="text-xs font-bold uppercase tracking-wider text-slate-500">Current password</label><div class="relative"><input id="current_password" type="password" name="current_password" autocomplete="current-password" required class="{{ $inputClass }} pr-12"><button type="button" data-password-toggle="current_password" class="absolute right-1 top-[0.625rem] grid h-10 w-10 place-items-center text-slate-400" aria-label="Show current password"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div>@error('current_password', 'passwordUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="password" class="text-xs font-bold uppercase tracking-wider text-slate-500">New password</label><div class="relative"><input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required class="{{ $inputClass }} pr-12"><button type="button" data-password-toggle="password" class="absolute right-1 top-[0.625rem] grid h-10 w-10 place-items-center text-slate-400" aria-label="Show new password"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div>@error('password', 'passwordUpdate')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="text-xs font-bold uppercase tracking-wider text-slate-500">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required class="{{ $inputClass }}"></div>
            <p class="rounded-2xl bg-slate-50 p-3 text-xs leading-5 text-slate-500">After saving, use your new password the next time you sign in.</p>
            <button class="h-12 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white shadow-lg shadow-slate-900/15">Update password</button>
        </form>
    </div>
</div>

<div id="picture-modal" data-profile-modal class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/55 p-0 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="picture-title">
    <div class="w-full max-w-xl rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#e7682b]">Profile photo</p><h2 id="picture-title" class="mt-0.5 text-lg font-extrabold text-[#17324d]">Change your picture</h2></div><button type="button" data-modal-close class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600" aria-label="Close"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12M6 6l12 12"/></svg></button></div>
        <form method="post" action="{{ route('agent.profile.picture.update') }}" enctype="multipart/form-data" class="space-y-5 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
            @csrf
            @method('PUT')
            <div class="mx-auto grid h-32 w-32 place-items-center overflow-hidden rounded-[2rem] bg-orange-100 text-3xl font-black text-[#e7682b] ring-4 ring-orange-50">
                @if ($profileUrl)<img data-picture-preview src="{{ $profileUrl }}" alt="Current profile picture" class="h-full w-full object-cover">@else<span data-picture-initials>{{ $agent->initials() }}</span><img data-picture-preview src="" alt="Selected profile picture" class="hidden h-full w-full object-cover">@endif
            </div>
            <input id="profile_picture_file" type="file" name="profile_picture_file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
            <div class="grid grid-cols-2 gap-3">
                <button type="button" data-picture-source="browse" class="flex h-14 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white text-sm font-extrabold text-[#17324d] shadow-sm"><svg class="h-5 w-5 text-[#e7682b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h5l2 2h11v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M3 7V5a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v2"/></svg>Browse</button>
                <button type="button" data-picture-source="camera" class="flex h-14 items-center justify-center gap-2 rounded-2xl bg-orange-50 text-sm font-extrabold text-[#d95419]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L8 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3l-1.5-2Z"/><circle cx="12" cy="13" r="3"/></svg>Take photo</button>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3 text-center"><p data-picture-name class="text-xs font-semibold text-slate-500">JPG, PNG, WEBP or GIF · Maximum 5 MB</p></div>
            @error('profile_picture_file', 'pictureUpdate')<p class="text-center text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            <button data-picture-submit disabled class="h-12 w-full rounded-2xl bg-[#17324d] text-sm font-extrabold text-white shadow-lg shadow-slate-900/15 transition disabled:cursor-not-allowed disabled:opacity-40">Upload picture</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modals = document.querySelectorAll('[data-profile-modal]');
        const openModal = (id) => {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(() => modal.querySelector('input:not([type="hidden"]), textarea, select, button')?.focus(), 50);
        };
        const closeModal = (modal) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (![...modals].some((item) => !item.classList.contains('hidden'))) document.body.classList.remove('overflow-hidden');
        };

        document.querySelectorAll('[data-modal-open]').forEach((button) => button.addEventListener('click', () => openModal(button.dataset.modalOpen)));
        document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => closeModal(button.closest('[data-profile-modal]'))));
        modals.forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') modals.forEach((modal) => closeModal(modal)); });

        document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        }));

        const pictureInput = document.getElementById('profile_picture_file');
        const picturePreview = document.querySelector('[data-picture-preview]');
        const pictureInitials = document.querySelector('[data-picture-initials]');
        const pictureName = document.querySelector('[data-picture-name]');
        const pictureSubmit = document.querySelector('[data-picture-submit]');

        document.querySelector('[data-picture-source="browse"]')?.addEventListener('click', () => {
            pictureInput.removeAttribute('capture');
            pictureInput.click();
        });
        document.querySelector('[data-picture-source="camera"]')?.addEventListener('click', () => {
            pictureInput.setAttribute('capture', 'user');
            pictureInput.click();
        });
        pictureInput?.addEventListener('change', () => {
            const file = pictureInput.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                picturePreview.src = reader.result;
                picturePreview.classList.remove('hidden');
                pictureInitials?.classList.add('hidden');
            });
            reader.readAsDataURL(file);
            pictureName.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
            pictureSubmit.disabled = false;
        });

        const initialModal = @json($initialModal);
        if (initialModal) openModal(initialModal);
    })();
</script>
@endpush
