@extends('agent.layouts.app')
@section('title', 'Edit POS Sale | Anugerah3D Agent')
@section('page_title', 'Edit Sale')
@section('back_url', route('agent.pos.index', ['tab' => 'history']))
@section('content')
<div class="space-y-5" data-pos-root>
    <section class="rounded-3xl bg-[#17324d] p-5 text-white"><p class="text-[10px] font-bold uppercase tracking-wider text-orange-300">{{ $posSale->sale_number }}</p><div class="mt-1 flex items-end justify-between gap-3"><div><h2 class="text-lg font-extrabold">{{ $activeSession->businessSite->site_name }}</h2><p class="text-xs text-slate-300">Active session</p></div><p class="font-mono text-lg font-black" data-pos-timer data-expires-at="{{ $activeSession->expires_at->toIso8601String() }}">01:00:00</p></div></section>
    @include('agent.pos._sale-form', ['action' => route('agent.pos.sales.update', $posSale), 'submitLabel' => 'Save sale changes'])
</div>
@include('agent.pos._script')
@endsection
