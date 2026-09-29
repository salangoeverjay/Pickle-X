@extends('emails.layout')

@section('label', 'New player')
@section('why', "You got this email because you host {$play->title} on PickleX. Turn these off in Notification settings.")

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
        <tr>
            <td valign="middle" style="width: 64px; padding-right: 16px;"><x-mail.avatar :name="$player->name" :color="$player->color()" :src="$player->avatarUrl()" :size="58" ring="#D3F347" /></td>
            <td valign="middle">
                <x-mail.h1 :size="28">{{ $player->name }} joined your open play</x-mail.h1>
                <p class="em-ink3" style="margin: 4px 0 0; font-size: 14px; color: #5B6257;">Just now{{ $stats->count() ? ' · '.$stats->count().' '.Str::plural('game', $stats->count()).' on PickleX · '.$stats->rate().'% win rate' : ' · new to PickleX' }}</p>
            </td>
        </tr>
    </table>

    <div class="em-soft" style="margin: 0 0 22px; padding: 18px; border-radius: 18px; background: #F6F5EE;">
        <p class="em-ink" style="margin: 0; font-weight: 700; font-size: 16px; color: #12170F;">{{ $play->title }}</p>
        <p class="em-ink3" style="margin: 0 0 12px; font-size: 13px; color: #5B6257;">{{ $play->starts_at->format('D, M j') }} · {{ $play->timeRange() }} · {{ $play->venue }}</p>
        @include('emails.partials.progress', ['count' => $count, 'cap' => $play->cap])
        @if ($play->fee)
            <p style="margin: 12px 0 0;">
                <span class="em-lime-soft em-lime-text" style="display: inline-block; padding: 5px 10px; border-radius: 999px; background: #F0FAC8; color: #496800; font-size: 12px; font-weight: 800;">&#10003; {{ $paidCount }} paid</span>
                @if ($claims)
                    <span style="display: inline-block; padding: 5px 10px; border-radius: 999px; background: #FDE9DA; color: #B23A0A; font-size: 12px; font-weight: 800;">{{ $claims }} to confirm</span>
                @endif
            </p>
        @endif
    </div>

    <p class="em-ink" style="margin: 0 0 6px; font-weight: 700; color: #12170F;">Latest players</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        @foreach ($latest as $p)
            <tr>
                <td class="em-line" style="padding: 10px 12px 10px 0; width: 34px; border-top: 1px solid #DEDCD0;"><x-mail.avatar :name="$p->name" :color="$p->color()" :src="$p->avatarUrl()" /></td>
                <td class="em-line" style="padding: 10px 0; border-top: 1px solid #DEDCD0;">
                    <p class="em-ink" style="margin: 0; font-weight: 700; font-size: 14px; color: #12170F;">{{ $p->name }}</p>
                    <p class="em-ink3" style="margin: 0; font-size: 12px; color: #5B6257;">{{ $p->created_at?->diffForHumans() }}{{ $p->user_id ? '' : ' · guest' }}</p>
                </td>
                @if ($play->fee)
                    <td align="right" class="em-line {{ $p->paid ? 'em-lime-text' : '' }}" style="padding: 10px 0; border-top: 1px solid #DEDCD0; font-size: 12px; font-weight: 800; color: {{ $p->paid ? '#496800' : '#B23A0A' }};">{{ $p->paid ? 'Paid' : 'Not paid' }}</td>
                @endif
            </tr>
        @endforeach
    </table>

    <p style="margin: 0;"><x-mail.button :href="route('organizer.plays')">Open my open plays</x-mail.button></p>
@endsection
