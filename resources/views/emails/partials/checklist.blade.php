{{-- A titled box of short lines. $tone: yes (lime check) or no (soft cross, for things that were removed). --}}
@php($yes = ($tone ?? 'yes') === 'yes')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 22px; border-radius: 18px; border: 1px solid #DEDCD0;">
    <tr><td style="padding: 16px 18px 8px;">
        <p class="em-ink" style="margin: 0 0 8px; font-weight: 800; color: #12170F;">{{ $title }}</p>
        @foreach ($items as $item)
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 8px;">
                <tr>
                    <td valign="top" style="width: 32px;">
                        <div class="{{ $yes ? '' : 'em-soft' }}" style="width: 22px; height: 22px; line-height: 22px; border-radius: 50%; text-align: center; font-size: 12px; font-weight: 800; {{ $yes ? 'background: #D3F347; color: #12170F;' : 'background: #F6F5EE; border: 1px solid #C8C6B8; color: #464D42; line-height: 20px;' }}">{!! $yes ? '&#10003;' : '&#10005;' !!}</div>
                    </td>
                    <td valign="top" class="em-ink2" style="font-size: 14px; line-height: 1.5; color: #464D42;">{{ $item }}</td>
                </tr>
            </table>
        @endforeach
    </td></tr>
</table>
