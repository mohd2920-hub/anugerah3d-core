@extends('admin.layouts.app')

@section('title', 'Edit Product | Anugerah3D Admin')

@section('page_title', 'Edit Product')

@section('content')
    <div class="max-w-2xl">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Product Code --}}
                <div>
                    <label for="prd_code" class="block text-sm font-medium text-slate-700 mb-2">
                        Product Code <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="prd_code" name="prd_code" value="{{ old('prd_code', $product->prd_code) }}" placeholder="e.g., HY/prd/1001" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('prd_code')
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Product Name --}}
                <div>
                    <label for="prd_name" class="block text-sm font-medium text-slate-700 mb-2">
                        Product Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="prd_name" name="prd_name" value="{{ old('prd_name', $product->prd_name) }}" placeholder="e.g., Hyurf 1" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('prd_name')
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Weight --}}
                <div>
                    <label for="weight_g" class="block text-sm font-medium text-slate-700 mb-2">
                        Weight (grams) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="weight_g" name="weight_g" value="{{ old('weight_g', $product->weight_g) }}" placeholder="10" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('weight_g')
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Dimensions --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="width_mm" class="block text-sm font-medium text-slate-700 mb-2">
                            Width (mm) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="width_mm" name="width_mm" value="{{ old('width_mm', $product->width_mm) }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error('width_mm')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="height_mm" class="block text-sm font-medium text-slate-700 mb-2">
                            Height (mm) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="height_mm" name="height_mm" value="{{ old('height_mm', $product->height_mm) }}" placeholder="50" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error('height_mm')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="length_mm" class="block text-sm font-medium text-slate-700 mb-2">
                        Length (mm)
                    </label>
                    <input type="number" id="length_mm" name="length_mm" value="{{ old('length_mm', $product->length_mm) }}" placeholder="100" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                    @error('length_mm')
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="color" class="block text-sm font-medium text-slate-700 mb-2">
                            Color
                        </label>
                        <input type="text" id="color" name="color" value="{{ old('color', $product->color) }}" placeholder="e.g., Blue" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                        @error('color')
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
                                <option value="{{ $material->id }}" {{ old('material_id', $product->material_id) == $material->id ? 'selected' : '' }}>{{ $material->name }}</option>
                            @empty
                                <option disabled>No materials available</option>
                            @endforelse
                        </select>
                        @error('material_id')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Product Balance --}}
                <div>
                    <label for="prd_balance" class="block text-sm font-medium text-slate-700 mb-2">
                        Stock Balance <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="prd_balance" name="prd_balance" value="{{ old('prd_balance', $product->prd_balance) }}" placeholder="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('prd_balance')
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Pricing --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="cost_rm" class="block text-sm font-medium text-slate-700 mb-2">
                            Cost (RM) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="cost_rm" name="cost_rm" value="{{ old('cost_rm', $product->cost_rm) }}" placeholder="5.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error('cost_rm')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="price_selling" class="block text-sm font-medium text-slate-700 mb-2">
                            Selling Price (RM) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" id="price_selling" name="price_selling" value="{{ old('price_selling', $product->price_selling) }}" placeholder="10.00" step="0.01" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                        @error('price_selling')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Agent Discount --}}
                <div>
                    <label for="agent_discount_default" class="block text-sm font-medium text-slate-700 mb-2">
                        Default Agent Discount (%) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="agent_discount_default" name="agent_discount_default" value="{{ old('agent_discount_default', $product->agent_discount_default) }}" placeholder="15" step="0.01" max="100" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100" required>
                    @error('agent_discount_default')
                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <x-admin.product-image-manager :product="$product" />

                {{-- Form Actions --}}
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#1a73e8] px-6 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
