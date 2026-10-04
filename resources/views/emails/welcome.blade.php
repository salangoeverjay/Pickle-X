@extends('emails.layout')

@section('label', 'Welcome')
@section('why', "You got this email because you created a PickleX account with {$user->email}.")

@section('content')
    {{-- A court with the new member on it (decorative, built from tables so it survives email clients). --}}
    <div role="img" aria-label="A pickleball court with four players" style="margin: 0 0 22px; padding: 26px 7%; border-radius: 20px; background: #2E6B4C;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #2F62AD; border: 2px solid #FFFFFF;">
            <tr>
                <td width="34%" align="center" valign="middle" style="height: 62px; border-bottom: 2px solid #FFFFFF;">
                    <div style="width: 34px; height: 34px; line-height: 30px; margin: 0 auto; border-radius: 50%; background: {{ $user->color() }}; border: 2px solid #FFFFFF; color: #12170F; font-weight: 800; font-size: 11px; text-align: center;">{{ $user->initials() }}</div>
                </td>
                <td width="32%" rowspan="2" style="background: #3B72C2; border-left: 2px solid #FFFFFF; border-right: 2px solid #FFFFFF;">&nbsp;</td>
                <td width="34%" align="center" valign="middle" style="border-bottom: 2px solid #FFFFFF;">
                    <div style="width: 30px; height: 30px; margin: 0 auto; border-radius: 50%; background: #CDEBB0; border: 2px solid #FFFFFF;"></div>
                </td>
            </tr>
            <tr>
                <td align="center" valign="middle" style="height: 62px;"><div style="width: 30px; height: 30px; margin: 0 auto; border-radius: 50%; background: #FFEFA3; border: 2px solid #FFFFFF;"></div></td>
                <td align="center" valign="middle"><div style="width: 30px; height: 30px; margin: 0 auto; border-radius: 50%; background: #E2D2FF; border: 2px solid #FFFFFF;"></div></td>
            </tr>
        </table>
        <p style="margin: 12px 0 0; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 10px; letter-spacing: .1em; color: #FFFFFF;"><span style="color: #DDF84E;">&#9679;</span> YOU'RE UP · COURT 2</p>
    </div>

    <x-mail.h1>Welcome to the queue, {{ $user->firstName() }}.</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">PickleX runs open play the fair way: one queue, courts that keep rotating, and real stats from every game you play.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 12px;">
        @foreach ([
            ['1', 'Join an open play', "Find a session, tap Join, and pay with the organizer's QR if there's a fee."],
            ['2', 'Check in at the courts', "Tap Check in when you arrive. Players who haven't checked in are skipped for the next game."],
            ['3', "Play when you're up", 'Your live card in the app shows your court. Every score counts toward your record and rankings.'],
        ] as [$n, $title, $text])
            <tr>
                <td valign="top" style="padding: 0 14px 14px 0; width: 40px;">
                    <div class="em-lime-soft em-lime-text" style="width: 40px; height: 40px; line-height: 40px; border-radius: 12px; background: #F0FAC8; color: #496800; text-align: center; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-weight: 600; font-size: 15px;">{{ $n }}</div>
                </td>
                <td valign="top" style="padding: 0 0 14px;">
                    <p class="em-ink" style="margin: 0; font-weight: 700; color: #12170F;">{{ $title }}</p>
                    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">{{ $text }}</p>
                </td>
            </tr>
        @endforeach
    </table>

    @if ($plays->isNotEmpty())
        <x-mail.h2>Open plays near you</x-mail.h2>
        @foreach ($plays as $p)
            @include('emails.partials.play-row', ['play' => $p, 'right' => ($p->cap - $p->active_participants_count).' left'])
        @endforeach
        <div style="height: 12px; line-height: 12px; font-size: 0;">&nbsp;</div>
    @endif

    <p style="margin: 0 0 22px;"><x-mail.button :href="route('member.plays')">Find an open play</x-mail.button></p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;"><strong class="em-ink" style="color: #12170F;">Tip:</strong> add a profile picture in Account settings so your partners can spot you at the courts.</p>
@endsection
