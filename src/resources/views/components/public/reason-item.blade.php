@props([
    'reason',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg border border-zinc-200 bg-white p-4 shadow-sm']) }}>
    <span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>
    <span class="font-medium text-zinc-800">{{ $reason }}</span>
</div>
