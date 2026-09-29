{{-- Label/value rows on a soft panel. $rows = [[label, value], ...] --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-soft" style="margin: 0 0 22px; border-radius: 18px; background: #F6F5EE;">
    <tr><td style="padding: 12px 18px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach ($rows as [$label, $value])
                <tr>
                    <td valign="top" class="em-ink3" style="padding: 5px 16px 5px 0; width: 96px; font-size: 14px; color: #5B6257;">{{ $label }}</td>
                    <td valign="top" class="em-ink" style="padding: 5px 0; font-size: 15px; font-weight: 600; color: #12170F;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </td></tr>
</table>
