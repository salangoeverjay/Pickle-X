@extends('emails.layout')

@section('label', 'Organizer request')
@section('why', 'You got this email because you review organizer requests on PickleX. Turn these off in Notification settings.')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
        <tr>
            <td valign="middle" style="width: 64px; padding-right: 16px;"><x-mail.avatar :name="$applicant->name" :color="$applicant->color()" :src="$applicant->avatarUrl()" :size="58" /></td>
            <td valign="middle">
                <x-mail.h1 :size="28">{{ $applicant->name }} wants to host</x-mail.h1>
                <p class="em-ink3" style="margin: 4px 0 0; font-size: 14px; color: #5B6257;">Just now{{ $stats->count() ? ' · '.$stats->count().' '.Str::plural('game', $stats->count()).' on PickleX' : ' · new to PickleX' }}</p>
            </td>
        </tr>
    </table>

    @include('emails.partials.details', ['rows' => array_filter([
        ['Club', $applicant->club],
        ['City', $applicant->city],
        ['Courts', $applicant->courts],
        ['Email', $applicant->email],
    ], fn ($r) => filled($r[1]))])

    @if ($applicant->organizer_note)
        <div class="em-soft" style="margin: 0 0 22px; padding: 12px 16px; border-radius: 0 14px 14px 0; background: #F6F5EE; border-left: 3px solid #D3F347;">
            <p class="em-ink3" style="margin: 0 0 4px; font-size: 12px; font-weight: 700; color: #5B6257;">Their note</p>
            <p class="em-ink" style="margin: 0; font-size: 15px; line-height: 1.5; color: #12170F;">{{ $applicant->organizer_note }}</p>
        </div>
    @endif

    <p class="em-ink2" style="margin: 0 0 22px; font-size: 14px; color: #464D42;">They can keep playing while they wait. Approving unlocks organizer tools on this same account.</p>

    <p style="margin: 0;"><x-mail.button :href="route('admin.requests')">Review the request</x-mail.button></p>
@endsection
