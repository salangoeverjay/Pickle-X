@extends('emails.layout')
@php($footerLink = ['Account settings', route('settings')])

@section('label', 'Account security')
@section('why', "This is a security email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <x-mail.h1>Reset your password</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, we got a request to reset the password for <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>. Enter this code on the reset screen to choose a new one.</p>

    <div class="em-soft" style="margin: 0 0 22px; padding: 24px 16px; border-radius: 20px; background: #F6F5EE; border: 1.5px dashed #C8C6B8; text-align: center;">
        <p class="em-ink3" style="margin: 0 0 6px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257;">Your reset code</p>
        <p class="em-code em-ink" style="margin: 0 0 6px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-weight: 600; font-size: 46px; line-height: 1.1; letter-spacing: .16em; color: #12170F;">{{ substr($code, 0, 3) }} {{ substr($code, 3) }}</p>
        <p class="em-ink2" style="margin: 0; font-size: 13px; color: #464D42;">Expires in 10 minutes, at {{ $expires->format('g:i A') }}</p>
    </div>

    <div style="margin: 0 0 22px;">
        <x-mail.button :href="$link">Reset password</x-mail.button>
        <p class="em-ink3" style="margin: 10px 0 0; font-size: 13px; color: #5B6257;">The button opens PickleX with the code filled in.</p>
    </div>

    <div class="em-line" style="height: 1px; margin: 0 0 22px; background: #DEDCD0; border-top: 1px solid #DEDCD0; line-height: 0; font-size: 0;">&nbsp;</div>

    <p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Request details</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">When</td><td class="em-ink">{{ now()->format('D, M j, Y · g:i A') }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Device</td><td class="em-ink">{{ $device }}</td></tr>
    </table>

    <div class="em-lime-soft" style="padding: 14px 16px; border-radius: 16px; background: #F0FAC8;">
        <p class="em-ink" style="margin: 0; font-size: 14px; line-height: 1.5; color: #12170F;"><strong>Didn't ask for this?</strong> You can ignore this email. Your password stays the same unless someone enters this code. Never share it, even with PickleX staff.</p>
    </div>
@endsection
