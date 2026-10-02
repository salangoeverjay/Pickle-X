@extends('emails.layout')

@section('label', 'Session ended')
@section('why', "You got this email because you host {$play->title} on PickleX. Session alerts are always sent.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-soft" style="display: inline-block; padding: 5px 12px; border-radius: 999px; background: #F6F5EE; border: 1px solid #C8C6B8; color: #12170F; font-size: 13px; font-weight: 800;">&#9201;&nbsp;Ended automatically</span></p>
    <x-mail.h1>We ended {{ $play->title }} for you</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $play->organizer?->firstName() ?? 'there' }}, it was still live at {{ $at }}, {{ App\Models\OpenPlay::AUTO_END_HOURS }} hours after its {{ $end }} end time, so PickleX closed the court board and the players' live view. Nothing was lost.</p>

    @include('emails.partials.stats', ['stats' => [
        ['Games', $games, null],
        ['Players', $players, null],
        ['Checked in', $checkedIn, null],
        ['Ran for', $ran, null],
    ]])

    <div class="em-line" style="margin: 0 0 22px; padding: 14px 16px; border-radius: 16px; border: 1px solid #DEDCD0;">
        <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.5; color: #464D42;"><strong class="em-ink" style="color: #12170F;">&#10003; Everything is saved.</strong> All {{ $games }} logged {{ Str::plural('game', $games) }}, the final standings and everyone's stats and rankings count, the same as if you had tapped End session.</p>
    </div>

    <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
        <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Next time</p>
        <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">Tap End session on the court board when the last game wraps up. Players get their recap right away instead of the next morning.</p>
    </div>

    <p style="margin: 0;"><x-mail.button :href="route('organizer.plays')">See the results</x-mail.button></p>
@endsection
