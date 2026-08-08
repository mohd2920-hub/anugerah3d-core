@extends("admin.layouts.app")

@section("title", "Add Product | Anugerah3D Admin")

@section("page_title", "Add Product")

@section("content")
    @php
        $productVariant = old("product_type", old("product_variant_ui", "standard"));
    @endphp

    <div class="max-w-4xl">
        <div class="rounded-lg bg-white p-6 shadow-sm">
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

                <section data-clicker-product-builder class="rounded-xl border border-slate-200 bg-slate-50 p-4">
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
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Casing</h3>
                                        <p class="mt-1 text-xs text-slate-500">Upload/select image max 10 images. Cut to 600px only.</p>
                                    </div>
                                    <span data-clicker-file-count="casing" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0 / 10</span>
                                </div>

                                <label class="mt-4 flex cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-[#1a73e8] hover:bg-blue-50">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">Upload / Select image</span>
                                        <span class="mt-1 block text-xs text-slate-500">PNG, JPG, WEBP</span>
                                    </span>
                                    <input data-clicker-file-input="casing" type="file" name="clicker_casing_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple class="sr-only">
                                </label>

                                <p data-clicker-file-error="casing" class="mt-2 hidden text-sm font-medium text-red-600"></p>

                                <p class="mt-3 text-xs text-slate-500">Selected files</p>
                                <div data-clicker-file-list="casing" class="mt-2 flex min-h-11 flex-wrap gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-500">
                                    <span>No images selected yet.</span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Huruf</h3>
                                        <p class="mt-1 text-xs text-slate-500">Upload/select image max 10 images. Cut to 600px only.</p>
                                    </div>
                                    <span data-clicker-file-count="huruf" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0 / 10</span>
                                </div>

                                <label class="mt-4 flex cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-[#1a73e8] hover:bg-blue-50">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">Upload / Select image</span>
                                        <span class="mt-1 block text-xs text-slate-500">PNG, JPG, WEBP</span>
                                    </span>
                                    <input data-clicker-file-input="huruf" type="file" name="clicker_huruf_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple class="sr-only">
                                </label>

                                <p data-clicker-file-error="huruf" class="mt-2 hidden text-sm font-medium text-red-600"></p>

                                <p class="mt-3 text-xs text-slate-500">Selected files</p>
                                <div data-clicker-file-list="huruf" class="mt-2 flex min-h-11 flex-wrap gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-500">
                                    <span>No images selected yet.</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Character Pricing</h3>
                                <p class="mt-1 text-xs text-slate-500">Setiap character ada harga masing-masing.</p>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach (range(1, 8) as $characterCount)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700">{{ $characterCount }}</span>

                                            <div class="min-w-0 flex-1">
                                                <input
                                                    type="number"
                                                    name="clicker_character_prices[{{ $characterCount }}]"
                                                    value="{{ old("clicker_character_prices.".$characterCount) }}"
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-center text-sm text-slate-900 outline-none transition placeholder:text-center placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Weight --}}
                <div>
                    <label for="weight_g" class="block text-sm font-medium text-slate-700 mb-2">
                        Weight (grams) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="weight_g" name="weight_g" value="{{ old("weight_g") }}" placeholder="10" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
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
                        <input type="number" id="width_mm" name="width_mm" value="{{ old("width_mm") }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("width_mm")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="height_mm" class="block text-sm font-medium text-slate-700 mb-2">
                            Height (mm) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="height_mm" name="height_mm" value="{{ old("height_mm") }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
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

                {{-- Product Balance --}}
                <div>
                    <label for="prd_balance" class="block text-sm font-medium text-slate-700 mb-2">
                        Stock Balance <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="prd_balance" name="prd_balance" value="{{ old("prd_balance") }}" placeholder="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error("prd_balance")
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Pricing --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="cost_rm" class="block text-sm font-medium text-slate-700 mb-2">
                            Cost (RM) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="cost_rm" name="cost_rm" value="{{ old("cost_rm") }}" placeholder="5.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("cost_rm")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="price_selling" class="block text-sm font-medium text-slate-700 mb-2">
                            Selling Price (RM) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="price_selling" name="price_selling" value="{{ old("price_selling") }}" placeholder="10.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("price_selling")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
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

                <x-admin.product-image-manager :product="null" />

                {{-- Form Actions --}}
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-6 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                        Create Product
                    </button>
                    <a href="{{ route("admin.products.index") }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
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

                    clickerPanel.classList.toggle("hidden", !isClicker);

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