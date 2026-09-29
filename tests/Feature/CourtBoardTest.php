<?php

namespace Tests\Feature;

use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CourtBoardTest extends TestCase
{
    use RefreshDatabase;

    private function play(int $players, array $attrs = []): OpenPlay
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);
        $play = OpenPlay::create($attrs + [
            'organizer_id' => $org->id, 'title' => 'Test', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 2, 'cap' => 24,
        ]);
        User::factory($players)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]); // everyone is at the courts

        return $play;
    }

    public function test_start_fills_courts_and_scores_rotate_everyone(): void
    {
        $play = $this->play(9);
        $play->start();

        $this->assertSame(2, $play->busyCourtCount());
        $this->assertSame(1, $play->queuedPlayerCount());

        $game = $play->recordScore(1, 11, 5);

        $this->assertSame('a', $game->winner);
        $this->assertSame(4, $game->players()->count());
        $this->assertSame(2, $game->players()->where('won', true)->count());
        // 1 waiting + 4 back = 5; auto-fill puts 4 back on court 1.
        $this->assertSame(2, $play->busyCourtCount());
        $this->assertSame(1, $play->queuedPlayerCount());
    }

    public function test_winners_stay_for_two_games_then_rotate(): void
    {
        $play = $this->play(10, ['courts' => 1, 'rotation' => 'winners']);
        $play->start();
        $winners = $play->boardCourts()[0]['a']['ids'];

        $play->recordScore(1, 11, 3);
        $this->assertSame($winners, $play->boardCourts()[0]['a']['ids']);
        $this->assertSame(1, $play->boardCourts()[0]['wins']);

        $play->recordScore(1, 11, 7);
        $this->assertNotContains($winners[0], $play->boardCourts()[0]['a']['ids']);
    }

    public function test_locked_partners_play_together(): void
    {
        $play = $this->play(6, ['courts' => 1]);
        $play->start();
        [$e1, $e2] = collect($play->queue())->pluck('id')->all();

        $play->lockPartners($e1, $e2);
        $this->assertTrue($play->queue()[0]['locked']);

        $play->recordScore(1, 5, 11);
        $team = collect($play->boardCourts()[0])->only(['a', 'b'])->first(fn ($t) => $t['locked']);
        $this->assertNotNull($team, 'locked pair should be on court together');
    }

    public function test_invalid_race_scores_are_rejected(): void
    {
        $play = $this->play(4, ['courts' => 1]);
        $play->start();

        $this->expectException(InvalidArgumentException::class);
        $play->recordScore(1, 11, 11);
    }

    public function test_member_status_and_leaving(): void
    {
        $play = $this->play(4, ['courts' => 1]);
        $late = User::factory()->create();
        $play->start();
        $play->join($late);

        $this->assertSame('court', $play->statusFor($play->participants()->first()->user)['state']);
        $this->assertSame('checkin', $play->statusFor($late)['state']);

        $play->checkIn([$play->participants()->where('user_id', $late->id)->value('id')]);
        // First in line, but only 1 of the 4 needed is ready.
        $this->assertSame(['state' => 'waiting', 'need' => 3], collect($play->fresh()->statusFor($late))->only('state', 'need')->all());

        $play->leave($late);
        $this->assertNull($play->statusFor($late));
        $this->assertSame(0, $play->queuedPlayerCount());
    }

    public function test_only_checked_in_and_present_players_are_called(): void
    {
        $play = $this->play(4, ['courts' => 1]);
        $play->participants()->limit(1)->update(['checked_in_at' => null]);
        $play->start();

        // One of the four hasn't arrived: nobody can be called yet.
        $this->assertSame(0, $play->busyCourtCount());

        $play->checkIn($play->participants()->pluck('id')->all());
        $this->assertSame(1, $play->busyCourtCount(), 'auto-fill runs once everyone is checked in');
    }

    public function test_sub_in_takes_the_spot_and_the_subbed_out_player_steps_out(): void
    {
        $play = $this->play(7, ['courts' => 1]);
        $play->start();
        [$first, $second, $third] = collect($play->queue())->pluck('id')->all();

        $play->subIn($first, $third);

        $queue = collect($play->queue());
        $this->assertSame([$third, $first, $second], $queue->pluck('id')->all());
        $this->assertTrue($queue->firstWhere('id', $first)['paused']);
        $this->assertNotContains($first, $play->nextUpTeams()['used'] ?? [], 'stepped-out entries are skipped');

        $play->setPaused($first, false);
        $this->assertFalse(collect($play->queue())->firstWhere('id', $first)['paused']);
    }

    public function test_full_open_play_rejects_joins(): void
    {
        $play = $this->play(4, ['cap' => 4]);

        $this->expectException(InvalidArgumentException::class);
        $play->join(User::factory()->create());
    }
}
