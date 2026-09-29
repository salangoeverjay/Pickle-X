@extends('emails.layout')
@php($medals = App\Support\Results::MEDALS)
@php($top = $mine['rank'] <= 3)
@php($diffColor = $mine['diff'] > 0 ? '#496800' : ($mine['diff'] < 0 ? '#B23A0A' : null))

@section('label', 'Recap')
@section('why', "You got this email because you played at {$play->title}. Turn off recaps anytime.")

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 16px;">
        <tr>
            <td valign="middle" style="width: 70px; padding-right: 18px;">
                @if ($top)
                    <x-mail.medal :rank="$mine['rank']" :size="66" :display="true" />
                @else
                    <div style="width: 66px; height: 66px; line-height: 60px; border-radius: 50%; background: #F6F5EE; border: 3px solid #12170F; color: #12170F; text-align: center; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 30px;">{{ $mine['rank'] }}</div>
                @endif
            </td>
            <td valign="middle">
                <p class="em-ink3" style="margin: 0 0 4px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257;">{{ $top ? $medals[$mine['rank'] - 1][1].' · ' : '' }}{{ App\Support\Results::ordinal($mine['rank']) }} of {{ $mine['of'] }}</p>
                <x-mail.h1>{{ $top ? 'Great night' : 'Good games' }}, {{ $user->firstName() }}.</x-mail.h1>
            </td>
        </tr>
    </table>
    <p class="em-ink2" style="margin: 0 0 22px; font-size: 16px; color: #464D42;">Here's how you did at <strong class="em-ink" style="color: #12170F;">{{ $play->title }}</strong> on {{ $play->starts_at->format('D, M j') }} at {{ $play->venue }}.</p>

    @include('emails.partials.stats', ['stats' => [
        ['Record', $mine['w'].'-'.$mine['l'], null],
        ['Points +/−', App\Support\Results::signed($mine['diff']), $diffColor],
        ['Win rate', round($mine['w'] * 100 / $mine['g']).'%', null],
        ['Games', $mine['g'], null],
    ]])

    <x-mail.h2>Tonight's podium</x-mail.h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        <tr>
            @foreach ([1 => 58, 0 => 78, 2 => 44] as $i => $height)
                @php($r = $results->rows[$i] ?? null)
                @php($p = $r ? $play->participant($r['participant_id']) : null)
                <td width="33%" valign="bottom" align="center" style="padding: 0 4px;">
                    @if ($r)
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr>
                            <td style="padding: 0;"><x-mail.avatar :name="$p?->name ?? '?'" :color="$p?->color() ?? '#FFFFFF'" :src="$p?->avatarUrl()" :size="$i ? 48 : 56" :ring="$r['participant_id'] === $pid ? '#D3F347' : null" /></td>
                            <td valign="bottom" style="padding: 0 0 0 0;"><div style="margin-left: -12px; margin-bottom: -6px;"><x-mail.medal :rank="$i + 1" :size="22" ring="#FFFFFF" /></div></td>
                        </tr></table>
                        <p class="em-ink" style="margin: 8px 0 0; font-weight: 800; font-size: 14px; color: #12170F;">{{ $p?->firstName() }}{{ $r['participant_id'] === $pid ? ' · you' : '' }}</p>
                        <p class="em-ink2" style="margin: 0 0 8px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 12px; color: #464D42;">{{ $r['w'] }}-{{ $r['l'] }} · {{ App\Support\Results::signed($r['diff']) }}</p>
                    @endif
                    <div style="height: {{ $height }}px; border-radius: 14px 14px 4px 4px; background: {{ $medals[$i][0] }}; color: #12170F; text-align: center; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: {{ $i ? 26 : 30 }}px; line-height: 1.6;">{{ $i + 1 }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <x-mail.h2>Your games</x-mail.h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
        @foreach ($mine['games'] as $g)
            <tr>
                <td class="em-line" style="padding: 9px 12px 9px 0; width: 26px; border-top: 1px solid #DEDCD0; {{ $loop->last ? 'border-bottom: 1px solid #DEDCD0;' : '' }}">
                    <div style="width: 26px; height: 26px; line-height: 23px; border-radius: 8px; text-align: center; font-size: 12px; font-weight: 800; {{ $g['won'] ? 'background: #D3F347; color: #12170F; border: 1.5px solid #D3F347;' : 'color: #464D42; border: 1.5px solid #C8C6B8;' }}">{{ $g['won'] ? 'W' : 'L' }}</div>
                </td>
                <td class="em-line em-ink" style="padding: 9px 0; font-size: 14px; border-top: 1px solid #DEDCD0; color: #12170F; {{ $loop->last ? 'border-bottom: 1px solid #DEDCD0;' : '' }}"><span class="em-ink3" style="color: #5B6257;">with</span> <strong>{{ $g['with'] }}</strong> <span class="em-ink3" style="color: #5B6257;">vs</span> {{ $g['vs'] }}</td>
                <td align="right" class="em-line em-ink" style="padding: 9px 0; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-weight: 600; font-size: 14px; border-top: 1px solid #DEDCD0; color: #12170F; {{ $loop->last ? 'border-bottom: 1px solid #DEDCD0;' : '' }}">{{ $g['score'] }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin: 0 0 22px;"><x-mail.button :href="route('member.plays', ['results' => $play->id])">See full standings</x-mail.button></p>

    @if ($next)
        <div class="em-soft" style="padding: 14px 16px; border-radius: 16px; background: #F6F5EE;">
            <p class="em-ink2" style="margin: 0; font-size: 14px; color: #464D42;"><strong class="em-ink" style="color: #12170F;">Up next:</strong> {{ $next->title }}, {{ $next->starts_at->format('D M j') }}. You're already in.</p>
        </div>
    @endif
@endsection
