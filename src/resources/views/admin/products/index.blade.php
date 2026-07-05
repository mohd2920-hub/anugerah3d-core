@extends('admin.layouts.app')

@section('title', 'Products | Anugerah3D Admin')

@section('page_title', 'Products')

@section('content')
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Products</h1>
                <p class="mt-1 text-sm text-slate-600">Manage your product catalog</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#1a73e8] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-700/20 transition hover:bg-[#1558b0] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:ring-offset-2">
                <span class="mr-2">+</span> Add Product
            </a>
        </div>

        {{-- Alert Messages --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- Search Section --}}
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.products.index') }}" class="flex gap-3">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by product code or name..." class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                    Search
                </button>
                @if (request('search'))
                    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- Products Table --}}
        <div class="rounded-lg bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Code</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Weight (g)</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Dimensions (mm)</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Balance</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Cost (RM)</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Selling (RM)</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Discount (%)</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Image</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono text-slate-900">{{ $product->prd_code }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $product->prd_name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ number_format($product->weight_g, 2) }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $product->width_mm }} × {{ $product->height_mm }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700">{{ $product->prd_balance }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-900 font-medium">RM {{ number_format($product->cost_rm, 2) }}</td>
                                <td class="px-6 py-4 text-slate-900 font-medium">RM {{ number_format($product->price_selling, 2) }}</td>
                                <td class="px-6 py-4 text-center text-slate-600">{{ number_format($product->agent_discount_default, 2) }}%</td>
                                <td class="px-6 py-4 text-center">
                                    @if ($product->prd_picture)
                                        <img src="{{ $product->prd_picture }}" alt="{{ $product->prd_name }}" class="h-10 w-10 rounded object-cover mx-auto">
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center justify-center rounded px-2.5 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 transition">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center rounded px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center">
                                    <p class="text-slate-600">No products found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $products->links('pagination::tailwind') }}
        </div>
    </div>
@endsection
