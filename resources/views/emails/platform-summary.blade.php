@extends('emails.layout')

@section('label', 'Weekly platform summary')
@section('why', "You got this email because you're a PickleX admin. Turn off the weekly summary in Notification settings.")

@section('content')
    <x-mail.h1>PickleX last week</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $from->format('D, M j') }} to {{ $to->format('D, M j') }}</p>

    @include('emails.partials.stats', ['stats' => [
        ['New members', $members, null],
        ['First-time hosts', $hosts, null],
        ['Sessions', $sessions, null],
        ['Games', $games, null],
    ]])

    <p class="em-ink2" style="margin: 0 0 22px; font-size: 14px; color: #464D42;">{{ $sessions }} {{ Str::plural('session', $sessions) }} ran across {{ $clubs }} {{ Str::plural('club', $clubs) }}. First-time hosts are organizers who ran their first session last week.</p>

    @if ($pending)
        @include('emails.partials.alert', ['lead' => $pending.' organizer '.Str::plural('request', $pending).' waiting.', 'text' => 'Applicants keep playing while they wait, but they can\'t host until you review them.'])
    @endif

    <p style="margin: 0;">
        <x-mail.button :href="route('admin.requests')">Review requests</x-mail.button>
        &nbsp;
        <x-mail.button :href="route('admin.users')" variant="secondary">Manage users</x-mail.button>
    </p>
@endsection
