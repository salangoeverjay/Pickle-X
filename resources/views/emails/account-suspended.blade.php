@extends('emails.layout')
@php($footerLink = ['PickleX', url('/')])

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-hot-soft em-hot" style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #FDE9DA; border: 1px solid #F2B791; color: #B23A0A; font-size: 13px; font-weight: 800;">&#9208;&nbsp;Suspended · {{ $until ? $days.' days' : 'until restored' }}</span></p>
    @if ($until)
        <x-mail.h1>Your account is suspended until {{ $until->format('M j') }}</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, a PickleX admin suspended <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> for {{ $days }} days. Until it lifts you can't log in, join open plays or check in at the courts.</p>
        @include('emails.partials.span', ['dates' => [['Suspended', $at->format('D, M j')], ['Back on', $until->format('D, M j')]],
            'caption' => "It lifts on its own that morning. You don't need to do anything, and we'll email you when you're back."])
    @else
        <x-mail.h1>Your account is suspended</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, a PickleX admin suspended <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> on {{ $at->format('D, M j, Y') }}. It stays suspended until an admin restores it, and until then you can't log in, join open plays or check in at the courts.</p>
    @endif

    @include('emails.partials.suspension-reason')

    @unless ($until)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 22px; border-radius: 18px; border: 1px solid #DEDCD0;">
            <tr><td style="padding: 16px 18px 8px;">
                <p class="em-ink" style="margin: 0 0 10px; font-weight: 800; color: #12170F;">How to ask for another look</p>
                @foreach (['Reply to this email with your side of what happened.', $user->suspension_reason === 'conduct' ? "An admin reads it alongside the organizer's report." : 'An admin reads it and takes another look.', 'We email you the decision either way.'] as $i => $step)
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 8px;">
                        <tr>
                            <td valign="top" style="width: 32px;"><div class="em-ink-bg" style="width: 22px; height: 22px; line-height: 22px; border-radius: 50%; background: #12170F; color: #FFFFFF; text-align: center; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-weight: 600; font-size: 12px;">{{ $i + 1 }}</div></td>
                            <td valign="top" class="em-ink2" style="font-size: 14px; line-height: 1.5; color: #464D42;">{{ $step }}</td>
                        </tr>
                    </table>
                @endforeach
            </td></tr>
        </table>
    @endunless

    @include('emails.partials.checklist', ['title' => "While you're suspended", 'items' => [
        'Your games, stats and rankings are kept.',
        'Spots you held in upcoming open plays were freed so others can join.',
        $until ? 'Your login and passkeys work again on '.$until->format('M j').'.' : 'Your login and passkeys stay, and work again if your account is restored.',
    ]])

    @if ($until)
        <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
            <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Think this is a mistake?</p>
            <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">Reply to this email with anything that helps, like a schedule clash or an organizer who knew you couldn't make it. An admin will take another look.</p>
        </div>
    @endif

    <p style="margin: 0;"><x-mail.button :href="'mailto:'.config('mail.from.address').'?subject='.rawurlencode('Re: Your PickleX account is suspended')">Reply to the PickleX team</x-mail.button></p>
@endsection
