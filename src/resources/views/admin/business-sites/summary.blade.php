@extends('admin.layouts.app')
@section('title', $businessSite->site_name.' | Sales Summary')
@section('page_title', 'Sales Summary · '.$businessSite->site_name)
@section('content')
<div class="site-workspace space-y-6" data-business-sites-root>
    @include('admin.business-sites._navigation', ['active' => 'summary'])
    <section class="site-hero"><div><p class="site-eyebrow">SALES SUMMARY / {{ $businessSite->city }}</p><h2>{{ $businessSite->site_name }}</h2><p>Ringkasan khusus lokasi ini. Setiap sesi kekal lengkap, termasuk operasi selepas tengah malam.</p></div><a class="site-hero-tag" href="{{ route('admin.business-sites.summaries') }}">Tukar lokasi ↗</a></section>
    <form method="GET" action="{{ route('admin.business-sites.summary', $businessSite) }}" class="site-filter-panel" data-site-report-filter>
        @include('admin.business-sites._filters')
        <button class="site-button mt-4" type="submit">Papar ringkasan</button>
    </form>
    @include('admin.business-sites._period')
    @if ($summary->open_count)<p class="site-live">{{ $summary->open_count }} sesi sedang beroperasi · Angka setakat semakan</p>@endif
    <x-admin.business-site-metrics :summary="$summary" />
    @include('admin.business-sites._operations')
</div>
@endsection
