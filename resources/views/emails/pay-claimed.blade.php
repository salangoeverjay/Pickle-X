@extends('emails.layout')

@section('label', 'Payment to check')
@section('why', "You got this email because you host {$play->title} on PickleX. Turn these off in Notification settings.")

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
        <tr>
            <td valign="middle" style="width: 64px; padding-right: 16px;"><x-mail.avatar :name="$player->name" :color="$player->color()" :src="$player->avatarUrl()" :size="58" ring="#D3F347" /></td>
            <td valign="middle">
                <x-mail.h1 :size="28">{{ $player->firstName() }} says they paid</x-mail.h1>
                <p class="em-ink3" style="margin: 4px 0 0; font-size: 14px; color: #5B6257;">{{ $play->title }}, {{ $play->starts_at->format('D, M j') }}, {{ $play->timeRange() }}</p>
            </td>
        </tr>
    </table>

    @include('emails.partials.details', ['rows' => array_filter([
        ['Player', $player->name],
        ['Amount', $play->fee],
        ['Paid to', collect([$play->pay_name, $play->payMethodLabel(), $play->pay_number])->filter()->join(', ')],
        ['Tapped', $at->format('D, M j, g:i A')],
    ], fn ($r) => filled($r[1]))])

    <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
        <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Check it before you mark it</p>
        <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">Open {{ $play->payApp() }} and look for {{ $play->fee ?: 'the payment' }} from {{ $player->name }}. If it's there, mark {{ $player->firstName() }} as paid in the player list. If it isn't, ask them at the courts.</p>
    </div>

    <p style="margin: 0 0 22px;">
        <span class="em-lime-soft em-lime-text" style="display: inline-block; padding: 5px 10px; border-radius: 999px; background: #F0FAC8; color: #496800; font-size: 12px; font-weight: 800;">&#10003; {{ $paidCount }} of {{ $count }} paid</span>
        <span style="display: inline-block; padding: 5px 10px; border-radius: 999px; background: #FDE9DA; color: #B23A0A; font-size: 12px; font-weight: 800;">{{ $claims }} to confirm</span>
    </p>

    <p style="margin: 0;"><x-mail.button :href="route('organizer.plays')">Open the player list</x-mail.button></p>
@endsection
