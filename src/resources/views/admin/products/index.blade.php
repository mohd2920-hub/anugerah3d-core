@extends('admin.layouts.app')

@section('title', $products->total().' Products | Anugerah3D Admin')

@section('page_title', $products->total().' Products')

@section('content')
    @php
        $formatAmount = static fn ($value): string => number_format((float) $value, 2);
        $formatOptionalAmount = static fn ($value): string => $value !== null ? number_format((float) $value, 2) : '-';
    @endphp

    <div class="space-y-5">
        {{-- Alert Messages --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Search Section --}}
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by product code or name..." class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                    Search
                </button>
                @if (request('search'))
                    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- Products Table - Desktop --}}
        <div class="hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70 md:block">
            <table class="admin-data-table w-full text-xs">
                <colgroup>
                    <col style="width: 7%;">
                    <col style="width: 22%;">
                    <col style="width: 18%;">
                    <col style="width: 14%;">
                    <col style="width: 8%;">
                    <col style="width: 13%;">
                    <col style="width: 7%;">
                    <col style="width: 11%;">
                </colgroup>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="rounded-tl-lg px-3 py-3 text-left font-semibold text-slate-700">Image</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Product</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Dimension</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Material / Colour</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-700">Balance</th>
                        <th class="px-3 py-3 text-left font-semibold text-slate-700">Cost / Selling</th>
                        <th class="px-3 py-3 text-center font-semibold text-slate-700">Discount</th>
                        <th class="rounded-tr-lg px-3 py-3 text-right font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($products as $product)
                        @php
                            $materialName = $product->materialType?->name ?: ($product->material ?: '-');
                            $colorName = $product->color ?: '-';
                            $safeColor = is_string($product->color) && preg_match('/\A(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]+)\z/', $product->color) ? $product->color : '#cbd5e1';
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-3 py-3">
                                @if ($product->prd_picture)
                                    <div class="relative h-11 w-11 overflow-hidden rounded-md border border-slate-200 bg-slate-100">
                                        <img src="{{ $product->prd_picture }}" alt="{{ $product->prd_name }}" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');">
                                        <div class="hidden h-full w-full items-center justify-center text-[10px] font-semibold text-slate-400">IMG</div>
                                    </div>
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-md border border-dashed border-slate-300 bg-slate-50 text-[10px] font-semibold text-slate-400">IMG</div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="truncate font-mono text-[0.72rem] font-semibold text-slate-900" title="{{ $product->prd_code }}">{{ $product->prd_code }}</div>
                                <div class="mt-1 truncate text-sm font-medium text-slate-900" title="{{ $product->prd_name }}">{{ $product->prd_name }}</div>
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                <div class="font-medium text-slate-900">{{ $formatAmount($product->weight_g) }} g</div>
                                <div class="mt-1 text-[0.72rem] leading-4 text-slate-500">
                                    W {{ $formatAmount($product->width_mm) }} x H {{ $formatAmount($product->height_mm) }}
                                    @if ($product->length_mm !== null)
                                        x L {{ $formatOptionalAmount($product->length_mm) }}
                                    @endif
                                    mm
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-medium text-slate-900">{{ $materialName }}</div>
                                <div class="mt-1 flex min-w-0 items-center gap-2 text-[0.72rem] text-slate-500">
                                    <span class="h-2.5 w-2.5 flex-none rounded-full border border-slate-300" style="background-color: {{ $safeColor }}"></span>
                                    <span class="truncate">{{ $colorName }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">{{ $product->prd_balance }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900">RM {{ $formatAmount($product->cost_rm) }}</div>
                                <div class="mt-1 text-[0.72rem] text-slate-500">Sell RM {{ $formatAmount($product->price_selling) }}</div>
                            </td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $formatAmount($product->agent_discount_default) }}%</td>
                            <td class="relative px-3 py-3 text-right">
                                <div class="relative inline-flex" data-action-menu>
                                    <button type="button" data-action-menu-button class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2" aria-haspopup="menu" aria-expanded="false">
                                        Actions
                                    </button>
                                    <div data-action-menu-panel class="absolute right-0 top-full z-30 mt-2 hidden w-36 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                            Edit
                                        </a>
                                        <button type="button" data-action="{{ route('admin.products.destroy', $product) }}" data-name="{{ $product->prd_name }}" onclick="openDeleteModal(this)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center">
                                <p class="text-slate-600">No products found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Products Cards - Mobile --}}
        <div class="grid gap-3 md:hidden">
            @forelse($products as $product)
                @php
                    $materialName = $product->materialType?->name ?: ($product->material ?: '-');
                    $colorName = $product->color ?: '-';
                    $safeColor = is_string($product->color) && preg_match('/\A(?:#[0-9a-fA-F]{3,8}|[a-zA-Z]+)\z/', $product->color) ? $product->color : '#cbd5e1';
                @endphp
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        @if ($product->prd_picture)
                            <div class="relative h-16 w-16 flex-none overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                <img src="{{ $product->prd_picture }}" alt="{{ $product->prd_name }}" loading="lazy" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');">
                                <div class="hidden h-full w-full items-center justify-center text-[10px] font-semibold text-slate-400">IMG</div>
                            </div>
                        @else
                            <div class="flex h-16 w-16 flex-none items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-[10px] font-semibold text-slate-400">IMG</div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="break-all font-mono text-[0.72rem] font-semibold text-slate-500">{{ $product->prd_code }}</div>
                            <h2 class="mt-0.5 break-words text-base font-semibold text-slate-950">{{ $product->prd_name }}</h2>
                        </div>

                        <div class="relative flex-none" data-action-menu>
                            <button type="button" data-action-menu-button class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2" aria-haspopup="menu" aria-expanded="false">
                                Actions
                            </button>
                            <div data-action-menu-panel class="absolute right-0 top-full z-30 mt-2 hidden w-36 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl">
                                <a href="{{ route('admin.products.edit', $product) }}" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                    Edit
                                </a>
                                <button type="button" data-action="{{ route('admin.products.destroy', $product) }}" data-name="{{ $product->prd_name }}" onclick="openDeleteModal(this)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Dimension</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $formatAmount($product->weight_g) }} g</dd>
                            <dd class="mt-0.5 text-xs text-slate-500">
                                {{ $formatAmount($product->width_mm) }} W x {{ $formatAmount($product->height_mm) }} H
                                @if ($product->length_mm !== null)
                                    x {{ $formatOptionalAmount($product->length_mm) }} L
                                @endif
                                mm
                            </dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Material</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $materialName }}</dd>
                            <dd class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                <span class="h-2.5 w-2.5 flex-none rounded-full border border-slate-300" style="background-color: {{ $safeColor }}"></span>
                                <span class="truncate">{{ $colorName }}</span>
                            </dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Price</dt>
                            <dd class="mt-1 font-semibold text-slate-900">RM {{ $formatAmount($product->price_selling) }}</dd>
                            <dd class="mt-0.5 text-xs text-slate-500">Cost RM {{ $formatAmount($product->cost_rm) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-[0.68rem] font-semibold uppercase text-slate-500">Stock</dt>
                            <dd class="mt-1">
                                <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">{{ $product->prd_balance }}</span>
                            </dd>
                            <dd class="mt-1 text-xs text-slate-500">{{ $formatAmount($product->agent_discount_default) }}% discount</dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-6 text-center text-slate-600 shadow-sm">
                    No products found.
                </div>
            @endforelse
        </div>

        {{-- Delete Password Modal --}}
        <div id="delete-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 py-6">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl ring-1 ring-slate-900/5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Confirm delete</h2>
                        <p class="mt-1 text-sm text-slate-600">To confirm, enter your admin password. This will permanently delete the selected product and cannot be undone.</p>
                    </div>
                    <button type="button" onclick="closeDeleteModal()" class="rounded-full border border-slate-200 bg-white p-2 text-slate-600 transition hover:bg-slate-50">
                        &times;
                    </button>
                </div>

                <form method="POST" action="" class="mt-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_action" id="delete_action" value="{{ old('delete_action') }}">
                    <input type="hidden" name="delete_product_name" id="delete_product_name" value="{{ old('delete_product_name') }}">

                    <p class="text-sm text-slate-700">Delete product: <span data-product-name class="font-medium text-slate-900"></span></p>

                    <div>
                        <label for="delete_password" class="mb-2 block text-sm font-medium text-slate-700">Admin Password</label>
                        <input id="delete_password" name="delete_password" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @if ($errors->has('delete_password'))
                            <span class="mt-1 block text-sm text-red-600">{{ $errors->first('delete_password') }}</span>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeDeleteModal()" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                            Delete product
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $products->links('pagination::tailwind') }}
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('delete-confirm-modal');

            function closeActionMenus() {
                document.querySelectorAll('[data-action-menu-panel]').forEach(function (panel) {
                    panel.classList.add('hidden');
                });

                document.querySelectorAll('[data-action-menu-button]').forEach(function (button) {
                    button.setAttribute('aria-expanded', 'false');
                });
            }

            function setDeleteModal(action, name) {
                const form = modal.querySelector('form');
                const productName = modal.querySelector('[data-product-name]');
                const deleteActionInput = modal.querySelector('[name="delete_action"]');
                const deleteProductNameInput = modal.querySelector('[name="delete_product_name"]');

                form.action = action;
                productName.textContent = name;
                deleteActionInput.value = action;
                deleteProductNameInput.value = name;
            }

            window.openDeleteModal = function (button) {
                closeActionMenus();
                setDeleteModal(button.dataset.action, button.dataset.name);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.querySelector('[name="delete_password"]').value = '';
                modal.querySelector('[name="delete_password"]').focus();
            };

            window.closeDeleteModal = function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('[data-action-menu-button]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.stopPropagation();

                    const menu = button.closest('[data-action-menu]');
                    const panel = menu.querySelector('[data-action-menu-panel]');
                    const shouldOpen = panel.classList.contains('hidden');

                    closeActionMenus();

                    if (shouldOpen) {
                        panel.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (! event.target.closest('[data-action-menu]')) {
                    closeActionMenus();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeActionMenus();
                    window.closeDeleteModal();
                }
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    window.closeDeleteModal();
                }
            });

            const persistedDeleteAction = @json(old('delete_action', ''));
            const persistedDeleteProductName = @json(old('delete_product_name', ''));

            if (persistedDeleteAction && persistedDeleteProductName) {
                setDeleteModal(persistedDeleteAction, persistedDeleteProductName);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.querySelector('[name="delete_password"]').focus();
            }
        })();
    </script>
@endsection
