@extends('emails.layout')
@php
    $footerLink = ['PickleX', url('/')];
    $fake = $key === 'fake';
    $caption = match ($key) {
        'noshow' => "It lifts on its own that morning. You don't need to do anything, and we'll email you when you're back.",
        'unpaid' => 'It lifts on its own that morning, or sooner once you settle up.',
        'spam' => 'It lifts on its own that morning. Your display name goes back to your full name.',
        default => 'It lifts on its own that morning.',
    };
    // Numbered steps when there's something to do, or to ask for another look when there's no end date.
    [$stepsTitle, $steps] = match (true) {
        $key === 'unpaid' => ['Get back on court sooner', [
            'Pay the organizer what you owe, by GCash or in cash at the courts.',
            'Reply to this email with the GCash reference or a photo of the receipt.',
            'An admin checks with the organizer and lifts the suspension'.($until ? ' early.' : '.'),
        ]],
        $fake => ['What to do', [
            'If this is your second account, keep using your main one. Nothing else to do.',
            "If you're a different person, reply with your full name and home club.",
            "An admin reads every reply and restores the account if it's yours alone.",
        ]],
        ! $until => ['How to ask for another look', [
            'Reply to this email with your side of what happened.',
            $key === 'conduct' ? "An admin reads it alongside the organizer's report." : 'An admin reads every reply and takes another look.',
            'We email you the decision either way.',
        ]],
        default => [null, []],
    };
@endphp

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-hot-soft em-hot" style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #FDE9DA; border: 1px solid #F2B791; color: #B23A0A; font-size: 13px; font-weight: 800;">&#9208;&nbsp;Suspended, {{ $until ? $days.' days' : 'until restored' }}</span></p>
    <x-mail.h1>{{ $fake ? 'This' : 'Your' }} account is suspended{{ $until ? ' until '.$until->format('M j') : '' }}</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $fake ? 'A' : 'Hi '.$user->firstName().', a' }} PickleX admin suspended <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>
        @if ($until)
            for {{ $days }} days. Until it lifts {{ $fake ? "it can't log in or join open plays." : "you can't log in, join open plays or check in at the courts." }}
        @else
            on {{ $at->format('D, M j, Y') }}. It stays suspended until an admin restores it, and until then {{ $fake ? "it can't log in or join open plays." : "you can't log in, join open plays or check in at the courts." }}
        @endif
    </p>
    @if ($until)
        @include('emails.partials.span', ['dates' => [['Suspended', $at->format('D, M j')], ['Back on', $until->format('D, M j')]], 'caption' => $caption])
    @endif

    @include('emails.partials.suspension-reason')

    @if ($steps)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 22px; border-radius: 18px; border: 1px solid #DEDCD0;">
            <tr><td style="padding: 16px 18px 8px;">
                <p class="em-ink" style="margin: 0 0 10px; font-weight: 800; color: #12170F;">{{ $stepsTitle }}</p>
                @foreach ($steps as $i => $step)
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 8px;">
                        <tr>
                            <td valign="top" style="width: 32px;"><div class="em-ink-bg" style="width: 22px; height: 22px; line-height: 22px; border-radius: 50%; background: #12170F; color: #FFFFFF; text-align: center; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-weight: 600; font-size: 12px;">{{ $i + 1 }}</div></td>
                            <td valign="top" class="em-ink2" style="font-size: 14px; line-height: 1.5; color: #464D42;">{{ $step }}</td>
                        </tr>
                    </table>
                @endforeach
            </td></tr>
        </table>
    @endif

    @if ($fake)
        @include('emails.partials.checklist', ['title' => 'Good to know', 'items' => [
            'Games logged on this account can be moved to your main one. Just ask in your reply.',
            'Spots this account held in upcoming open plays were freed.',
        ]])
    @else
        @include('emails.partials.checklist', ['title' => "While you're suspended", 'items' => [
            'Your games, stats and rankings are kept.',
            'Spots you held in upcoming open plays were freed so others can join.',
            $until ? 'Your login and passkeys work again on '.$until->format('M j').($key === 'unpaid' ? ", or as soon as it's lifted." : '.')
                : 'Your login and passkeys stay, and work again if your account is restored.',
        ]])
    @endif

    @if ($until && ! $steps)
        @include('emails.partials.tip', ['title' => 'Think this is a mistake?', 'text' => $key === 'noshow'
            ? "Reply to this email with anything that helps, like a schedule clash or an organizer who knew you couldn't make it. An admin will take another look."
            : 'Reply to this email with anything that helps. An admin reads every reply and takes another look.'])
    @endif

    <p style="margin: 0;"><x-mail.button :href="'mailto:'.config('mail.from.address').'?subject='.rawurlencode('Re: '.($fake ? 'This' : 'Your').' PickleX account is suspended')">Reply to the PickleX team</x-mail.button></p>
@endsection
