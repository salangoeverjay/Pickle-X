@extends('emails.layout')

@section('label', 'Session reminder')
@section('why', "You got this email because you host {$play->title} on PickleX. Turn these off in Notification settings.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-soft" style="display: inline-block; padding: 5px 12px; border-radius: 999px; background: #F6F5EE; border: 1px solid #C8C6B8; color: #12170F; font-size: 13px; font-weight: 800;">&#9201;&nbsp;Starts in about an hour</span></p>
    <x-mail.h1>{{ $play->title }} starts at {{ $play->starts_at->format('g:i A') }}</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $play->starts_at->format('D, M j') }} · {{ $play->timeRange() }} · {{ $play->venue }}</p>

    <div class="em-soft" style="margin: 0 0 22px; padding: 18px; border-radius: 18px; background: #F6F5EE;">
        @include('emails.partials.progress', ['count' => $count, 'cap' => $play->cap])
        @if ($play->fee)
            <p style="margin: 12px 0 0;">
                <span class="em-lime-soft em-lime-text" style="display: inline-block; padding: 5px 10px; border-radius: 999px; background: #F0FAC8; color: #496800; font-size: 12px; font-weight: 800;">&#10003; {{ $paid }} paid</span>
                @if ($claims)
                    <span style="display: inline-block; padding: 5px 10px; border-radius: 999px; background: #FDE9DA; color: #B23A0A; font-size: 12px; font-weight: 800;">{{ $claims }} to confirm</span>
                @endif
            </p>
        @endif
    </div>

    @include('emails.partials.checklist', ['title' => 'Before the first game', 'items' => array_filter([
        'Tap Start session in Open plays when you get to the courts. Everyone who joined goes into the queue.',
        'Players tap Check in when they arrive. Only checked-in players get called to a court.',
        $claims ? "Confirm the {$claims} ".Str::plural('player', $claims).' who say they paid, so nobody gets asked twice.' : null,
    ])])

    <p style="margin: 0;"><x-mail.button :href="route('organizer.plays')">Open my open plays</x-mail.button></p>
@endsection
