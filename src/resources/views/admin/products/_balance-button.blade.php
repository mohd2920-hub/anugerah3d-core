@adminRoute('admin.products.balance.update')
<button type="button" data-balance-open data-url="{{ route('admin.products.balance.show', $balanceProductId) }}" data-casing="{{ $balanceCasingId ?? '' }}" data-count="{{ $balanceCharacterCount ?? '' }}" class="inline-flex h-8 {{ !empty($balanceButtonLabel) ? 'gap-2 whitespace-nowrap px-3 text-xs font-semibold' : 'w-8' }} shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-white text-blue-700" aria-label="Edit baki stok {{ $balanceProductName }}" title="Edit baki stok">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m16 3 5 5M4 16l-1 5 5-1L21 7a2 2 0 0 0-4-4Z"/></svg>
    @if(!empty($balanceButtonLabel))<span>{{ $balanceButtonLabel }}</span>@endif
</button>
@endadminRoute
