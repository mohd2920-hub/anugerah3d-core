@props([
    'title',
])

<figure {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-zinc-200 bg-[#f4f8f6] shadow-sm']) }}>
    <div class="grid aspect-[4/3] place-items-center bg-[linear-gradient(135deg,#e7f6f1_0%,#f8faf8_48%,#fff1df_100%)] p-8">
        <div class="relative h-28 w-32">
            <div class="absolute left-2 top-8 h-14 w-24 rounded-lg bg-white shadow-xl shadow-zinc-900/10"></div>
            <div class="absolute left-10 top-2 h-14 w-14 rotate-45 rounded-lg bg-teal-300/80 shadow-lg"></div>
            <div class="absolute bottom-2 right-1 h-16 w-16 rounded-full bg-amber-200/90 shadow-lg"></div>
            <div class="absolute bottom-7 left-12 h-12 w-12 rounded-lg bg-rose-200/90 shadow-lg"></div>
        </div>
    </div>
    <figcaption class="px-5 py-4 text-sm font-medium text-zinc-700">{{ $title }}</figcaption>
</figure>
