@extends('emails.layout')

@section('label', 'Open play update')
@section('why', "You got this email because you joined {$play->title} on PickleX. Changes to open plays you join are always sent.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-ink-bg" style="display: inline-block; padding: 6px 12px; border-radius: 999px; background: #12170F; color: #FFFFFF; font-size: 13px; font-weight: 800;">{{ $timeOnly ? 'New time' : 'Updated' }}</span></p>
    <x-mail.h1>{{ $timeOnly ? "{$play->title} now starts at {$start}" : "{$play->title} has changed" }}</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, {{ $host }} {{ $timeOnly ? 'moved this open play to a new time' : 'updated this open play' }}. You're still in, so if the new {{ $timeOnly ? 'time works' : 'details work' }} for you, there's nothing to do.</p>

    <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
        <p class="em-ink3" style="margin: 0 0 10px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257;">What changed</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach ($changes as $c)
                <tr>
                    <td valign="top" class="em-ink3" style="padding: 4px 16px 8px 0; width: 90px; font-size: 14px; color: #5B6257;">{{ $c['label'] }}</td>
                    <td valign="top" style="padding: 4px 0 8px;">
                        <p class="em-ink" style="margin: 0; font-size: 17px; font-weight: 800; color: #12170F;">{{ $c['value'] }}</p>
                        <p class="em-ink3" style="margin: 2px 0 0; font-size: 14px; color: #5B6257; text-decoration: line-through;">Was {{ $c['was'] }}</p>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <p class="em-ink" style="margin: 0 0 10px; font-weight: 700; color: #12170F;">Updated details</p>
    @include('emails.partials.details', ['rows' => [
        ['When', $play->starts_at->format('D, M j').', '.$play->timeRange()],
        ['Where', $play->venue],
        ['Format', $play->courts.' '.Str::plural('court', $play->courts).', race to '.$play->race_to.', '.($play->rotation === 'winners' ? 'winners stay on' : 'everyone rotates')],
        ['Your spot', "In, {$count} of {$play->cap} joined"],
    ]])

    @if ($paid && $play->fee)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 24px; border: 1px solid #DEDCD0; border-radius: 16px;">
            <tr>
                <td valign="top" style="padding: 14px 0 14px 16px; width: 24px;"><div style="width: 24px; height: 24px; line-height: 24px; border-radius: 50%; background: #D3F347; color: #12170F; text-align: center; font-size: 13px; font-weight: 800;">&#10003;</div></td>
                <td class="em-ink2" style="padding: 14px 16px 14px 12px; font-size: 14px; line-height: 1.5; color: #464D42;"><strong class="em-ink" style="color: #12170F;">Your {{ $play->fee }} payment still counts.</strong> You're marked as paid, so you don't need to pay again.</td>
            </tr>
        </table>
    @endif

    <p style="margin: 0 0 18px;">
        <x-mail.button :href="route('member.plays')">View open play</x-mail.button>
        &nbsp;
        <x-mail.button :href="App\Support\Emails::calendarUrl($play)" variant="secondary">Update my calendar</x-mail.button>
    </p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">Can't make {{ $timeOnly ? 'the new time' : 'it now' }}? <a href="{{ route('member.plays') }}" class="em-ink" style="color: #12170F; font-weight: 700; text-decoration: underline; text-decoration-color: #D3F347;">Leave the open play</a> so someone else can take your spot.</p>
@endsection
