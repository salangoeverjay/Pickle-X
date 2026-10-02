@extends('emails.layout')
@php($footerLink = ['PickleX', url('/')])

@section('label', 'Account')
@section('why', "This is the last email we'll send to this address about the deleted account.")

@section('content')
    @if ($byAdmin)
        <p style="margin: 0 0 22px;"><span class="em-hot-soft em-hot" style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #FDE9DA; border: 1px solid #F2B791; color: #B23A0A; font-size: 13px; font-weight: 800;">&#10005;&nbsp;Account deleted</span></p>
        <x-mail.h1>Your account was deleted</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, a PickleX admin deleted the account for <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> on {{ $at->format('D, M j, Y') }}. You can't log in with it anymore, with your password or a passkey.</p>
    @else
        <p style="margin: 0 0 22px;"><span class="em-soft em-ink" style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #F6F5EE; border: 1px solid #C8C6B8; color: #12170F; font-size: 13px; font-weight: 800;">&#10005;&nbsp;Account deleted</span></p>
        <x-mail.h1>Your account is deleted, {{ $user->firstName() }}</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">As you asked, we deleted the PickleX account for <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> on {{ $at->format('D, M j, Y') }} at {{ $at->format('g:i A') }}. Thanks for playing with us.</p>
    @endif

    @include('emails.partials.checklist', ['tone' => 'no', 'title' => $byAdmin ? 'What was removed' : 'What we removed', 'items' => [
        'Your login, password and passkeys.',
        'Your profile, avatar, display name and looks.',
        $byAdmin ? 'Your spots in upcoming open plays.' : 'Your spots in upcoming open plays, so others can take them.',
    ]])

    @include('emails.partials.checklist', ['title' => 'What stays', 'items' => $byAdmin ? [
        "Games you played stay in other players' history as Former player.",
        "Payments you made went to organizers directly. PickleX doesn't hold any money, so ask the organizer about a refund.",
    ] : [
        "Games you played stay in other players' history as Former player, so their records still add up.",
    ]])

    @if ($byAdmin)
        <p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Details</p>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
            <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">Deleted by</td><td class="em-ink">A PickleX admin</td></tr>
            <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">When</td><td class="em-ink">{{ $at->format('D, M j, Y · g:i A') }}</td></tr>
            <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Account</td><td class="em-ink">{{ $user->email }}</td></tr>
        </table>

        <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
            <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Think this is a mistake?</p>
            <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">Reply to this email and tell us what happened. You can also make a new account with the same email any time.</p>
        </div>

        <p style="margin: 0;"><x-mail.button :href="'mailto:'.config('mail.from.address').'?subject='.rawurlencode('Re: Your PickleX account was deleted')">Reply to the PickleX team</x-mail.button></p>
    @else
        @include('emails.partials.alert', ['lead' => "Didn't delete your account?", 'text' => 'Reply to this email right away. Someone may know your password.'])

        <p class="em-ink" style="margin: 0 0 4px; font-weight: 700; color: #12170F;">Changed your mind?</p>
        <p class="em-ink2" style="margin: 0 0 22px; font-size: 14px; line-height: 1.5; color: #464D42;">You can make a new account with the same email any time. Your old stats won't come back.</p>

        <p style="margin: 0;"><x-mail.button :href="route('signup')" variant="secondary">Create a new account</x-mail.button></p>
    @endif
@endsection
