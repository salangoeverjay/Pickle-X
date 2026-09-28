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
    /** Banner copy for the live open play the member is in: {title, head, sub, timer?}. */
    public static function liveStatus(User $user): ?array
    {
        $plays = OpenPlay::status('live')->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $user->id))->get();

        foreach ($plays as $play) {
            $s = $play->statusFor($user);
            if (! $s) {
                continue;
            }
            $with = $s['state'] !== 'queued' ? ($s['partner'] ? "With {$s['partner']} vs " : 'Against ').$s['opponents'] : null;

            return ['title' => $play->title] + match ($s['state']) {
                'court' => ['head' => "You're on Court {$s['court']}", 'sub' => $with.' · ', 'timer' => now()->timestamp - ($s['started_at'] ?? now()->timestamp)],
                'next' => ['head' => "You're up next!", 'sub' => $with.' — grab your paddle.'],
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

        return view('livewire.member.dashboard', [
            'user' => $user,
            'stats' => new MemberStats($user),
            'live' => self::liveStatus($user),
            'rankMonth' => MemberStats::rankOf($user, now()->startOfMonth()),
            'rankAll' => MemberStats::rankOf($user),
        ]);
    }
}
