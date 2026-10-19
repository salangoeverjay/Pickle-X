@extends('emails.layout')
@php($closest = $results->closest())

@section('label', 'Organizer recap')
@section('why', "You got this email because you host {$play->title} on PickleX.")

@section('content')
    <x-mail.h1>{{ $play->title }} is a wrap.</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $play->starts_at->format('D, M j') }}, {{ $play->timeRange() }}, {{ $play->venue }}</p>

    @include('emails.partials.stats', ['stats' => [
        ['Players', $players, null],
        ['Games', $results->log->count(), null],
        ['Courts', $play->courts, null],
        ['Closest game', $closest ? App\Support\Results::score($closest) : 'None', null],
    ]])

    @if ($money)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 22px; border: 1.5px solid #C8C6B8; border-radius: 18px;">
            <tr><td style="padding: 18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 10px;">
                    <tr>
                        <td class="em-ink" style="font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 26px; letter-spacing: -0.03em; color: #12170F;">{{ $money['total'] }} collected</td>
                        <td align="right" class="em-ink3" style="font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 12px; color: #5B6257; white-space: nowrap;">{{ $money['paid'] }} of {{ $players }} paid</td>
                    </tr>
                </table>
                <div class="em-line" style="height: 8px; border-radius: 4px; background: #DEDCD0; overflow: hidden; line-height: 0; font-size: 0;"><div class="em-lime-text" style="width: {{ min(100, round($money['paid'] * 100 / max(1, $players))) }}%; height: 8px; border-radius: 4px; background: #496800;"></div></div>
                @if ($money['unpaid'])
                    @php($n = count($money['unpaid']))
                    <p class="em-ink2" style="margin: 12px 0 0; font-size: 14px; color: #464D42;"><strong class="em-ink" style="color: #12170F;">{{ $n }} to follow up:</strong> @php($names = collect($money['unpaid'])->map(fn ($name) => Str::before(trim($name), ' ')))
                        {{ $n > 5 ? $names->take(5)->join(', ').' and '.($n - 5).' '.Str::plural('other', $n - 5) : $names->join(', ', ' and ') }} played but {{ $n === 1 ? "hasn't" : "haven't" }} paid yet.</p>
                @endif
            </td></tr>
        </table>
    @endif

    @if ($results->rows)
        <x-mail.h2>Top 3</x-mail.h2>
        @foreach (array_slice($results->rows, 0, 3) as $i => $r)
            @php($p = $play->participant($r['participant_id']))
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-lime-soft" style="margin: 0 0 6px; border-radius: 14px; background: #F0FAC8;">
                <tr>
                    <td style="padding: 10px 0 10px 12px; width: 26px;"><x-mail.medal :rank="$i + 1" :size="26" /></td>
                    <td style="padding: 10px 0 10px 12px; width: 34px;"><x-mail.avatar :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" :src="$p?->avatarUrl()" /></td>
                    <td class="em-ink" style="padding: 10px 12px; font-weight: 800; color: #12170F;">{{ $p?->name }}</td>
                    <td align="right" class="em-ink" style="padding: 10px 12px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 13px; white-space: nowrap; color: #12170F;"><strong>{{ $r['w'] }}-{{ $r['l'] }}</strong>&nbsp;&nbsp;<span class="em-lime-text" style="color: #496800; font-weight: 600;">{{ App\Support\Results::signed($r['diff']) }}</span></td>
                </tr>
            </table>
        @endforeach

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0 22px;">
            <tr>
                @foreach ($results->highlights() as [$label, $value])
                    <td class="em-full" width="33%" valign="top" style="padding: {{ $loop->first ? '0 5px 0 0' : ($loop->last ? '0 0 0 5px' : '0 5px') }};">
                        <div class="em-line" style="margin-bottom: 10px; padding: 12px 14px; border-radius: 16px; border: 1px solid #DEDCD0;">
                            <p class="em-ink3" style="margin: 0 0 2px; font-size: 12px; font-weight: 700; color: #5B6257;">{{ $label }}</p>
                            <p class="em-ink" style="margin: 0; font-weight: 800; color: #12170F;">{{ $value }}</p>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
        <p class="em-ink2" style="margin: 0 0 22px; font-size: 14px; color: #464D42;">Players who played get their own recap with their record, their games and the final standings.</p>
    @endif

    <p style="margin: 0 0 18px;">
        <x-mail.button :href="route('organizer.plays', ['results' => $play->id])">View full standings</x-mail.button>
        &nbsp;
        <x-mail.button :href="route('organizer.plays')" variant="secondary">Create the next open play</x-mail.button>
    </p>
    @if ($results->rows)
        <a href="{{ route('organizer.standings-csv', $play) }}" class="em-ink" style="font-size: 13px; font-weight: 700; color: #12170F; text-decoration: underline; text-decoration-color: #D3F347;">Download standings as CSV</a>
    @endif
@endsection
