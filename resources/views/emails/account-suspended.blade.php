@extends('emails.layout')
@php($footerLink = ['PickleX', url('/')])

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span class="em-hot-soft em-hot" style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #FDE9DA; border: 1px solid #F2B791; color: #B23A0A; font-size: 13px; font-weight: 800;">&#9208;&nbsp;Suspended</span></p>
    <x-mail.h1>Your account is suspended</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Hi {{ $user->firstName() }}, a PickleX admin suspended <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> on {{ $user->suspended_at->format('D, M j, Y') }}. While it's suspended you can't log in, join open plays or check in at the courts.</p>

    @include('emails.partials.checklist', ['title' => 'What happens now', 'items' => [
        'Your games, stats and rankings are kept.',
        'Spots you held in upcoming open plays were released so others can join.',
        'Your login and passkeys stay, and work again if your account is restored.',
    ]])

    <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
        <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Think this is a mistake?</p>
        <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">Reply to this email with anything that helps, like a schedule clash or an organizer who knew you couldn't make it. An admin will take another look.</p>
    </div>

    <p style="margin: 0;"><x-mail.button :href="'mailto:'.config('mail.from.address').'?subject='.rawurlencode('Re: Your PickleX account is suspended')">Reply to the PickleX team</x-mail.button></p>
@endsection
