@props(['rank', 'scope' => 'agent'])
@if ($rank > 3)
    <span {{ $attributes->class(['rank-number-plain']) }} aria-label="Kedudukan {{ $rank }}">{{ $rank }}</span>
@else
<span {{ $attributes->class(['agent-rank-medal', 'agent-rank-medal-'.$rank, 'rank-medal-neutral' => $rank > 3]) }} role="img" aria-label="Kedudukan {{ $rank }}">
    <svg viewBox="0 0 100 112" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="rank-metal-{{ $scope }}-{{ $rank }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" class="rank-metal-light"/><stop offset=".35" class="rank-metal-mid"/><stop offset=".52" class="rank-metal-light"/><stop offset="1" class="rank-metal-dark"/>
            </linearGradient>
        </defs>
        <circle cx="50" cy="61" r="30" fill="currentColor" opacity=".07"/>
        <g fill="url(#rank-metal-{{ $scope }}-{{ $rank }})" stroke="currentColor" stroke-width=".5">
            <path d="M33 24 29 10 41 16 50 4 59 16 71 10 67 24Z"/>
            <rect x="33" y="26" width="34" height="4" rx="2"/>
            <circle cx="29" cy="9" r="2.5"/><circle cx="50" cy="3.5" r="2.5"/><circle cx="71" cy="9" r="2.5"/>
            <path d="M40 91C13 80 10 53 25 37M60 91C87 80 90 53 75 37" fill="none" stroke-width="1.4"/>
            @foreach ([0, 1] as $side)
                <g transform="{{ $side ? 'translate(100 0) scale(-1 1)' : '' }}">
                    <ellipse cx="21" cy="42" rx="3" ry="7" transform="rotate(25 21 42)"/>
                    <ellipse cx="16" cy="53" rx="3" ry="7" transform="rotate(-15 16 53)"/>
                    <ellipse cx="18" cy="66" rx="3" ry="7" transform="rotate(-30 18 66)"/>
                    <ellipse cx="24" cy="78" rx="3" ry="7" transform="rotate(-45 24 78)"/>
                    <ellipse cx="29" cy="49" rx="3" ry="6" transform="rotate(45 29 49)"/>
                    <ellipse cx="26" cy="62" rx="3" ry="6" transform="rotate(45 26 62)"/>
                    <ellipse cx="31" cy="73" rx="3" ry="6" transform="rotate(35 31 73)"/>
                </g>
            @endforeach
            <path d="M13 88 26 90 26 102 12 99 16 94ZM87 88 74 90 74 102 88 99 84 94Z"/>
            <path d="M25 87Q50 95 75 87V102Q50 110 25 102Z"/>
        </g>
        <text x="50" y="80" text-anchor="middle" @class(['rank-medal-number', 'rank-medal-double' => $rank >= 10]) fill="#000000" stroke="#000000" stroke-width=".5">{{ $rank }}</text>
        <text x="50" y="101" text-anchor="middle" fill="currentColor" font-size="8" letter-spacing="2">★ ★ ★</text>
    </svg>
</span>
@endif
