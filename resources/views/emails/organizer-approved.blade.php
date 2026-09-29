@extends('emails.layout')

@section('label', 'Organizer')
@section('why', 'You got this email because you asked to host open plays on PickleX.')

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 6px 12px 6px 10px; border-radius: 999px; background: #D3F347; color: #12170F; font-size: 13px; font-weight: 800;">&#10003;&nbsp;Approved</span></p>
    <x-mail.h1>You're approved, {{ $user->firstName() }}.</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $user->club }} can now host open plays on PickleX. You keep player mode on the same account, so you can switch between running courts and playing any time.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-soft" style="margin: 0 0 22px; border-radius: 18px; background: #F6F5EE; border: 1px solid #DEDCD0;">
        <tr>
            <td style="padding: 14px 0 14px 16px; width: 48px;">
                <div style="width: 48px; height: 48px; line-height: 48px; border-radius: 14px; background: #2F62AD; color: #FFFFFF; text-align: center; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 17px;">{{ initials($user->club ?? 'PX') }}</div>
            </td>
            <td style="padding: 14px 16px;">
                <p class="em-ink" style="margin: 0; font-weight: 700; font-size: 16px; color: #12170F;">{{ $user->club }}</p>
                <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">{{ collect([$user->city, $user->courts ? $user->courts.' '.Str::plural('court', $user->courts) : null])->filter()->join(' · ') }}</p>
            </td>
            <td align="right" class="em-ink3" style="padding: 14px 16px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 12px; color: #5B6257; white-space: nowrap;">{{ now()->format('M j') }}</td>
        </tr>
    </table>

    <x-mail.h2>Get your first night going</x-mail.h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        @foreach ([
            ['Create an open play', 'Pick the date, time and courts, set a fee, and add your GCash or Maya QR.'],
            ['Share the link or QR poster', 'Members join and pay from their phones. Anyone on your paid list is added for you.'],
            ['Run the court board', 'Check players in, fill courts from the queue and enter scores. Standings update after every game.'],
        ] as $i => [$title, $text])
            <tr>
                <td valign="top" style="padding: 0 14px 14px 0; width: 30px;">
                    <div class="em-ink-bg" style="width: 30px; height: 30px; line-height: 30px; border-radius: 50%; background: #12170F; color: #FFFFFF; text-align: center; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-weight: 600; font-size: 13px;">{{ $i + 1 }}</div>
                </td>
                <td valign="top" style="padding: 0 0 14px;">
                    <p class="em-ink" style="margin: 0; font-weight: 700; color: #12170F;">{{ $title }}</p>
                    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">{{ $text }}</p>
                </td>
            </tr>
        @endforeach
    </table>

    <p style="margin: 0 0 22px;"><x-mail.button :href="route('organizer.plays')">Create your first open play</x-mail.button></p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">Questions about setting up? Reply to this email and someone on the PickleX team will get back to you.</p>
@endsection
