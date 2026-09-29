@extends('emails.layout')

@section('label', 'New open play')
@section('why', "You got this email because you've played at {$play->club}. Turn off new open play emails anytime.")

@section('content')
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
        <tr>
            <td style="padding-right: 10px;"><div style="width: 30px; height: 30px; line-height: 30px; border-radius: 9px; background: #2F62AD; color: #FFFFFF; text-align: center; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 12px;">{{ initials($play->club) }}</div></td>
            <td class="em-ink2" style="font-size: 14px; color: #464D42;"><strong class="em-ink" style="color: #12170F;">{{ $play->club }}</strong> posted a new open play</td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
        <tr>
            <td valign="middle" style="width: 72px; padding-right: 16px;">
                <div style="width: 72px; padding: 10px 0; border-radius: 18px; background: #D3F347; color: #12170F; text-align: center; line-height: 1.1;">
                    <span style="display: block; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 11px; letter-spacing: .12em;">{{ strtoupper($play->starts_at->format('M')) }}</span>
                    <span style="display: block; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 34px; letter-spacing: -0.04em;">{{ $play->starts_at->format('j') }}</span>
                    <span style="display: block; font-size: 11px; font-weight: 700;">{{ $play->starts_at->format('l') }}</span>
                </div>
            </td>
            <td valign="middle">
                <x-mail.h1>{{ $play->title }}</x-mail.h1>
                <p class="em-ink2" style="margin: 4px 0 0; font-size: 16px; font-weight: 600; color: #464D42;">{{ $play->timeRange() }}</p>
            </td>
        </tr>
    </table>

    @include('emails.partials.details', ['rows' => array_filter([
        ['Where', $play->venue],
        ['Format', $play->courts.' '.Str::plural('court', $play->courts).' · race to '.$play->race_to.' · '.($play->rotation === 'winners' ? 'winners stay on' : 'everyone rotates')],
        ['Fee', $play->feeLabel().($play->hasPaymentQr() ? " · pay with the club's {$play->payMethodLabel()} QR" : '')],
        ['Host', $play->organizer?->name],
    ], fn ($r) => filled($r[1]))])

    @include('emails.partials.progress', ['count' => $count, 'cap' => $play->cap])

    @if ($going->isNotEmpty())
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 14px 0 0;">
            <tr>
                @foreach ($going as $p)
                    <td style="padding-right: 4px;"><x-mail.avatar :name="$p->name" :color="$p->color()" :src="$p->avatarUrl()" :size="32" /></td>
                @endforeach
                <td class="em-ink2" style="padding-left: 10px; font-size: 14px; color: #464D42;">
                    @php($names = $going->take(3)->map->firstName())
                    {{ $count > 3 ? $names->join(', ').' and '.($count - 3).' '.Str::plural('other', $count - 3) : $names->join(', ', ' and ') }} {{ $count === 1 ? 'is' : 'are' }} going
                </td>
            </tr>
        </table>
    @endif

    <p style="margin: 24px 0 0;">
        <x-mail.button :href="route('member.plays')">Join open play</x-mail.button>
        &nbsp;
        <x-mail.button :href="App\Support\Emails::calendarUrl($play)" variant="secondary">Add to calendar</x-mail.button>
    </p>
@endsection
