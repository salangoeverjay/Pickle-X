@extends('emails.layout')
@php
    $footerLink = ['Account settings', route('settings')];
    $label = ['member' => 'Member', 'organizer' => 'Organizer', 'admin' => 'Admin'];
    // Role pills: [classes, style].
    $pill = [
        'member' => ['em-card em-ink2', 'background: #FFFFFF; color: #464D42; border: 1.5px solid #C8C6B8;'],
        'organizer' => ['em-court-bg', 'background: #244C86; color: #FFFFFF; border: 1.5px solid #244C86;'],
        'admin' => ['em-ink-bg', 'background: #12170F; color: #FFFFFF; border: 1.5px solid #12170F;'],
    ];
    $club = $user->club ?: 'your club';
    [$body, $listTitle, $items, $cta, $href] = match ($role) {
        'organizer' => ["You can now host open plays for {$club}, and you still play on the same account.", "What's new for you", [
            "Create open plays for {$club}, with paid players and a payment QR.",
            'Run the court board: check-ins, the queue, scores and the live view.',
            'Switch to player mode any time. Your games, stats and rankings stay.',
        ], 'Create your first open play', route('organizer.plays')],
        'admin' => ['You can now review organizer requests and manage users, developers and sponsors.', "What's new for you", [
            'Approve or decline organizer requests.',
            'Manage accounts: roles, password resets, suspensions and deletions.',
            'Manage developer accounts and sponsor looks.',
        ], 'Open the admin dashboard', route('admin.requests')],
        default => ["You play on PickleX as a member now, so the hosting tools are off for this account.", 'What stays', [
            'Your games, stats and rankings.',
            'Your spots in open plays you joined.',
            'To host again, apply from Account settings › Apply to host open plays.',
        ], 'Find an open play', route('member.plays')],
    };
    $piece = fn ($r, $cap) => '<span style="display: inline-block; vertical-align: middle;"><span class="em-ink3" style="display: block; margin: 0 0 6px; font-family: \'Barlow Condensed\', \'Arial Narrow\', sans-serif; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257;">'.$cap.'</span><span class="'.$pill[$r][0].'" style="display: inline-block; padding: 7px 14px; border-radius: 999px; font-size: 15px; font-weight: 800; '.$pill[$r][1].'">'.$label[$r].'</span></span>';
@endphp

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #D3F347; color: #12170F; font-size: 13px; font-weight: 800;">&#8644;&nbsp;Role changed</span></p>
    <x-mail.h1>You're now {{ $role === 'member' ? 'a' : 'an' }} {{ $label[$role] }}, {{ $user->firstName() }}</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">A PickleX admin changed the role on <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>. {{ $body }}</p>

    <div role="img" aria-label="Role changed from {{ $label[$was] }} to {{ $label[$role] }}" class="em-soft" style="margin: 0 0 22px; padding: 18px; border-radius: 18px; background: #F6F5EE; border: 1px solid #DEDCD0;">
        {!! $piece($was, 'Was') !!}<span class="em-ink3" style="display: inline-block; vertical-align: bottom; padding: 0 12px 8px; font-size: 18px; color: #5B6257;">&rarr;</span>{!! $piece($role, 'Now') !!}
    </div>

    @include('emails.partials.checklist', ['title' => $listTitle, 'items' => $items])

    <p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Details</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">Changed by</td><td class="em-ink">A PickleX admin</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">When</td><td class="em-ink">{{ now()->format('D, M j, Y · g:i A') }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Account</td><td class="em-ink">{{ $user->email }}</td></tr>
    </table>

    <p style="margin: 0 0 22px;"><x-mail.button :href="$href">{{ $cta }}</x-mail.button></p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">Didn't expect this? Reply to this email and the PickleX team will check it.</p>
@endsection
