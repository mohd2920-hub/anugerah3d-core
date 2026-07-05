@props([
    'accent',
    'description',
    'index',
    'name',
])

<article {{ $attributes->merge(['class' => 'group rounded-lg border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-teal-200 hover:shadow-xl hover:shadow-teal-900/5']) }}>
    <div class="{{ $accent }} grid h-11 w-11 place-items-center rounded-lg text-sm font-bold">
        {{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}
    </div>
    <h3 class="mt-6 text-lg font-semibold text-zinc-950">{{ $name }}</h3>
    <p class="mt-3 leading-7 text-zinc-600">{{ $description }}</p>
</article>
