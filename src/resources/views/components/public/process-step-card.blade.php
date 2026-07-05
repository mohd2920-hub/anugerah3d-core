@props([
    'description',
    'stepNumber',
    'title',
])

<article {{ $attributes->merge(['class' => 'rounded-lg border border-white/10 bg-white/[0.06] p-6']) }}>
    <div class="text-sm font-semibold text-teal-200">Step {{ $stepNumber }}</div>
    <h3 class="mt-5 text-lg font-semibold">{{ $title }}</h3>
    <p class="mt-3 leading-7 text-zinc-300">{{ $description }}</p>
</article>
