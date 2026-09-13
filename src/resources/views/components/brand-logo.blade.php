@props(['width' => 64, 'message' => null])
@php
    $logoPath = 'images/anugerah3d-official-logo.png';
    $logoSource = $message ? $message->embed(public_path($logoPath)) : asset($logoPath);
@endphp
<img src="{{ $logoSource }}" alt="Anugerah3D" width="{{ $width }}" {{ $attributes->merge(['style' => 'display:inline-block;width:'.$width.'px;max-width:100%;height:auto;object-fit:contain;vertical-align:middle;background:#fff;border-radius:8px;flex-shrink:0;']) }}>
