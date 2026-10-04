@extends('emails.layout')

@section('label', "You're up next")
@section('why', "You got this email because you're in the queue at {$play->title}. Turn these off in Notification settings.")

@section('content')
    <div style="width: 56px; height: 56px; line-height: 52px; margin: 0 0 22px; border-radius: 18px; background: #D3F347; border: 2px solid #12170F; color: #12170F; text-align: center; font-size: 26px; font-weight: 800;">&#8599;</div>
    <x-mail.h1>You're up next, {{ $user->firstName() }}.</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">You're in the next game at <strong class="em-ink" style="color: #12170F;">{{ $play->title }}</strong>. You go on as soon as a court opens, so stay close and keep your paddle handy.</p>

    @include('emails.partials.details', ['rows' => array_filter([
        ['With', $partner],
        ['Against', $opponents],
        ['Where', $play->venue],
    ], fn ($r) => filled($r[1]))])

    <p style="margin: 0;"><x-mail.button :href="route('member.plays')">Open the live view</x-mail.button></p>
@endsection
