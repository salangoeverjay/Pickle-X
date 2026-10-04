@extends('emails.layout')

@section('label', 'Live now')
@section('why', "You got this email because you joined {$play->title} on PickleX. Turn these off in Notification settings.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px; border-radius: 999px; background: #12170F; color: #FFFFFF; font-size: 13px; font-weight: 800;"><span style="color: #D3F347;">&#9679;</span>&nbsp;Live now</span></p>
    <x-mail.h1>{{ $play->title }} is live</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, {{ $play->organizer?->firstName() ?? $play->club }} started the session at {{ ($play->started_at ?? now())->format('g:i A') }}. Courts fill from the queue, so check in as soon as you get there.</p>

    @include('emails.partials.details', ['rows' => [
        ['Where', $play->venue],
        ['Until', $play->ends_at->format('g:i A')],
        ['Format', $play->courts.' '.Str::plural('court', $play->courts).' · race to '.$play->race_to.' · '.($play->rotation === 'winners' ? 'winners stay on' : 'everyone rotates')],
        ['Players', $count.' joined'],
    ]])

    @if ($owes)
        @include('emails.partials.alert', ['lead' => 'Not paid yet.', 'text' => "Pay the {$play->fee} fee before your first game".($play->hasPaymentQr() ? ", with the QR in PickleX." : '.')])
    @endif

    <p style="margin: 0 0 18px;"><x-mail.button :href="route('member.plays')">Check in</x-mail.button></p>
    <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">Can't make it anymore? Leave the open play so the queue skips you.</p>
@endsection
