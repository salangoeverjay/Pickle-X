@extends('emails.layout')

@section('label', "You're in")
@section('why', 'You got this email because you joined an open play on PickleX.')

@section('content')
    <div style="width: 56px; height: 56px; line-height: 52px; margin: 0 0 22px; border-radius: 18px; background: #D3F347; border: 2px solid #12170F; color: #12170F; text-align: center; font-size: 26px; font-weight: 800;">&#10003;</div>
    <x-mail.h1>You're in, {{ $user->firstName() }}.</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Your spot at <strong class="em-ink" style="color: #12170F;">{{ $play->title }}</strong> is saved. You're player {{ $position }} of {{ $play->cap }}.</p>

    @include('emails.partials.details', ['rows' => [
        ['When', $play->starts_at->format('D, M j').' · '.$play->timeRange()],
        ['Where', $play->venue],
        ['Format', $play->courts.' '.Str::plural('court', $play->courts).' · race to '.$play->race_to.' · '.($play->rotation === 'winners' ? 'winners stay on' : 'everyone rotates')],
        ['Organizer', collect([$play->organizer?->name, $play->club])->filter()->join(' · ')],
    ]])

    @if ($owes)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 22px; border: 1.5px solid #C8C6B8; border-radius: 18px;">
            <tr><td style="padding: 18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="em-ink" style="font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 26px; letter-spacing: -0.03em; color: #12170F;">{{ $play->fee }}</td>
                        <td align="right"><span style="display: inline-block; padding: 4px 10px; border-radius: 999px; background: #FDE9DA; color: #B23A0A; font-size: 12px; font-weight: 800; white-space: nowrap;">Not paid yet</span></td>
                    </tr>
                </table>
                @if ($play->hasPaymentQr())
                    <p class="em-ink2" style="margin: 10px 0 14px; font-size: 14px; color: #464D42;">Pay {{ $play->pay_name }}{{ $play->pay_method === 'other' ? '' : ' with '.$play->payMethodLabel() }} before you play. Open the QR in PickleX, pay in {{ $play->payApp() }}, then tap I've sent the payment.</p>
                    <x-mail.button :href="route('member.plays')" variant="dark">Pay {{ $play->fee }} with QR</x-mail.button>
                @else
                    <p class="em-ink2" style="margin: 10px 0 0; font-size: 14px; color: #464D42;">Pay the organizer before you play.</p>
                @endif
            </td></tr>
        </table>
    @endif

    <p class="em-ink" style="margin: 0 0 10px; font-weight: 700; color: #12170F;">Before you go</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        @foreach (["Check in when you arrive so you're added to the rotation.", "Can't make it? Leave the open play so someone else can take your spot."] as $line)
            <tr>
                <td valign="top" class="em-lime-text" style="padding: 0 10px 8px 0; color: #496800; font-size: 14px;">&#9679;</td>
                <td class="em-ink2" style="padding: 0 0 8px; font-size: 14px; color: #464D42;">{{ $line }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin: 0;">
        <x-mail.button :href="route('member.plays')">View open play</x-mail.button>
        &nbsp;
        <x-mail.button :href="App\Support\Emails::calendarUrl($play)" variant="secondary">Add to calendar</x-mail.button>
    </p>
@endsection
