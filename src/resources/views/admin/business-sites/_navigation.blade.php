<nav class="site-navigation" aria-label="Business site pages">
    @foreach (['control' => ['index', '01', 'Control'], 'statistics' => ['statistics', '02', 'Statistik'], 'summary' => ['summaries', '03', 'Sales Summary']] as $key => [$destination, $number, $label])
        @adminRoute('admin.business-sites.'.$destination)
        <a href="{{ route('admin.business-sites.'.$destination) }}" @class(['site-nav-link', 'is-active' => $active === $key]) @if($active === $key) aria-current="page" @endif><span>{{ $number }}</span>{{ $label }}</a>
        @endadminRoute
    @endforeach
</nav>
