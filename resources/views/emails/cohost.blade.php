@extends('emails.layout')

@php($hostFirst = $host->firstName())
@section('label', 'Co-host')
@section('why', $added ? 'You got this email because an organizer made you a co-host on PickleX. Co-host changes are always sent.' : 'You got this email because an organizer changed who co-hosts an open play on PickleX. Co-host changes are always sent.')

@section('content')
    @if ($added)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 18px;">
            <tr>
                <td valign="middle" style="width: 92px; padding-right: 16px;">
                    <x-mail.avatar :name="$host->name" :color="$host->color()" :src="$host->avatarUrl()" :size="48" ring="#D3F347" />
                    <x-mail.avatar :name="$user->name" :color="$user->color()" :src="$user->avatarUrl()" :size="48" />
                </td>
                <td valign="middle">
                    <p class="em-ink3" style="margin: 0 0 2px; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #5B6257;">Co-host</p>
                    <x-mail.h1 :size="28">You're co-hosting {{ $play->title }}</x-mail.h1>
                </td>
            </tr>
        </table>
        <p class="em-ink2" style="margin: 0 0 20px; font-size: 16px; line-height: 1.5; color: #3B4237;">Hi {{ $user->firstName() }}, {{ $host->name }}{{ $host->club ? ' of '.$host->club : '' }} added you as a co-host. On the day you can start the session and run the court board, even if {{ $hostFirst }} isn't there yet.</p>

        <div class="em-soft" style="margin: 0 0 22px; padding: 18px; border-radius: 18px; background: #F6F5EE;">
            <p class="em-ink" style="margin: 0; font-weight: 700; font-size: 16px; color: #12170F;">{{ $play->title }}</p>
            <p class="em-ink3" style="margin: 0 0 12px; font-size: 13px; color: #5B6257;">{{ $play->starts_at->format('D, M j') }} · {{ $play->timeRange() }} · {{ $play->venue }}</p>
            @include('emails.partials.progress', ['count' => $play->playerCount(), 'cap' => $play->cap])
            <p class="em-ink3" style="margin: 12px 0 0; font-size: 13px; color: #5B6257;">{{ $play->courts }} {{ Str::plural('court', $play->courts) }} · {{ $play->rotation === 'winners' ? 'winners stay' : 'everyone rotates' }}{{ $play->fee ? ' · '.$play->feeLabel().' per player, paid to '.$hostFirst.($play->hasPaymentQr() ? ' by '.$play->payMethodLabel() : '') : ' · free' }}</p>
        </div>

        @include('emails.partials.checklist', ['title' => 'What you can do', 'items' => [
            'Start the session and fill the courts',
            'Check players in, add walk-ins and sub players',
            'Enter scores, change the game mode and end the session',
            "See the player list and share the open play's link",
        ]])
        @include('emails.partials.checklist', ['title' => "{$hostFirst} still handles", 'tone' => 'muted', 'items' => [
            'Changes to the open play, and cancelling it',
            'Payments and who else co-hosts',
        ]])

        <p style="margin: 0 0 18px;"><x-mail.button :href="$user->isOrganizer() ? route('organizer.plays') : route('member.plays')">Open your open plays</x-mail.button></p>
        <p class="em-ink3" style="margin: 0; font-size: 13px; line-height: 1.5; color: #5B6257;">{{ $hostFirst }} can remove you as a co-host any time, and we'll email you if that happens. Weren't expecting this? Reply and we'll let {{ $hostFirst }} know.</p>
    @else
        <x-mail.h1 :size="28">You're no longer co-hosting {{ $play->title }}</x-mail.h1>
        <p class="em-ink2" style="margin: 12px 0 20px; font-size: 16px; line-height: 1.5; color: #3B4237;">Hi {{ $user->firstName() }}, {{ $host->name }} removed you as a co-host of {{ $play->title }} on {{ $play->starts_at->format('D, M j') }}. You can't start it or run its court board anymore.</p>
        <p class="em-ink2" style="margin: 0 0 20px; font-size: 16px; line-height: 1.5; color: #3B4237;">{{ $joined ? "You're still on the player list, so see you on court." : 'You can still join it as a player.' }}</p>
        <p style="margin: 0;"><x-mail.button :href="$user->isOrganizer() ? route('organizer.plays') : route('member.plays')" variant="secondary">Open your open plays</x-mail.button></p>
    @endif
@endsection
