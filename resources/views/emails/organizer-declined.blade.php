@extends('emails.layout')

@section('label', 'Organizer')
@section('why', 'You got this email because you asked to host open plays on PickleX.')

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #FDE9DA; border: 1px solid #F2B791; color: #B23A0A; font-size: 13px; font-weight: 800;">Not approved yet</span></p>
    <x-mail.h1>We couldn't approve your request yet</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, thanks for applying to host open plays for {{ $user->club }}. After review, we weren't able to approve it this time.</p>

    <div class="em-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F6F5EE; border-left: 4px solid #B23A0A;">
        <p class="em-ink2" style="margin: 0 0 6px; font-size: 13px; font-weight: 700; color: #464D42;">What helps us approve a club</p>
        <p class="em-ink" style="margin: 0; font-size: 15px; color: #12170F;">Add your venue, your city and your regular schedule so we can confirm where and when your sessions run, then send the request again.</p>
    </div>

    <p class="em-ink" style="margin: 0 0 10px; font-weight: 700; color: #12170F;">You can still play</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        @foreach (['Join open plays and queue up as a player', 'Check in, play and track your stats and rankings', 'Apply again any time from Account settings'] as $line)
            <tr>
                <td valign="top" style="padding: 0 10px 8px 0;"><div class="em-lime-soft em-lime-text" style="width: 22px; height: 22px; line-height: 22px; border-radius: 50%; background: #F0FAC8; color: #496800; text-align: center; font-size: 12px; font-weight: 800;">&#10003;</div></td>
                <td class="em-ink2" style="padding: 1px 0 8px; font-size: 14px; color: #464D42;">{{ $line }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin: 0 0 22px;"><x-mail.button :href="route('settings')">Update my request</x-mail.button></p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">Think we got it wrong? Reply to this email and tell us a bit more about your club.</p>
@endsection
