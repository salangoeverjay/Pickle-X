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
    [$body, $h1, $cta, $href] = match ($role) {
        'organizer' => ["You can host open plays for {$club} now, and you still play on the same account.", "You're now an Organizer", 'Create your first open play', route('organizer.plays')],
        'admin' => ['You can manage the whole platform now, and you can still join open plays as a player.', "You're now a PickleX Admin", 'Open the admin pages', route('admin.requests')],
        default => ["You can still join any open play, but you can't create or run them anymore.", 'Your role is now Member', 'Find an open play', route('member.plays')],
    };
    // Demoted organizers keep their upcoming open plays; nothing hands them over.
    $kept = match ($hosting->count()) {
        0 => null,
        1 => "{$hosting[0]->title} on {$hosting[0]->starts_at->format('D, M j')} stays on, and players who joined keep their spots. Reply to this email if it needs a new host.",
        default => "Your {$hosting->count()} upcoming open plays stay on, and players who joined keep their spots. Reply to this email if they need a new host.",
    };
    $piece = fn ($r, $cap) => '<span style="display: inline-block; vertical-align: middle;"><span class="em-ink3" style="display: block; margin: 0 0 6px; font-family: \'Barlow Condensed\', \'Arial Narrow\', sans-serif; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257;">'.$cap.'</span><span class="'.$pill[$r][0].'" style="display: inline-block; padding: 7px 14px; border-radius: 999px; font-size: 15px; font-weight: 800; '.$pill[$r][1].'">'.$label[$r].'</span></span>';
@endphp

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #D3F347; color: #12170F; font-size: 13px; font-weight: 800;">&#8644;&nbsp;Role changed</span></p>
    <x-mail.h1>{{ $h1 }}, {{ $user->firstName() }}</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $role === 'admin' ? 'Another admin' : 'A PickleX admin' }} changed the role on <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>. {{ $body }}</p>

    <div role="img" aria-label="Role changed from {{ $label[$was] }} to {{ $label[$role] }}" class="em-soft" style="margin: 0 0 22px; padding: 18px; border-radius: 18px; background: #F6F5EE; border: 1px solid #DEDCD0;">
        {!! $piece($was, 'Was') !!}<span class="em-ink3" style="display: inline-block; vertical-align: bottom; padding: 0 12px 8px; font-size: 18px; color: #5B6257;">&rarr;</span>{!! $piece($role, 'Now') !!}
    </div>

    @if ($role === 'organizer')
        @include('emails.partials.checklist', ['title' => 'Your hosting tools', 'items' => [
            ['Create open plays', 'Pick the date, time, courts, player cap and fee, and add a GCash or bank QR so players can pay ahead.'],
            ['Run the live court board', 'Check players in, fill courts from a fair queue, sub players in and out, and log scores.'],
            ["See who's in and who paid", 'Every upcoming open play lists who joined. Mark them paid or not as payments arrive.'],
            ['Switch to player mode', 'Join other open plays any time. Your games, stats and rankings stay.'],
        ]])
    @elseif ($role === 'admin')
        @include('emails.partials.checklist', ['title' => 'Your admin pages', 'items' => [
            ['Requests', 'Approve or decline people who ask to host open plays for their club.'],
            ['Users', 'Change roles, send password resets, sign people out, suspend or restore accounts, and delete them.'],
            ['Developers', 'Mark the people who build PickleX so they get the Developer look.'],
            ['Sponsors', 'Give Gold, Silver and Bronze sponsor looks to the players who back the community.'],
        ]])
        @include('emails.partials.tip', ['title' => 'Keep your account locked down', 'text' => 'Admins can see every account. Add a passkey in Account settings › Passkeys, and never share your password.'])
    @else
        @include('emails.partials.checklist', ['title' => 'What stays', 'items' => [
            'Your account, password and passkeys.',
            'Your games, stats, rankings and looks.',
            'Open plays you hosted stay in the history, with their results.',
        ]])
        @include('emails.partials.checklist', ['title' => 'What changes', 'tone' => 'muted', 'items' => array_filter([
            $was === 'admin' ? 'No more admin pages.' : 'No more Create open play or court board.',
            $kept,
        ])])
        @include('emails.partials.tip', ['title' => 'Want to host again?', 'text' => 'Apply any time from Account settings › Organize. An admin reviews it, usually within a few days.'])
    @endif

    @include('emails.partials.facts', ['rows' => [
        ['Changed by', $role === 'admin' && $by ? $by : 'A PickleX admin'],
        ['When', now()->format('D, M j, Y · g:i A')],
        ['Account', $user->email],
    ]])

    <p style="margin: 0 0 22px;"><x-mail.button :href="$href">{{ $cta }}</x-mail.button></p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;">Didn't expect this? Reply to this email and the PickleX team will check it.</p>
@endsection
