@extends('agent.layouts.guest')

@section('title', 'Register as Agent | Anugerah3D')

@section('content')
@php
    $referrerProfileUrl = $referrer->profile_picture
        ? (filter_var($referrer->profile_picture, FILTER_VALIDATE_URL) ? $referrer->profile_picture : asset($referrer->profile_picture))
        : null;
@endphp
<main class="min-h-screen bg-[#eef3f6] px-4 py-7 sm:py-10">
    <section class="mx-auto w-full max-w-2xl overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/10">
        <header class="relative overflow-hidden bg-[linear-gradient(145deg,#17324d,#285875)] px-6 pb-10 pt-7 text-white sm:px-9">
            <div class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-[#e7682b]/30 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-xs font-black text-[#17324d]">A3D</span>
                    <div><p class="font-bold">Anugerah3D</p><p class="text-xs text-slate-300">Agent opportunity</p></div>
                </div>
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.2em] text-orange-300">Grow with us</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Register as agent</h1>
                <p class="mt-3 max-w-lg text-sm leading-6 text-slate-300">Submit your details below. Our admin team will review your registration before activating your account.</p>

                <div class="mt-6 flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 p-3 backdrop-blur">
                    @if ($referrerProfileUrl)
                        <img src="{{ $referrerProfileUrl }}" alt="{{ $referrer->agt_name }}" class="h-12 w-12 rounded-full border-2 border-white/70 object-cover">
                    @else
                        <span class="grid h-12 w-12 place-items-center rounded-full bg-orange-300 text-sm font-black text-[#17324d]">{{ $referrer->initials() }}</span>
                    @endif
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-300">Introduced by</p><p class="mt-0.5 font-extrabold">Referrer: {{ $referrer->agt_name }}</p></div>
                    <svg class="ml-auto h-6 w-6 text-orange-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>
                </div>
            </div>
        </header>

        <div class="relative -mt-5 rounded-t-[2rem] bg-white px-6 pb-8 pt-8 sm:px-9">
            @if (session('registration_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-emerald-700"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg></span>
                    <h2 class="mt-3 text-lg font-extrabold text-emerald-900">Registration received</h2>
                    <p class="mt-1 text-sm leading-6 text-emerald-800">Your application is marked as new and is waiting for admin approval.</p>
                </div>
            @else
                <form method="POST" action="{{ $submissionUrl }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Full name <span class="text-red-500">*</span></span>
                        <input name="agt_name" value="{{ old('agt_name') }}" autocomplete="name" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        @error('agt_name')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">WhatsApp number <span class="text-red-500">*</span></span>
                            <input name="phone_number" value="{{ old('phone_number') }}" type="tel" inputmode="tel" autocomplete="tel" placeholder="e.g. 0123456789" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                            @error('phone_number')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Confirm phone number <span class="text-red-500">*</span></span>
                            <input name="phone_number_confirmation" value="{{ old('phone_number_confirmation') }}" type="tel" inputmode="tel" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        </label>
                    </div>
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Email <span class="text-red-500">*</span></span>
                        <input name="email" value="{{ old('email') }}" type="email" autocomplete="email" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                        @error('email')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">City <span class="text-red-500">*</span></span>
                            <input name="city" value="{{ old('city') }}" autocomplete="address-level2" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                            @error('city')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">State <span class="text-red-500">*</span></span>
                            <select name="state" autocomplete="address-level1" required class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none transition focus:border-[#e7682b] focus:bg-white focus:ring-4 focus:ring-orange-100">
                                <option value="">Select state</option>
                                @foreach ($states as $state)<option value="{{ $state->name }}" @selected(old('state') === $state->name)>{{ $state->name }}</option>@endforeach
                            </select>
                            @error('state')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label class="block rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                        <span class="block text-sm font-bold text-slate-700">Profile picture <span class="font-normal text-slate-400">(optional)</span></span>
                        <span class="mt-1 block text-xs text-slate-500">JPG, PNG or WebP. Maximum 5 MB.</span>
                        <input name="profile_picture_file" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#17324d] file:px-4 file:py-2 file:font-bold file:text-white">
                        @error('profile_picture_file')<span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit" class="flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-[#e7682b] px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-600/20 transition active:scale-[0.99]">Submit registration <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-5-5 5 5-5 5"/></svg></button>
                    <p class="text-center text-xs leading-5 text-slate-400">Admin approval is required before the agent account becomes active.</p>
                </form>
            @endif
        </div>
    </section>
</main>
@endsection
