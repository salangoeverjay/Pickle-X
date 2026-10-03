{{-- Two dates with an arrow between: [[label, date], [label, date]], then an optional $caption. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-soft" style="margin: 0 0 22px; border-radius: 18px; background: #F6F5EE; border: 1px solid #DEDCD0;">
    <tr><td style="padding: 16px 18px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            <tr>
                @foreach ($dates as [$label, $date])
                    @if (! $loop->first)
                        <td valign="bottom" class="em-ink3" style="padding: 0 14px 4px; font-size: 18px; color: #5B6257;">&rarr;</td>
                    @endif
                    <td valign="top">
                        <p class="em-ink3" style="margin: 0 0 4px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257;">{{ $label }}</p>
                        <p class="em-ink" style="margin: 0; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 20px; letter-spacing: -0.02em; color: #12170F; white-space: nowrap;">{{ $date }}</p>
                    </td>
                @endforeach
            </tr>
        </table>
        @if ($caption ?? null)
            <p class="em-ink2" style="margin: 10px 0 0; font-size: 14px; line-height: 1.5; color: #464D42;">{{ $caption }}</p>
        @endif
    </td></tr>
</table>
