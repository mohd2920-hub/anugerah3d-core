@extends('agent.layouts.app')
@section('title', 'My Progress | Anugerah3D Agent')
@section('page_title', 'My progress')

@section('content')
<div class="space-y-5">
    <section class="overflow-hidden rounded-[1.75rem] bg-[linear-gradient(145deg,#17324d,#285875)] p-5 text-white shadow-xl shadow-slate-900/10">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-300">Sales performance</p>
                <p class="mt-3 text-3xl font-black tracking-tight">RM {{ number_format((float) $agent->total_sale, 2) }}</p>
                <p class="mt-1 text-sm text-slate-300">Recorded total sales</p>
            </div>
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-orange-300"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 17 6-6 4 4 8-9"/><path d="M14 6h7v7"/></svg></span>
        </div>
        <div class="mt-7">
            <div class="flex justify-between text-xs font-semibold"><span>Next RM {{ number_format($monthlyTarget, 0) }} milestone</span><span>{{ $progressPercentage }}%</span></div>
            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-[#f28a52]" style="width: {{ $progressPercentage }}%"></div></div>
            <p class="mt-2 text-xs text-slate-300">RM {{ number_format($remainingTarget, 2) }} remaining to reach this milestone.</p>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-orange-100 text-[#e7682b]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
            <p class="mt-4 text-xs font-semibold text-slate-500">Agent discount</p>
            <p class="mt-1 text-xl font-black text-[#17324d]">{{ number_format((float) $agent->discount_percentage, 1) }}%</p>
        </div>
        <a href="{{ route('agent.history') }}" class="rounded-3xl border border-amber-200 bg-amber-50 p-4 shadow-sm transition active:scale-[0.99]">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-100 text-amber-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5M9 13h6M9 17h4"/></svg></span>
            <p class="mt-4 text-xs font-semibold text-slate-500">Pending order</p>
            <p class="mt-1 text-xl font-black text-[#17324d]">{{ $pendingOrderItemCount }} <span class="text-xs font-bold text-slate-500">items</span></p>
        </a>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></span>
            <p class="mt-4 text-xs font-semibold text-slate-500">Account status</p>
            <p class="mt-1 text-xl font-black capitalize text-[#17324d]">{{ $agent->agt_status }}</p>
        </div>
        <a href="{{ route('agent.team.index') }}" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.99]">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-cyan-100 text-cyan-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6m3-3h-6"/></svg></span>
            <p class="mt-4 text-xs font-semibold text-slate-500">My team</p>
            <p class="mt-1 text-lg font-black text-[#17324d]">{{ number_format($teamAgentCount) }} <span class="text-xs font-bold text-slate-500">agents</span></p>
            <p class="text-xs font-semibold text-slate-500">RM {{ number_format($teamSalesTotal, 2) }} ({{ number_format($teamOrderCount) }} orders)</p>
            <p class="mt-1 text-[11px] font-bold text-cyan-700">Bonus rate: Tier 1 {{ number_format((float) ($agent->tier1_percentage ?? 7), 2) }}% • Tier 2 {{ number_format((float) ($agent->tier2_percentage ?? 3), 2) }}%</p>
        </a>
    </section>

    <section class="relative overflow-hidden rounded-[1.75rem] border border-orange-200 bg-[linear-gradient(135deg,#fff7ed,#fffbeb)] p-5 shadow-sm" data-referral-card data-invite-message="{{ $referralMessage }}">
        <div class="absolute -right-10 -top-12 h-36 w-36 rounded-full bg-orange-200/60 blur-2xl"></div>
        <div class="relative">
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-[#c6531e]">Referral programme</p><h2 class="mt-1 text-xl font-black text-[#17324d]">Grow your team</h2><p class="mt-2 text-sm leading-6 text-slate-600">Invite someone wonderful to join the Anugerah3D agent community with your personal link.</p></div>
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#e7682b] text-white shadow-md shadow-orange-600/20"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M19 8v6m3-3h-6"/></svg></span>
            </div>

            <div class="mt-5 rounded-2xl border border-orange-200 bg-white/90 p-4">
                <div class="flex items-center justify-between gap-3"><span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Your referral link</span><span class="rounded-full bg-orange-100 px-2.5 py-1 font-mono text-[10px] font-black text-[#c6531e]">{{ $agent->referral_code }}</span></div>
                <input id="agent_referral_url" value="{{ $referralUrl }}" readonly class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-[#17324d] outline-none">
                <button type="button" data-copy-referral class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#17324d] px-4 text-xs font-extrabold text-white"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><span data-copy-referral-label>Copy invitation message</span></button>
                <p class="mt-2 hidden text-center text-xs font-bold text-emerald-700" data-referral-feedback></p>
            </div>

            <form class="mt-4" data-referral-whatsapp-form>
                <label for="referral_phone" class="text-xs font-bold uppercase tracking-wider text-slate-500">Send directly to WhatsApp</label>
                <div class="mt-2 flex gap-2">
                    <input id="referral_phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="e.g. 0123456789" required class="h-12 min-w-0 flex-1 rounded-xl border border-orange-200 bg-white px-4 text-sm outline-none focus:border-[#e7682b] focus:ring-4 focus:ring-orange-100">
                    <button type="submit" class="h-12 rounded-xl bg-[#16a34a] px-5 text-sm font-extrabold text-white shadow-sm">Send</button>
                </div>
                <p class="mt-2 text-xs text-slate-500">WhatsApp will open with a friendly invitation and your referral link ready to send.</p>
                <p class="mt-2 hidden text-xs font-semibold text-red-600" data-referral-phone-error>Please enter a valid WhatsApp number.</p>
            </form>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cyan-100 text-cyan-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M12 7v5l3 2"/></svg></span>
            <div><h2 class="font-extrabold text-[#17324d]">Progress updates</h2><p class="text-xs text-slate-500">Your latest account information</p></div>
        </div>
        <div class="mt-5 space-y-4 border-l-2 border-slate-100 pl-5">
            <div class="relative"><span class="absolute -left-[25px] top-1 h-2 w-2 rounded-full bg-[#e7682b] ring-4 ring-orange-50"></span><p class="text-sm font-bold text-slate-700">Last platform login</p><p class="mt-1 text-xs text-slate-500">{{ $agent->last_login_at?->format('d M Y, h:i A') ?: 'This is your first recorded login.' }}</p></div>
            <div class="relative"><span class="absolute -left-[25px] top-1 h-2 w-2 rounded-full bg-cyan-600 ring-4 ring-cyan-50"></span><p class="text-sm font-bold text-slate-700">Sales record is synced</p><p class="mt-1 text-xs text-slate-500">Values shown are managed by Anugerah3D operations.</p></div>
        </div>
    </section>
</div>
@push('scripts')
<script>
(() => {
    const card = document.querySelector('[data-referral-card]');
    if (!card) return;

    const message = card.dataset.inviteMessage || '';
    const feedback = card.querySelector('[data-referral-feedback]');
    const copyLabel = card.querySelector('[data-copy-referral-label]');

    const copyText = async (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
    };

    card.querySelector('[data-copy-referral]')?.addEventListener('click', async () => {
        await copyText(message);
        if (copyLabel) copyLabel.textContent = 'Invitation copied!';
        if (feedback) {
            feedback.textContent = 'Friendly message and referral link copied. Paste it into WhatsApp.';
            feedback.classList.remove('hidden');
        }
        window.setTimeout(() => {
            if (copyLabel) copyLabel.textContent = 'Copy invitation message';
        }, 2500);
    });

    card.querySelector('[data-referral-whatsapp-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const phoneInput = card.querySelector('#referral_phone');
        const error = card.querySelector('[data-referral-phone-error]');
        let phone = (phoneInput?.value || '').replace(/\D/g, '');

        if (phone.startsWith('0')) phone = '60' + phone.slice(1);
        if (phone.length < 8) {
            error?.classList.remove('hidden');
            phoneInput?.focus();
            return;
        }

        error?.classList.add('hidden');
        window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(message), '_blank', 'noopener');
    });
})();
</script>
@endpush

@endsection
