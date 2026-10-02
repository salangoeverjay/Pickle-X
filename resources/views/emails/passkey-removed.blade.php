@extends('emails.layout')
@php($footerLink = ['Account settings', route('settings')])

@section('label', 'Account security')
@section('why', "This is a security email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-soft em-ink" style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #F6F5EE; border: 1px solid #C8C6B8; color: #12170F; font-size: 13px; font-weight: 800;">&#10005;&nbsp;Passkey removed</span></p>
    <x-mail.h1>A passkey was removed</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, a passkey was removed from <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>, so that device can't log in with it anymore. {{ match (true) { $left === 0 => 'Your password still works.', $left === 1 => 'Your password and your other passkey still work.', default => 'Your password and your other passkeys still work.' } }}</p>

    <div class="em-soft" style="margin: 0 0 22px; padding: 14px 16px; border-radius: 18px; background: #F6F5EE; border: 1px dashed #C8C6B8;">
        <p class="em-ink2" style="margin: 0 0 3px; font-weight: 800; font-size: 17px; color: #464D42; text-decoration: line-through; text-decoration-color: #B23A0A;">{{ $name }}</p>
        <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">Added {{ $added->format('M j') }} · Removed {{ $at->format('D, M j · g:i A') }}</p>
    </div>

    <p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Details</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">When</td><td class="em-ink">{{ $at->format('D, M j, Y · g:i A') }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Removed on</td><td class="em-ink">{{ $device }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Passkeys</td><td class="em-ink">{{ $left ? $left.' left on your account' : 'None left on your account' }}</td></tr>
    </table>

    <div class="em-line" style="height: 1px; margin: 0 0 22px; background: #DEDCD0; border-top: 1px solid #DEDCD0; line-height: 0; font-size: 0;">&nbsp;</div>

    @include('emails.partials.alert', ['lead' => "Wasn't you?", 'text' => "Change your password and check the passkeys on your account. If you can't log in, use Forgot password? on the log in screen."])

    <p style="margin: 0;">
        <x-mail.button :href="route('settings').'#set-passkeys'">Review your passkeys</x-mail.button>&nbsp;
        <x-mail.button :href="route('settings').'#set-passkeys'" variant="secondary">Add a passkey</x-mail.button>
    </p>
@endsection
