<section class="min-w-0 flex-1 rounded-lg border border-blue-200 bg-white p-3 text-sm font-normal text-slate-700" aria-label="Diskaun Ejen">
    <h3 class="font-semibold text-blue-800">Diskaun Ejen</h3>
    <p class="mt-1 text-xs text-slate-500">Tetapan semasa · Orders sahaja</p>
    <dl class="mt-2 space-y-1">
        <div class="flex justify-between gap-4"><dt>Bawah RM20</dt><dd class="font-semibold">{{ $discountSetting->below_rm20 }}%</dd></div>
        <div class="flex justify-between gap-4"><dt>RM20 hingga bawah RM100</dt><dd class="font-semibold">{{ $discountSetting->below_rm100 }}%</dd></div>
        <div class="flex justify-between gap-4"><dt>RM100 dan ke atas</dt><dd class="font-semibold">{{ $discountSetting->at_least_rm100 }}%*</dd></div>
    </dl>
    <p class="mt-2 text-xs">*Kadar profil ejen yang lebih tinggi digunakan bagi RM100 ke atas. Jumlah barang sebelum diskaun, tanpa penghantaran.</p>
    @if ($latestDiscountChange = $discountHistory->first())
        <p class="mt-2 text-xs text-slate-500">Terakhir diubah oleh {{ $latestDiscountChange->properties['changed_by'] ?? 'Superadmin' }} pada {{ $latestDiscountChange->created_at->format('d/m/Y H:i') }}.</p>
    @else
        <p class="mt-2 text-xs text-slate-500">Kadar asal · Belum ada perubahan.</p>
    @endif
    @if (auth('admin')->user()->isSuperAdmin() && $discountSetting->exists)
        <details class="mt-3" data-discount-editor @if ($errors->hasAny(['below_rm20', 'below_rm100', 'at_least_rm100', 'version'])) open @endif>
            <summary class="cursor-pointer font-semibold text-blue-700">Ubah Diskaun</summary>
            <form method="POST" action="{{ route('admin.orders.discount-settings.update') }}" class="mt-3 space-y-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="version" value="{{ old('version', $discountSetting->version) }}">
                @foreach (['below_rm20' => 'Bawah RM20', 'below_rm100' => 'RM20 hingga bawah RM100', 'at_least_rm100' => 'RM100 dan ke atas'] as $field => $label)
                    <label class="block text-xs font-semibold">{{ $label }} (%)
                        <input name="{{ $field }}" type="number" min="0" max="100" step="0.1" required value="{{ old($field, $discountSetting->$field) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    @error($field)<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                @endforeach
                @error('version')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                <p class="text-xs text-slate-500">Berkuat kuasa selepas disimpan untuk pesanan baharu. Pesanan lama dan tetapan POS kekal.</p>
                <div class="flex gap-3">
                    <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white" type="submit">Simpan</button>
                    <button class="rounded-lg border border-slate-300 px-4 py-2" type="button" data-discount-cancel>Batal</button>
                </div>
            </form>
        </details>
    @endif
    @if ($discountHistory->isNotEmpty())
        <details class="mt-3">
            <summary class="cursor-pointer text-xs font-semibold text-blue-700">Sejarah perubahan (10 terkini)</summary>
            <ol class="mt-2 space-y-3 text-xs">
                @foreach ($discountHistory as $change)
                    <li class="border-t border-slate-100 pt-2">
                        <p>{{ $change->properties['changed_by'] ?? 'Superadmin' }} · {{ $change->created_at->format('d/m/Y H:i') }}</p>
                        @foreach (['below_rm20' => 'Bawah RM20', 'below_rm100' => 'RM20–<RM100', 'at_least_rm100' => 'RM100 ke atas'] as $field => $label)
                            <p>{{ $label }}: {{ data_get($change->properties, 'before.'.$field) }}% → {{ data_get($change->properties, 'after.'.$field) }}%</p>
                        @endforeach
                    </li>
                @endforeach
            </ol>
        </details>
    @endif
</section>
