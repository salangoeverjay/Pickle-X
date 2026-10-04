@extends('emails.layout')

@section('label', 'Weekly club summary')
@section('why', "You got this email because you host open plays for {$organizer->club} on PickleX. Turn off the weekly summary in Notification settings.")

@section('content')
    <x-mail.h1>{{ $organizer->club }} last week</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $from->format('D, M j') }} to {{ $to->format('D, M j') }}</p>

    @include('emails.partials.stats', ['stats' => [
        ['Sessions', $plays->count(), null],
        ['Games', $games, null],
        ['Players', $players, null],
        ['Per session', round($games / $plays->count()), null],
    ]])

    <x-mail.h2>Busiest {{ Str::plural('night', $busiest->count()) }}</x-mail.h2>
    @foreach ($busiest as $p)
        @include('emails.partials.play-row', ['play' => $p, 'right' => ($counts[$p->id] ?? 0).' '.Str::plural('player', $counts[$p->id] ?? 0).' · '.$p->games_count.' '.Str::plural('game', $p->games_count)])
    @endforeach

    <p style="margin: 22px 0 0;"><x-mail.button :href="route('organizer.plays')">Create the next open play</x-mail.button></p>
@endsection
