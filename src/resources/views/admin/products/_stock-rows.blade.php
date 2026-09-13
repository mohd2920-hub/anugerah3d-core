<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4"><h2 class="text-lg font-bold text-slate-900">{{ ['low' => 'Stok Bawah 5', 'empty' => 'Stok Habis', 'critical' => 'Stok Kritikal', 'healthy' => 'Stok Mencukupi'][$stockFilter] }}</h2><p class="mt-1 text-sm text-slate-500">{{ $stockRows->total() }} item / variasi · Disusun daripada baki terendah</p></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-600"><tr><th class="p-4">Produk</th><th class="p-4">Casing</th><th class="p-4">Huruf</th><th class="p-4">Baki</th><th class="p-4">Status</th><th class="p-4 text-right">Tindakan</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($stockRows as $row)
                <tr @class(['bg-red-50/70' => $row->balance <= 0, 'bg-amber-50/70' => $row->balance > 0 && $row->balance < 5])>
                    <td class="p-4"><div class="flex min-w-44 items-center gap-3">@if($row->image_path)<img src="{{ filter_var($row->image_path, FILTER_VALIDATE_URL) ? $row->image_path : asset($row->image_path) }}" alt="" loading="lazy" class="h-12 w-12 shrink-0 rounded-lg object-contain">@endif<div><a href="{{ route('admin.products.show', $row->product_id) }}" class="font-bold text-slate-900 hover:underline">{{ $row->prd_name }}</a><p class="text-xs text-slate-500">{{ $row->prd_code }}</p>@if(!$row->is_visible_to_agents)<span class="text-xs text-slate-500">Tersembunyi</span>@endif</div></div></td>
                    <td class="p-4">{{ $row->casing_name ?: '—' }}</td><td class="p-4">{{ $row->character_count ?? '—' }}</td>
                    <td class="p-4 text-lg font-extrabold {{ $row->balance <= 0 ? 'text-red-700' : ($row->balance < 5 ? 'text-amber-700' : 'text-emerald-700') }}"><span class="inline-flex items-center gap-2">{{ number_format($row->balance) }}
                        @include('admin.products._balance-button', ['balanceProductId' => $row->product_id, 'balanceProductName' => $row->prd_name, 'balanceCasingId' => $row->casing_id, 'balanceCharacterCount' => $row->character_count, 'balanceButtonLabel' => $stockFilter === 'empty' ? 'Kemas Kini Baki' : null])
                    </span></td>
                    <td class="p-4"><span class="whitespace-nowrap rounded-lg px-2 py-1 text-xs font-bold {{ $row->balance <= 0 ? 'bg-red-100 text-red-800' : ($row->balance < 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">{{ $row->balance <= 0 ? 'Stok habis' : ($row->balance < 5 ? 'Kritikal' : 'Mencukupi') }}</span></td>
                    <td class="p-4 text-right">@adminRoute('admin.products.edit')<a href="{{ route('admin.products.edit', $row->product_id) }}{{ $row->casing_id ? '#clicker-casing-'.$row->casing_id : '' }}" class="inline-flex whitespace-nowrap rounded-lg border border-blue-200 bg-white px-3 py-2 font-semibold text-blue-700 hover:bg-blue-50">Urus Stok</a>@else<span class="text-xs text-slate-500">Lihat sahaja</span>@endadminRoute</td>
                </tr>
            @empty<tr><td colspan="6" class="p-8 text-center text-slate-500">Tiada item untuk penapis ini.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 p-4">{{ $stockRows->links() }}</div>
    <p class="bg-blue-50 p-4 text-sm text-blue-800">Clicker: baki dikira mengikut casing + bilangan huruf. Pelarasan stok masih memerlukan sebab pada halaman produk.</p>
</section>
