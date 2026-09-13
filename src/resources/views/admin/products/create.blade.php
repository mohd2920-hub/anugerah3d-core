@extends("admin.layouts.app")

@section("title", "Add Product | Anugerah3D Admin")

@section("page_title", "Add Product")

@section("content")
    @php
        $productVariant = old("product_type", old("product_variant_ui", "standard"));
    @endphp

    <div class="max-w-4xl">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            @adminRoute('admin.products.store')
<form method="POST" action="{{ route("admin.products.store") }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Product Code --}}
                <div>
                    <label for="prd_code" class="block text-sm font-medium text-slate-700 mb-2">
                        Product Code <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="prd_code" name="prd_code" value="{{ old("prd_code") }}" placeholder="e.g., HY/prd/1001" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error("prd_code")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Product Name --}}
                <div>
                    <label for="prd_name" class="block text-sm font-medium text-slate-700 mb-2">
                        Product Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="prd_name" name="prd_name" value="{{ old("prd_name") }}" placeholder="e.g., Hyurf 1" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error("prd_name")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <section data-casing-stock data-clicker-product-builder class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Product Type</h2>
                            <p class="mt-1 text-xs text-slate-500">Pilih STANDARD untuk form biasa, atau CLICKER untuk buka paparan UI tambahan.</p>
                        </div>
                    </div>

                    <input data-product-type-input type="hidden" name="product_type" value="{{ $productVariant }}">
                    <input data-product-variant-input type="hidden" name="product_variant_ui" value="{{ $productVariant }}">

                    <div class="mt-4 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                        <button
                            type="button"
                            data-product-variant-button
                            data-variant="standard"
                            aria-pressed="{{ $productVariant === "standard" ? "true" : "false" }}"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $productVariant === "standard" ? "bg-[#1a73e8] text-white shadow-sm" : "text-slate-600 hover:bg-slate-100" }}"
                        >
                            STANDARD
                        </button>
                        <button
                            type="button"
                            data-product-variant-button
                            data-variant="clicker"
                            aria-pressed="{{ $productVariant === "clicker" ? "true" : "false" }}"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $productVariant === "clicker" ? "bg-[#1a73e8] text-white shadow-sm" : "text-slate-600 hover:bg-slate-100" }}"
                        >
                            CLICKER
                        </button>
                    </div>

                    <div data-clicker-panel class="{{ $productVariant === "clicker" ? "" : "hidden" }} mt-5 space-y-5 border-t border-slate-200 pt-5">
                        @if ($casingStockAvailable ?? false)
                                <div class="space-y-2 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm">
                                    @if (isset($product) && ! \App\Support\CasingStock::enabled($product))
                                        <label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="enable_casing_stock" value="1" @checked(old('enable_casing_stock'))> Aktifkan stok mengikut saiz casing</label>
                                        <p class="text-xs text-slate-600">Masukkan stok fizikal sebenar bagi saiz 1–8. Jumlah baharu akan menggantikan baki lama selepas disimpan. Pesanan terbuka perlu diselesaikan dahulu.</p>
                                    @else
                                        <input type="hidden" name="enable_casing_stock" value="1">
                                    @endif
                                    <p>Baki lama: <strong>{{ $product->prd_balance ?? 0 }}</strong> unit → Jumlah baharu: <strong data-casing-stock-total>{{ collect(old("clicker_images.casing", []))->sum(fn ($row) => array_sum($row["stock"] ?? [])) }}</strong> unit</p>
                                    <label class="block text-xs font-semibold">Sebab pelarasan stok<input name="casing_stock_reason" maxlength="500" value="{{ old('casing_stock_reason') }}" placeholder="Wajib apabila stok berubah atau diaktifkan" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm"></label>
                                </div>
                            @endif
                            <div class="grid gap-5 xl:grid-cols-2">
                            <x-admin.clicker-image-manager type="casing" :images="collect()"  :stock-available="$casingStockAvailable ?? false" :stocks="$casingStocks ?? []" />
                            <x-admin.clicker-image-manager type="huruf" :images="collect()" />
                        </div>

                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4">
                            <h3 class="text-sm font-semibold text-slate-900">Result Combination</h3>
                            <p class="mt-1 text-xs text-slate-500">Save this product first. Then add Result images from the Edit Product page by choosing one casing and one huruf.</p>
                        </div>

                        <x-admin.clicker-character-pricing :pricing="$clickerCharacterPrices ?? []" :disabled="$productVariant !== 'clicker'"  :stock-available="$casingStockAvailable ?? false" :stocks="$casingStocks ?? []" />
                    </div>
                </section>

                <div data-standard-product-fields class="{{ $productVariant === 'clicker' ? 'hidden' : '' }} space-y-6">
                {{-- Weight --}}
                <div>
                    <label for="weight_g" class="block text-sm font-medium text-slate-700 mb-2">
                        Weight (grams) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="weight_g" name="weight_g" value="{{ old("weight_g") }}" placeholder="10" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" data-standard-required required>
                    @error("weight_g")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Dimensions --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="width_mm" class="block text-sm font-medium text-slate-700 mb-2">
                            Width (mm) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="width_mm" name="width_mm" value="{{ old("width_mm") }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" data-standard-required required>
                        @error("width_mm")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="height_mm" class="block text-sm font-medium text-slate-700 mb-2">
                            Height (mm) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="height_mm" name="height_mm" value="{{ old("height_mm") }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" data-standard-required required>
                        @error("height_mm")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="length_mm" class="block text-sm font-medium text-slate-700 mb-2">
                        Length (mm)
                    </label>
                    <input type="number" id="length_mm" name="length_mm" value="{{ old("length_mm") }}" placeholder="100" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    @error("length_mm")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="color" class="block text-sm font-medium text-slate-700 mb-2">
                            Color
                        </label>
                        <input type="text" id="color" name="color" value="{{ old("color") }}" placeholder="e.g., Blue" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                        @error("color")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="material_id" class="block text-sm font-medium text-slate-700 mb-2">
                            Material
                        </label>
                        <select id="material_id" name="material_id" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                            <option value="">Select a material</option>
                            @forelse($materials as $material)
                                <option value="{{ $material->id }}" {{ old("material_id") == $material->id ? "selected" : "" }}>{{ $material->name }}</option>
                            @empty
                                <option disabled>No materials available</option>
                            @endforelse
                        </select>
                        @error("material_id")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                {{-- Pricing --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="cost_rm" class="block text-sm font-medium text-slate-700 mb-2">
                            Cost (RM) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="cost_rm" name="cost_rm" value="{{ old("cost_rm") }}" placeholder="5.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" data-standard-required required>
                        @error("cost_rm")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="price_selling" class="block text-sm font-medium text-slate-700 mb-2">
                            Selling Price (RM) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="price_selling" name="price_selling" value="{{ old("price_selling") }}" placeholder="10.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" data-standard-required required>
                        @error("price_selling")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                </div>

                {{-- Agent Discount --}}
                <div>
                    <label for="agent_discount_default" class="block text-sm font-medium text-slate-700 mb-2">
                        Default Agent Discount (%) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="agent_discount_default" name="agent_discount_default" value="{{ old("agent_discount_default") }}" placeholder="15" step="0.01" max="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error("agent_discount_default")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div data-standard-stock class="{{ $productVariant === 'clicker' ? 'hidden' : '' }}">
                    <label for="prd_balance" class="mb-2 block text-sm font-medium text-slate-700">
                        Stock Balance <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="prd_balance" name="prd_balance" @disabled($productVariant === 'clicker') value="{{ old("prd_balance", 0) }}" placeholder="0" min="0" step="1" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error("prd_balance")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <x-admin.product-image-manager :product="null" />

                {{-- Form Actions --}}
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-6 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                        Create Product
                    </button>
                    @adminRoute('admin.products.index')
<a href="{{ route("admin.products.index") }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </a>
@endadminRoute
                </div>
            </form>
@endadminRoute
        </div>
    </div>

    <script>
        (() => {
            const initializeAdminProductClicker = () => {
                const container = document.querySelector("[data-clicker-product-builder]");

                if (!container) {
                    return;
                }

                const variantInput = container.querySelector("[data-product-variant-input]");
                const productTypeInput = container.querySelector("[data-product-type-input]");
                const variantButtons = [...container.querySelectorAll("[data-product-variant-button]")];
                const clickerPanel = container.querySelector("[data-clicker-panel]");
                const clickerInputs = [...clickerPanel.querySelectorAll("input:not([type=hidden])")];
                const fileInputs = [...container.querySelectorAll("[data-clicker-file-input]")];
                const standardFields = container.closest("form").querySelector("[data-standard-product-fields]");
                const standardInputs = [...standardFields.querySelectorAll("input, select")];

                const renderFileSelection = (input) => {
                    const type = input.dataset.clickerFileInput;
                    const count = container.querySelector("[data-clicker-file-count=\"" + type + "\"]");
                    const list = container.querySelector("[data-clicker-file-list=\"" + type + "\"]");
                    const error = container.querySelector("[data-clicker-file-error=\"" + type + "\"]");
                    const files = [...input.files];

                    count.textContent = files.length + " / 10";
                    list.innerHTML = "";

                    if (files.length === 0) {
                        const emptyState = document.createElement("span");
                        emptyState.textContent = "No images selected yet.";
                        list.append(emptyState);
                        error.textContent = "";
                        error.classList.add("hidden");

                        return;
                    }

                    files.forEach((file) => {
                        const item = document.createElement("span");
                        item.className = "inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700";
                        item.textContent = file.name;
                        list.append(item);
                    });

                    error.textContent = "";
                    error.classList.add("hidden");
                };

                const syncVariantUi = () => {
                    const isClicker = variantInput.value === "clicker";

                    productTypeInput.value = variantInput.value;
                        const stockField = container.closest('form').querySelector('[data-standard-stock]');
                        stockField.classList.toggle('hidden', isClicker);
                        stockField.querySelector('input').disabled = isClicker;
                        container.querySelectorAll('[name="enable_casing_stock"]').forEach((input) => { input.disabled = !isClicker; });

                    clickerPanel.classList.toggle("hidden", !isClicker);
                    standardFields.classList.toggle("hidden", isClicker);

                    standardInputs.forEach((input) => {
                        input.disabled = isClicker;
                        input.required = !isClicker && input.hasAttribute("data-standard-required");
                    });

                    variantButtons.forEach((button) => {
                        const active = button.dataset.variant === variantInput.value;

                        button.setAttribute("aria-pressed", active ? "true" : "false");
                        button.classList.toggle("bg-[#1a73e8]", active);
                        button.classList.toggle("text-white", active);
                        button.classList.toggle("shadow-sm", active);
                        button.classList.toggle("text-slate-600", !active);
                        button.classList.toggle("hover:bg-slate-100", !active);
                    });

                    clickerInputs.forEach((input) => {
                        input.disabled = !isClicker;
                    });
                };

                variantButtons.forEach((button) => {
                    button.addEventListener("click", () => {
                        variantInput.value = button.dataset.variant;
                        syncVariantUi();
                    });
                });

                fileInputs.forEach((input) => {
                    renderFileSelection(input);

                    input.addEventListener("change", () => {
                        const type = input.dataset.clickerFileInput;
                        const error = container.querySelector("[data-clicker-file-error=\"" + type + "\"]");

                        if (input.files.length > 10) {
                            input.value = "";
                            error.textContent = "Maximum 10 images only.";
                            error.classList.remove("hidden");
                            renderFileSelection(input);

                            return;
                        }

                        renderFileSelection(input);
                    });
                });

                syncVariantUi();
            };

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initializeAdminProductClicker);
            } else {
                initializeAdminProductClicker();
            }
        })();
    </script>
@endsection