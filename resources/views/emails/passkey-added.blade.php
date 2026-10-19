@extends('emails.layout')
@php($footerLink = ['Account settings', route('settings')])

@section('label', 'Account security')
@section('why', "This is a security email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #D3F347; color: #12170F; font-size: 13px; font-weight: 800;">&#10003;&nbsp;Passkey added</span></p>
    <x-mail.h1>A new passkey can log in to your account</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, a passkey was added to <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>. That device can now log in with its fingerprint, face or screen lock, without your password.</p>

    <div class="em-soft" style="margin: 0 0 22px; padding: 14px 16px; border-radius: 18px; background: #F6F5EE; border: 1px solid #DEDCD0;">
        <p class="em-ink" style="margin: 0 0 3px; font-weight: 800; font-size: 17px; color: #12170F;">{{ $name }}</p>
        <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">Added {{ $at->format('D, M j, Y, g:i A') }}</p>
    </div>

    <p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Details</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">When</td><td class="em-ink">{{ $at->format('D, M j, Y, g:i A') }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Device</td><td class="em-ink">{{ $device }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Passkeys</td><td class="em-ink">{{ $count }} on your account</td></tr>
    </table>

    <div class="em-line" style="height: 1px; margin: 0 0 22px; background: #DEDCD0; border-top: 1px solid #DEDCD0; line-height: 0; font-size: 0;">&nbsp;</div>

    @include('emails.partials.alert', ['lead' => "Wasn't you?", 'text' => 'Remove this passkey and change your password right away. Someone who added it may know your password.'])

    <p style="margin: 0;">
        <x-mail.button :href="route('settings').'#set-passkeys'">Review your passkeys</x-mail.button>&nbsp;
        <x-mail.button :href="route('settings').'#set-security'" variant="secondary">Change password</x-mail.button>
    </p>
@endsection
