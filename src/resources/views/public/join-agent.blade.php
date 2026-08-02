@extends('public.layouts.app')

@section('title', 'Join Anugerah3D as an Agent')
@section('description', 'Register as an Anugerah3D agent through your referrer invitation.')

@section('content')
@php
    $referrerPictureUrl = $referrer->profile_picture
        ? (filter_var($referrer->profile_picture, FILTER_VALIDATE_URL) ? $referrer->profile_picture : asset(ltrim($referrer->profile_picture, '/')))
        : null;
@endphp
<main class="relative overflow-hidden bg-[#eef3f6] px-4 py-10 sm:px-6 sm:py-16">
    <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>
    <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-orange-200/40 blur-3xl"></div>

    <section class="relative mx-auto grid w-full max-w-6xl overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-[0.85fr_1.15fr]">
        <aside class="relative overflow-hidden bg-[linear-gradient(145deg,#17324d,#285875)] p-7 text-white sm:p-10">
            <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#e7682b]/30 blur-3xl"></div>
            <div class="relative">
                <img src="{{ asset('images/anugerah3d-logo.png') }}" alt="Anugerah3D" class="h-16 w-16 rounded-2xl border-2 border-white/70 object-cover shadow-lg">
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.2em] text-orange-300">Grow together</p>
                <h1 class="mt-3 text-3xl font-black leading-tight sm:text-4xl">Turn creativity into opportunity.</h1>
                <p class="mt-4 text-sm leading-7 text-slate-300">Join the Anugerah3D agent community, share creative 3D products and grow your income with friendly support along the way.</p>

                <div class="mt-8 grid gap-3 text-sm">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-orange-300 font-black text-[#17324d]">1</span><span>Simple online registration</span></div>
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-orange-300 font-black text-[#17324d]">2</span><span>Admin reviews your application</span></div>
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-orange-300 font-black text-[#17324d]">3</span><span>Start sharing after approval</span></div>
                </div>

                <div class="mt-8 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-300">Your referrer</p>
                    <div class="mt-3 flex items-center gap-3">
                        @if ($referrerPictureUrl)
                            <img src="{{ $referrerPictureUrl }}" alt="{{ $referrer->agt_name }}" class="h-14 w-14 rounded-full border-2 border-white/70 object-cover">
                        @else
                            <span class="grid h-14 w-14 place-items-center rounded-full bg-orange-300 text-sm font-black text-[#17324d]">{{ $referrer->initials() }}</span>
                        @endif
                        <div class="min-w-0"><p class="truncate font-extrabold">{{ $referrer->agt_name }}</p><p class="mt-1 text-xs text-slate-300">{{ $referrer->phone_number ?: 'Anugerah3D agent' }}</p></div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="p-6 sm:p-10">
            @if (session('registration_success'))
                <div class="flex min-h-[520px] items-center justify-center">
                    <div class="max-w-md text-center">
                        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100 text-emerald-700"><svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg></span>
                        <p class="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Application received</p>
                        <h2 class="mt-2 text-2xl font-black text-[#17324d]">Welcome to the journey!</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-500">Your application is pending admin approval. We have emailed your agent information, login ID and temporary password to you.</p>
                        <p class="mt-3 text-xs leading-6 text-slate-400">You can sign in after the administrator approves your account.</p>
                        <a href="{{ config('domains.public_url', 'https://anugerah3d.com') }}" class="mt-7 inline-flex h-12 items-center justify-center rounded-xl bg-[#17324d] px-6 text-sm font-extrabold text-white">Back to Anugerah3D</a>
                    </div>
                </div>
            @else
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#e7682b]">Agent registration</p>
                <h2 class="mt-2 text-2xl font-black text-[#17324d]">Let's get to know you</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Complete the simple form below. Choose the login ID you want to use when your account is approved.</p>

                <form method="POST" action="{{ $submissionUrl }}" enctype="multipart/form-data" class="mt-7 space-y-5">
                    @csrf
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Login ID <span class="text-red-500">*</span></span>
                        <input id="agent_registration_login" name="login_id" value="{{ old('login_id') }}" type="text" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="choose your login id" required data-availability-url="{{ $loginAvailabilityUrl }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 lowercase outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        <span class="mt-1.5 block text-xs font-semibold text-slate-500" data-login-id-feedback>Use this login ID to sign in after approval.</span>
                        @error('login_id')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Full name <span class="text-red-500">*</span></span>
                        <input name="agt_name" value="{{ old('agt_name') }}" autocomplete="name" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        @error('agt_name')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Profile picture <span class="text-red-500">*</span></span>
                        <input id="agent_registration_picture" name="profile_picture_file" type="file" accept="image/jpeg,image/png,image/webp" capture="user" required class="sr-only" data-profile-picture-input>
                        <span class="flex cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-orange-300 bg-orange-50/70 p-4 transition hover:border-[#e7682b] hover:bg-orange-50">
                            <span class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white text-[#e7682b] shadow-sm">
                                <img alt="Profile picture preview" class="hidden h-full w-full object-cover" data-profile-picture-preview>
                                <span data-profile-picture-placeholder>
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.1l1.2-1.5h6.4L16.4 6h1.1A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5z"/>
                                        <circle cx="12" cy="12.5" r="3.25"/>
                                    </svg>
                                </span>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-extrabold text-[#17324d]" data-profile-picture-label>Add / take profile picture</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">Tap here to use your camera or choose a clear photo. JPG, PNG or WebP, maximum 5 MB.</span>
                            </span>
                        </span>
                        @error('profile_picture_file')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Email <span class="text-red-500">*</span></span>
                            <input id="agent_registration_email" name="email" value="{{ old('email') }}" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                            @error('email')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">WhatsApp number <span class="text-red-500">*</span></span>
                        <input name="phone_number" value="{{ old('phone_number') }}" type="tel" inputmode="tel" autocomplete="tel" placeholder="e.g. 0123456789" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        @error('phone_number')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Address <span class="text-red-500">*</span></span>
                        <textarea name="address" rows="3" maxlength="250" autocomplete="street-address" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">{{ old('address') }}</textarea>
                        @error('address')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">City <span class="text-red-500">*</span></span>
                            <input name="city" value="{{ old('city') }}" autocomplete="address-level2" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                            @error('city')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">State <span class="text-red-500">*</span></span>
                            <select name="state" autocomplete="address-level1" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                                <option value="">Select state</option>
                                @foreach ($states as $state)<option value="{{ $state->name }}" @selected(old('state') === $state->name)>{{ $state->name }}</option>@endforeach
                            </select>
                            @error('state')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <button type="submit" class="flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-[#e7682b] px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-600/20 transition hover:bg-[#d95d21] active:scale-[0.99]">Submit my application <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-5-5 5 5-5 5"/></svg></button>
                    <p class="text-center text-xs leading-5 text-slate-400">Your account remains pending until an administrator completes the review.</p>
                </form>
            @endif
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const loginInput = document.getElementById('agent_registration_login');
    const loginFeedback = document.querySelector('[data-login-id-feedback]');
    const pictureInput = document.querySelector('[data-profile-picture-input]');
    const picturePreview = document.querySelector('[data-profile-picture-preview]');
    const picturePlaceholder = document.querySelector('[data-profile-picture-placeholder]');
    const pictureLabel = document.querySelector('[data-profile-picture-label]');

    let pictureUrl;
    let availabilityTimer;
    let availabilityRequest;

    const setLoginFeedback = (message, color = '') => {
        if (!loginFeedback) return;

        loginFeedback.textContent = message;
        loginFeedback.style.color = color;
    };

    const checkLoginIdAvailability = () => {
        if (!loginInput) return;

        const value = loginInput.value.trim().toLowerCase();
        const availabilityUrl = loginInput.dataset.availabilityUrl;

        if (loginInput.value !== value) {
            loginInput.value = value;
        }

        if (availabilityRequest) {
            availabilityRequest.abort();
            availabilityRequest = null;
        }

        window.clearTimeout(availabilityTimer);

        if (value === '') {
            loginInput.setCustomValidity('Please enter a login ID.');
            setLoginFeedback('Login ID is required.', '#dc2626');
            return;
        }

        if (!availabilityUrl) {
            loginInput.setCustomValidity('');
            setLoginFeedback('Use this login ID to sign in after approval.', '');
            return;
        }

        loginInput.setCustomValidity('Checking login ID availability.');
        setLoginFeedback('Checking login ID availability...', '#64748b');

        availabilityTimer = window.setTimeout(async () => {
            availabilityRequest = new AbortController();

            try {
                const response = await fetch(availabilityUrl + '?login_id=' + encodeURIComponent(value), {
                    headers: { Accept: 'application/json' },
                    signal: availabilityRequest.signal,
                });
                const data = await response.json();

                if (response.ok && data.available) {
                    loginInput.setCustomValidity('');
                    setLoginFeedback(data.message || 'This login ID is available.', '#15803d');
                    return;
                }

                loginInput.setCustomValidity(data.message || 'This login ID is already taken.');
                setLoginFeedback(data.message || 'This login ID is already taken.', '#dc2626');
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                loginInput.setCustomValidity('Unable to confirm login ID availability right now.');
                setLoginFeedback('Unable to confirm login ID availability right now.', '#dc2626');
            }
        }, 320);
    };

    pictureInput?.addEventListener('change', () => {
        const picture = pictureInput.files?.[0];
        if (!picture || !picturePreview) return;

        if (pictureUrl) URL.revokeObjectURL(pictureUrl);
        pictureUrl = URL.createObjectURL(picture);
        picturePreview.src = pictureUrl;
        picturePreview.classList.remove('hidden');
        picturePlaceholder?.classList.add('hidden');
        if (pictureLabel) pictureLabel.textContent = 'Profile picture ready';
    });

    loginInput?.addEventListener('input', checkLoginIdAvailability);
    loginInput?.addEventListener('blur', () => loginInput.reportValidity());

    if (loginInput?.value.trim()) {
        checkLoginIdAvailability();
    }
})();
</script>
@endpush
