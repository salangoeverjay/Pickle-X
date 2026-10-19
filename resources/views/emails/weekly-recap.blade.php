@extends('emails.layout')

@section('label', 'Weekly recap')
@section('why', 'You got this email because you played on PickleX last week. Turn off the weekly recap in Notification settings.')

@section('content')
    <x-mail.h1>Your week, {{ $user->firstName() }}.</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $from->format('D, M j') }} to {{ $to->format('D, M j') }}, {{ $plays }} open {{ Str::plural('play', $plays) }}</p>

    @include('emails.partials.stats', ['stats' => [
        ['Games', $games, null],
        ['Record', $wins.'-'.$losses, null],
        ['Win rate', $rate.'%', $rate >= 50 ? '#496800' : null],
        ['Rank', $rank ? '#'.$rank : 'None', null],
    ]])

    @if ($move)
        <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
            <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Rankings</p>
            <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">{{ $move }}</p>
        </div>
    @endif

    <p style="margin: 0;">
        <x-mail.button :href="route('member.dashboard')">See your stats</x-mail.button>
        &nbsp;
        <x-mail.button :href="route('member.rankings')" variant="secondary">Rankings</x-mail.button>
    </p>
@endsection
