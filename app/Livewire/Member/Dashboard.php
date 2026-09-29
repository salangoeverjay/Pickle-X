<?php

namespace App\Livewire\Member;

use App\Models\OpenPlay;
use App\Models\User;
use App\Support\MemberStats;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home · PickleX')]
class Dashboard extends Component
{
    /** Banner copy for the live open play the member is in: {title, standing, head, sub, timer?}. */
    public static function liveStatus(User $user): ?array
    {
        $plays = OpenPlay::status('live')->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))->get();

        foreach ($plays as $play) {
            $s = $play->statusFor($user);
            if (! $s) {
                continue;
            }
            $with = (($s['partner'] ?? null) ? "With {$s['partner']} vs " : 'Against ').($s['opponents'] ?? null);
            $pid = $play->participantMap()->firstWhere('user_id', $user->id)?->id;
            $mine = collect($play->standings())->firstWhere('participant_id', $pid);
            $standing = $mine ? ($mine['rank'] <= 3 ? 'Podium · ' : '')."#{$mine['rank']} tonight · {$mine['w']}-{$mine['l']}" : null;

            return ['title' => $play->title, 'standing' => $standing] + match ($s['state']) {
                'court' => ['head' => "You're on Court {$s['court']}", 'sub' => $with.' · ', 'timer' => now()->timestamp - ($s['started_at'] ?? now()->timestamp)],
                'next' => ['head' => "You're up next", 'sub' => $with.(($s['court'] ?? null) ? " on Court {$s['court']}. Grab your paddle." : '. You go on as soon as a court finishes.')],
                'waiting' => [
                    'head' => "You're next in line",
                    'sub' => "Waiting for {$s['need']} more checked-in ".($s['need'] === 1 ? 'player' : 'players').' to fill a game. Stay close to the courts.',
                ],
                'checkin' => [
                    'head' => 'Check in to get on court',
                    'sub' => "You're #{$s['position']} in line. Organizers only call players who are checked in, so tap Check in when you reach the courts.",
                ],
                'paused' => [
                    'head' => 'You stepped out',
                    'sub' => "Someone subbed in for you. Tap I'm back when you're ready. You kept your spot at #{$s['position']}.",
                ],
                default => [
                    'head' => "#{$s['position']} in line",
                    'sub' => $s['ahead'].($s['ahead'] === 1 ? ' player' : ' players').' ahead of you · '.$play->busyCourtCount().' of '.count($play->boardCourts()).' courts in play',
                ],
            };
        }

        return null;
    }

    public function render()
    {
        $user = auth()->user();
        // Headline ranks come from the member's home club table (Rankings has one table per club).
        $club = MemberStats::homeTable($user);

        return view('livewire.member.dashboard', [
            'user' => $user,
            'stats' => new MemberStats($user),
            'live' => self::liveStatus($user),
            'rankMonth' => $club ? MemberStats::rankOf($user, now()->startOfMonth(), $club) : null,
            'rankAll' => $club ? MemberStats::rankOf($user, null, $club) : null,
        ]);
    }
}
