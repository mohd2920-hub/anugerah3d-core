@props(["imageUrls"])

@if (count($imageUrls) > 0)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        @foreach ($imageUrls as $index => $imageUrl)
            <tr>
                <td align="center" style="padding:{{ $index === 0 ? "0" : "14px 0 0" }};">
                    <img src="{{ $imageUrl }}" alt="Email image {{ $index + 1 }}" style="display:block;max-width:100%;width:auto;height:auto;margin:0 auto;border:0;border-radius:12px;">
                </td>
            </tr>
        @endforeach
    </table>
@endif
