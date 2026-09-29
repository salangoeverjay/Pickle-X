@extends('emails.layout')

@section('label', 'Open play cancelled')
@section('why', "You got this email because you joined {$play->title} on PickleX. Cancellations are always sent.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px; border-radius: 999px; background: #FDE9DA; border: 1px solid #F2B791; color: #B23A0A; font-size: 13px; font-weight: 800;">&#10005;&nbsp;Cancelled</span></p>
    <x-mail.h1>{{ $play->title }} is cancelled</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, {{ $host }} cancelled this open play, so it's been taken off your Open plays. Sorry for the change of plans.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-soft" style="margin: 0 0 22px; border-radius: 18px; background: #F6F5EE; border: 1px dashed #C8C6B8;">
        <tr>
            <td style="padding: 14px 0 14px 16px; width: 60px;">
                <div class="em-card" style="width: 60px; padding: 8px 0; border-radius: 16px; background: #FFFFFF; border: 1px solid #DEDCD0; text-align: center; line-height: 1.1; opacity: .7;">
                    <span class="em-ink3" style="display: block; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 10px; letter-spacing: .12em; color: #5B6257;">{{ strtoupper($play->starts_at->format('M')) }}</span>
                    <span class="em-ink2" style="display: block; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 26px; color: #464D42;">{{ $play->starts_at->format('j') }}</span>
                    <span class="em-ink3" style="display: block; font-size: 10px; color: #5B6257;">{{ $play->starts_at->format('D') }}</span>
                </div>
            </td>
            <td style="padding: 14px 16px;">
                <p class="em-ink2" style="margin: 0; font-weight: 800; font-size: 17px; color: #464D42; text-decoration: line-through; text-decoration-color: #B23A0A;">{{ $play->title }}</p>
                <p class="em-ink3" style="margin: 2px 0 0; font-size: 13px; color: #5B6257;">{{ $play->timeRange() }} · {{ $play->venue }}</p>
                <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">{{ $play->club }} · hosted by {{ $host }}</p>
            </td>
        </tr>
    </table>

    @if ($owed)
        <div class="em-line" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; border: 1.5px solid #C8C6B8;">
            <p class="em-ink" style="margin: 0 0 8px; font-weight: 800; color: #12170F;">About your {{ $play->fee }} payment</p>
            <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">You paid {{ $host }}{{ $method ? " by {$method}" : '' }}. PickleX doesn't hold payments, so your refund comes straight from {{ $hostFirst }}.{{ $method ? " Keep your {$method} receipt handy in case you need it." : '' }}</p>
        </div>
    @endif

    @if ($others->isNotEmpty())
        <x-mail.h2>Other open plays you can join</x-mail.h2>
        @foreach ($others as $p)
            @include('emails.partials.play-row', ['play' => $p, 'right' => ($p->cap - $p->active_participants_count).' left'])
        @endforeach
        <div style="height: 12px; line-height: 12px; font-size: 0;">&nbsp;</div>
    @endif

    <p style="margin: 0;"><x-mail.button :href="route('member.plays')">Find another open play</x-mail.button></p>
@endsection
