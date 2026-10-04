{{-- A small "Details" label over plain label/value rows. $rows = [[label, value], ...] --}}
<p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Details</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
    @foreach ($rows as [$label, $value])
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">{{ $label }}</td><td class="em-ink">{{ $value }}</td></tr>
    @endforeach
</table>
