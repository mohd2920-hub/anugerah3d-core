@props(['configuration' => null])
@if ($configuration)
    <div class="mt-2 text-xs text-slate-600">
        <p>{{ implode('', $configuration['characters']) }} · {{ $configuration['character_count'] }} huruf</p>
        <p>{{ $configuration['casing_name'] }} + {{ $configuration['huruf_name'] }}</p>
        @if ($configuration['result_image'] ?? $configuration['casing_image'] ?? null)
            <img src="{{ $configuration['result_image'] ?? $configuration['casing_image'] }}" alt="{{ $configuration['casing_name'] }} + {{ $configuration['huruf_name'] }}" width="80" height="80" style="max-width:80px;height:80px;object-fit:contain;border-radius:8px;margin-top:6px;">
        @endif
    </div>
@endif
