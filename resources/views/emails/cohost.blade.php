@extends('emails.layout')

@php($hostFirst = $host->firstName())
@section('label', 'Co-host')
@section('why', $added ? 'You got this email because an organizer made you a co-host on PickleX. Co-host changes are always sent.' : 'You got this email because you were a co-host on PickleX. Co-host changes are always sent.')

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
            <p class="em-ink3" style="margin: 0 0 12px; font-size: 13px; color: #5B6257;">{{ $play->starts_at->format('D, M j') }}, {{ $play->timeRange() }}, {{ $play->venue }}</p>
            @include('emails.partials.progress', ['count' => $play->playerCount(), 'cap' => $play->cap])
            <p class="em-ink3" style="margin: 12px 0 0; font-size: 13px; color: #5B6257;">{{ $play->courts }} {{ Str::plural('court', $play->courts) }}, {{ $play->rotation === 'winners' ? 'winners stay' : 'everyone rotates' }}{{ $play->fee ? ', '.$play->feeLabel().' per player, paid to '.$hostFirst.($play->hasPaymentQr() ? ' by '.$play->payMethodLabel() : '') : ', free' }}</p>
        </div>

        @include('emails.partials.checklist', ['title' => 'What you can do', 'items' => [
            'Start the session and fill the courts',
            'Check players in, add walk-ins and sub players',
            'Enter scores, change the game mode and end the session',
            "See the player list and share the open play's link",
        ]])
        @include('emails.partials.checklist', ['title' => "{$hostFirst} still handles", 'tone' => 'muted', 'items' => [
            'Changes to the open play, and cancelling it',
            "Marking who's paid, and who else co-hosts",
        ]])

        <p style="margin: 0 0 18px;"><x-mail.button :href="$user->isOrganizer() ? route('organizer.plays') : route('member.plays')">Open your open plays</x-mail.button></p>
        <p class="em-ink3" style="margin: 0; font-size: 13px; line-height: 1.5; color: #5B6257;">{{ $hostFirst }} can remove you as a co-host any time, and we'll email you if that happens. Weren't expecting this? Reply and we'll let {{ $hostFirst }} know.</p>
    @else
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 18px;">
            <tr>
                <td valign="middle" style="width: 92px; padding-right: 16px;">
                    <x-mail.avatar :name="$host->name" :color="$host->color()" :src="$host->avatarUrl()" :size="48" ring="#D3F347" />
                    <x-mail.avatar :name="$user->name" :color="$user->color()" :src="$user->avatarUrl()" :size="48" />
                </td>
                <td valign="middle">
                    <p class="em-ink3" style="margin: 0 0 2px; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #5B6257;">Co-host</p>
                    <x-mail.h1 :size="28">You're no longer co-hosting {{ $play->title }}</x-mail.h1>
                </td>
            </tr>
        </table>
        <p class="em-ink2" style="margin: 0 0 20px; font-size: 16px; line-height: 1.5; color: #3B4237;">Hi {{ $user->firstName() }}, {{ $host->name }} removed you as a co-host of {{ $play->title }}. Nothing else about the open play has changed.</p>

        @if ($joined)
            <div class="em-lime-soft" style="margin: 0 0 22px; padding: 18px; border-radius: 18px; background: #F0FAC8;">
                <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; font-size: 16px; color: #12170F;">&#10003; You're still on the player list</p>
                <p class="em-ink2" style="margin: 0 0 12px; font-size: 14px; line-height: 1.5; color: #3B4237;">{{ $paid ? 'Your '.$play->feeLabel().($play->hasPaymentQr() ? ' '.$play->payMethodLabel() : '')." payment to {$hostFirst} still counts, so you keep your spot." : 'You keep your spot.' }} Check in when you get to the courts.</p>
                <p class="em-ink" style="margin: 0; font-weight: 700; font-size: 14px; color: #12170F;">{{ $play->title }}</p>
                <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">{{ $play->starts_at->format('D, M j') }}, {{ $play->timeRange() }}, {{ $play->venue }}</p>
            </div>
        @endif

        @include('emails.partials.checklist', ['title' => 'What changes', 'tone' => 'muted', 'items' => [
            "You can't start the session or run the court board",
            "The Court board won't show up in your menu for this open play",
            "You see the same Who's in list as every other player",
        ]])

        <p style="margin: 0 0 18px;">
            <x-mail.button :href="route('member.plays')">See the open play</x-mail.button>
            @if ($joined)
                &nbsp;<x-mail.button :href="route('member.plays')" variant="secondary">Can't make it? Leave it</x-mail.button>
            @endif
        </p>
        <p class="em-ink3" style="margin: 0; font-size: 13px; line-height: 1.5; color: #5B6257;">Weren't expecting this? Reply and we'll let {{ $hostFirst }} know.</p>
    @endif
@endsection
