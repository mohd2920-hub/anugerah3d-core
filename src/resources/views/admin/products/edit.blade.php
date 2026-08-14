@extends("admin.layouts.app")

@section("title", ($isReadOnly ?? request()->routeIs("admin.products.show")) ? $product->prd_name." | Product Details | Anugerah3D Admin" : "Edit Product | Anugerah3D Admin")

@section("page_title", ($isReadOnly ?? request()->routeIs("admin.products.show")) ? "Product Details" : "Edit Product")

@section("content")
    @php
        $isReadOnly = $isReadOnly ?? request()->routeIs("admin.products.show");
        $productVariant = old("product_type", $product->product_type ?: "standard");
        $formatAmount = static fn (mixed $value): string => number_format((float) ($value ?? 0), 2);
        $formatMoney = static fn (mixed $value): string => "RM ".number_format((float) ($value ?? 0), 2);
        $resolveImageUrl = static function (?string $path): ?string {
            if (! is_string($path) || trim($path) === "") {
                return null;
            }

            return filter_var($path, FILTER_VALIDATE_URL) ? $path : asset(ltrim($path, "/"));
        };

        $productImages = $product->images ?? collect();
        $clickerImages = $clickerImages ?? collect();
        $mainImagePath = $productImages->first()?->image_path ?: $product->prd_picture;
        $mainImageUrl = $resolveImageUrl($mainImagePath);
        $galleryImages = $productImages
            ->map(fn ($image): array => [
                "url" => $resolveImageUrl($image->image_path),
                "alt" => $image->alt_text ?: $product->prd_name,
            ])
            ->filter(fn (array $image): bool => filled($image["url"]))
            ->values();

        if ($galleryImages->isEmpty() && $mainImageUrl) {
            $galleryImages = collect([["url" => $mainImageUrl, "alt" => $product->prd_name]]);
        }

        $clickerGroups = collect([
            "Casing Images" => collect($clickerImages->get("casing", collect())),
            "Huruf Images" => collect($clickerImages->get("huruf", collect())),
        ])->filter(fn ($images) => $images->isNotEmpty());

        $summary = $summary ?? null;
        $materialName = $product->materialType?->name ?: ($product->material ?: "Not set");
        $typeLabel = $productVariant === "clicker" ? "Clicker" : "Standard";
    @endphp

    @if ($isReadOnly)
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route("admin.products.index") }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a73e8] hover:underline">
                    <span aria-hidden="true">&larr;</span> Back to products
                </a>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $typeLabel }}</span>
                    <a href="{{ route("admin.products.edit", $product) }}" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                        Edit product
                    </a>
                </div>
            </div>

            <section class="rounded-lg bg-[linear-gradient(135deg,#111827,#1e293b)] p-5 text-white shadow-sm">
                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">Product Profile</p>
                        <h2 class="mt-2 text-2xl font-semibold">{{ $product->prd_name }}</h2>
                        <p class="mt-2 font-mono text-sm text-slate-300">{{ $product->prd_code }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-200">
                            <span class="rounded-full bg-white/10 px-3 py-1">Material: {{ $materialName }}</span>
                            <span class="rounded-full bg-white/10 px-3 py-1">Color: {{ $product->color ?: "Not set" }}</span>
                            <span class="rounded-full bg-white/10 px-3 py-1">Discount: {{ $formatAmount($product->agent_discount_default) }}%</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm sm:min-w-[280px]">
                        <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Selling Price</p>
                            <p class="mt-2 text-xl font-semibold">{{ $formatMoney($product->price_selling) }}</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Stock Balance</p>
                            <p class="mt-2 text-xl font-semibold">{{ $summary["stock_balance"] ?? $product->prd_balance }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if ($summary)
                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Sold</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary["total_sold_quantity"] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Order {{ $summary["order_sold_quantity"] }} | POS {{ $summary["pos_sold_quantity"] }}</p>
                    </article>
                    <article class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sales Amount</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $formatMoney($summary["total_sales_amount"]) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Order {{ $formatMoney($summary["order_sales_amount"]) }} | POS {{ $formatMoney($summary["pos_sales_amount"]) }}</p>
                    </article>
                    <article class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gallery Count</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary["gallery_count"] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Product and clicker images</p>
                    </article>
                    <article class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit Margin</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $formatMoney((float) $product->price_selling - (float) $product->cost_rm) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Cost {{ $formatMoney($product->cost_rm) }}</p>
                    </article>
                </section>
            @endif

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div class="space-y-5">
                    <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="font-semibold text-slate-950">Product Overview</h3>
                            <p class="mt-1 text-sm text-slate-500">Read only product information without edit form.</p>
                        </div>
                        <div class="grid gap-6 p-5 lg:grid-cols-[280px_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                @if ($mainImageUrl)
                                    <img src="{{ $mainImageUrl }}" alt="{{ $product->prd_name }}" class="aspect-square h-full w-full object-cover">
                                @else
                                    <div class="grid aspect-square place-items-center text-slate-300">
                                        <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m4 16 4-4 4 4 3-3 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                    </div>
                                @endif
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Product Code</p><p class="mt-1 font-mono text-sm text-slate-800">{{ $product->prd_code }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Product Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $typeLabel }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Weight</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $formatAmount($product->weight_g) }} g</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dimensions</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $formatAmount($product->width_mm) }} W x {{ $formatAmount($product->height_mm) }} H @if ($product->length_mm !== null) x {{ $formatAmount($product->length_mm) }} L @endif mm</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Material</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $materialName }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Color</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $product->color ?: "Not set" }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $product->created_at?->format("d M Y, h:i A") ?: "-" }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $product->updated_at?->format("d M Y, h:i A") ?: "-" }}</p></div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="font-semibold text-slate-950">Product Gallery</h3>
                            <p class="mt-1 text-sm text-slate-500">All main and related product images.</p>
                        </div>
                        <div class="p-5">
                            @if ($galleryImages->isNotEmpty())
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach ($galleryImages as $image)
                                        <a href="{{ $image["url"] }}" target="_blank" rel="noopener" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                            <img src="{{ $image["url"] }}" alt="{{ $image["alt"] }}" class="aspect-square h-full w-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-slate-500">No product images available.</p>
                            @endif
                        </div>
                    </section>

                    @if ($clickerGroups->isNotEmpty())
                        <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                            <div class="border-b border-slate-200 px-5 py-4">
                                <h3 class="font-semibold text-slate-950">Clicker Images</h3>
                                <p class="mt-1 text-sm text-slate-500">Grouped clicker assets displayed clearly.</p>
                            </div>
                            <div class="space-y-6 p-5">
                                @foreach ($clickerGroups as $label => $images)
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900">{{ $label }}</h4>
                                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                            @foreach ($images as $image)
                                                @php
                                                    $clickerUrl = $resolveImageUrl($image->image_path ?? null);
                                                @endphp
                                                @if ($clickerUrl)
                                                    <a href="{{ $clickerUrl }}" target="_blank" rel="noopener" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                        <img src="{{ $clickerUrl }}" alt="{{ $product->prd_name }} {{ $label }}" class="aspect-square h-full w-full object-cover">
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($productVariant === "clicker")
                        <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                            <div class="border-b border-slate-200 px-5 py-4">
                                <h3 class="font-semibold text-slate-950">Character Pricing</h3>
                                <p class="mt-1 text-sm text-slate-500">Price by character count for clicker products.</p>
                            </div>
                            <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach (range(1, 8) as $characterCount)
                                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $characterCount }} Character</p>
                                        <p class="mt-2 text-xl font-semibold text-slate-950">{{ $formatMoney($clickerCharacterPrices[$characterCount] ?? 0) }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="space-y-5">
                    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <h3 class="font-semibold text-slate-950">Pricing</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Cost Price</dt><dd class="mt-1 font-medium text-slate-900">{{ $formatMoney($product->cost_rm) }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Selling Price</dt><dd class="mt-1 font-medium text-slate-900">{{ $formatMoney($product->price_selling) }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Agent Discount</dt><dd class="mt-1 font-medium text-slate-900">{{ $formatAmount($product->agent_discount_default) }}%</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Gross Unit Margin</dt><dd class="mt-1 font-medium text-emerald-700">{{ $formatMoney((float) $product->price_selling - (float) $product->cost_rm) }}</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <h3 class="font-semibold text-slate-950">Inventory And Sales</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Stock Balance</dt><dd class="mt-1 font-medium text-slate-900">{{ $summary["stock_balance"] ?? $product->prd_balance }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Order Sales</dt><dd class="mt-1 font-medium text-slate-900">{{ $summary["order_sold_quantity"] ?? 0 }} units | {{ $formatMoney($summary["order_sales_amount"] ?? 0) }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">POS Sales</dt><dd class="mt-1 font-medium text-slate-900">{{ $summary["pos_sold_quantity"] ?? 0 }} units | {{ $formatMoney($summary["pos_sales_amount"] ?? 0) }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Total Sales</dt><dd class="mt-1 font-medium text-slate-900">{{ $summary["total_sold_quantity"] ?? 0 }} units | {{ $formatMoney($summary["total_sales_amount"] ?? 0) }}</dd></div>
                        </dl>
                    </section>
                </aside>
            </div>
        </div>
    @else
        <div class="max-w-4xl">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route("admin.products.update", $product) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method("PUT")
                    <input type="hidden" name="product_type" value="{{ $productVariant }}">

                    <div>
                        <label for="prd_code" class="mb-2 block text-sm font-medium text-slate-700">
                            Product Code <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="prd_code" name="prd_code" value="{{ old("prd_code", $product->prd_code) }}" placeholder="e.g., HY/prd/1001" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("prd_code")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="prd_name" class="mb-2 block text-sm font-medium text-slate-700">
                            Product Name <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="prd_name" name="prd_name" value="{{ old("prd_name", $product->prd_name) }}" placeholder="e.g., Hyurf 1" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("prd_name")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($productVariant === "clicker")
                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Character Pricing</h3>
                                <p class="mt-1 text-xs text-slate-500">Setiap character ada harga masing masing.</p>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach (range(1, 8) as $characterCount)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-slate-50 text-sm font-semibold text-slate-700">{{ $characterCount }}</span>

                                            <div class="min-w-0 flex-1">
                                                <input
                                                    type="number"
                                                    name="clicker_character_prices[{{ $characterCount }}]"
                                                    value="{{ old("clicker_character_prices.".$characterCount, $clickerCharacterPrices[$characterCount] ?? "") }}"
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-center text-sm text-slate-900 outline-none transition placeholder:text-center placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @error("clicker_character_prices")
                                <span class="mt-3 block text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </section>
                    @endif

                    <div>
                        <label for="weight_g" class="mb-2 block text-sm font-medium text-slate-700">
                            Weight (grams) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="weight_g" name="weight_g" value="{{ old("weight_g", $product->weight_g) }}" placeholder="10" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("weight_g")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="width_mm" class="mb-2 block text-sm font-medium text-slate-700">
                                Width (mm) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" id="width_mm" name="width_mm" value="{{ old("width_mm", $product->width_mm) }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                            @error("width_mm")
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="height_mm" class="mb-2 block text-sm font-medium text-slate-700">
                                Height (mm) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" id="height_mm" name="height_mm" value="{{ old("height_mm", $product->height_mm) }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                            @error("height_mm")
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="length_mm" class="mb-2 block text-sm font-medium text-slate-700">
                            Length (mm)
                        </label>
                        <input type="number" id="length_mm" name="length_mm" value="{{ old("length_mm", $product->length_mm) }}" placeholder="100" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                        @error("length_mm")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="color" class="mb-2 block text-sm font-medium text-slate-700">
                                Color
                            </label>
                            <input type="text" id="color" name="color" value="{{ old("color", $product->color) }}" placeholder="e.g., Blue" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                            @error("color")
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="material_id" class="mb-2 block text-sm font-medium text-slate-700">
                                Material
                            </label>
                            <select id="material_id" name="material_id" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                                <option value="">Select a material</option>
                                @forelse($materials as $material)
                                    <option value="{{ $material->id }}" {{ old("material_id", $product->material_id) == $material->id ? "selected" : "" }}>{{ $material->name }}</option>
                                @empty
                                    <option disabled>No materials available</option>
                                @endforelse
                            </select>
                            @error("material_id")
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="prd_balance" class="mb-2 block text-sm font-medium text-slate-700">
                            Stock Balance <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="prd_balance" name="prd_balance" value="{{ old("prd_balance", $product->prd_balance) }}" placeholder="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("prd_balance")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cost_rm" class="mb-2 block text-sm font-medium text-slate-700">
                                Cost (RM) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" id="cost_rm" name="cost_rm" value="{{ old("cost_rm", $product->cost_rm) }}" placeholder="5.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                            @error("cost_rm")
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="price_selling" class="mb-2 block text-sm font-medium text-slate-700">
                                Selling Price (RM) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" id="price_selling" name="price_selling" value="{{ old("price_selling", $product->price_selling) }}" placeholder="10.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                            @error("price_selling")
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="agent_discount_default" class="mb-2 block text-sm font-medium text-slate-700">
                            Default Agent Discount (%) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="agent_discount_default" name="agent_discount_default" value="{{ old("agent_discount_default", $product->agent_discount_default) }}" placeholder="15" step="0.01" max="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error("agent_discount_default")
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <x-admin.product-image-manager :product="$product" />

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-6 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                            Save Changes
                        </button>
                        <a href="{{ route("admin.products.index") }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
