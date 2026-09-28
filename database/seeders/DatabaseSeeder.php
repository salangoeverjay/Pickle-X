<?php

namespace Database\Seeders;

use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data for the one-tap accounts on the login page (APP_DEMO=true). Every account's password is "password".
 * Past sessions are played through the real court board engine with the clock moved back,
 * so stats, rankings and "played with" all come from logged games.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CLUB = 'Panabo Dink Society';

    public function run(): void
    {
        mt_srand(7);

        $members = collect([
            ['Kai Santos', '#BCD9F7'], ['Rico Dizon', '#CDEBB0'], ['Ana Reyes', '#FFCBBB'], ['Ben Tan', '#E2D2FF'],
            ['Mika Lim', '#FFEFA3'], ['Jojo Bautista', '#BDEBE3'], ['Carla Mendoza', '#F7C9DB'], ['Paolo Cruz', '#BCD9F7'],
            ['Tin Ramos', '#FFD3A3'], ['Gab Navarro', '#FFEFA3'], ['Liza Uy', '#BDEBE3'], ['Nico Garcia', '#FFCBBB'],
            ['Bea Castillo', '#E2D2FF'], ['Enzo Aquino', '#CDEBB0'], ['Joy Villanueva', '#F7C9DB'], ['Marco Rivera', '#FFD3A3'],
            ['Sam Flores', '#BCD9F7'], ['Ria Torres', '#CDEBB0'], ['Leo Soriano', '#E2D2FF'], ['Dana Chua', '#FFEFA3'],
        ])->map(fn ($m, $i) => User::factory()->create([
            'name' => $m[0],
            'email' => $i === 0 ? 'kai@picklex.app' : strtolower(strtok($m[0], ' ')).'@picklex.test',
            'avatar_color' => $m[1],
            'club' => self::CLUB,
        ]));
        $kai = $members[0];

        $ely = $this->organizer('Ely Ramos', 'ely@panabodink.ph', self::CLUB, 'Panabo City', 4, 'approved', 'Monday night and Saturday morning open plays.', '-16 days');
        $davao = $this->organizer('Rafa Medina', 'rafa@davaopaddle.ph', 'Davao Paddle Club', 'Davao City', 2, 'approved', 'Weekend beginner clinics.', '-30 days');
        $digos = $this->organizer('Joan Uy', 'joan@digosdinkers.ph', 'Digos Dinkers', 'Digos City', 3, 'approved', 'Midweek mixers for all levels.', '-40 days');

        $this->organizer('Marites Gonzaga', 'marites@tagumpicklers.ph', 'Tagum Picklers', 'Tagum City', 3, 'pending',
            'We run Wednesday and Friday night sessions for about 20 regulars and want a fair queue instead of a paper list.', '-1 day');
        $this->organizer('Jerome Alcantara', 'jerome@mintalpaddle.ph', 'Mintal Paddle Crew', 'Davao City', 4, 'pending',
            'Weekend beginner clinics followed by open play.', '-2 days');
        $this->organizer('Pia Lacson', 'pia@samaldinkers.ph', 'Samal Island Dinkers', 'Island Garden City of Samal', 2, 'pending',
            'Small group for now, hoping to grow to two nights a week.', '-3 days');

        User::factory()->create(['name' => 'PickleX Admin', 'email' => 'admin@picklex.app', 'role' => 'admin']);

        // Eight weeks of Monday and Saturday sessions at the club.
        $start = Carbon::today()->subWeeks(8);
        foreach (range(0, 15) as $i) {
            $day = $start->copy()->addDays(intdiv($i, 2) * 7 + ($i % 2 ? 5 : 0))->setTime($i % 2 ? 6 : 18, 0);
            if ($day->isFuture() || $day->isToday()) {
                continue;
            }
            $players = $members->shuffle()->take(mt_rand(12, 18))->push($kai)->unique('id');
            $this->playSession($ely, $i % 2 ? 'Saturday Sunrise Rally' : 'Monday Night Open Play', $day, $players, mt_rand(14, 22));
        }

        // Tonight: live for 73 minutes, two waiting players locked as partners, Kai just joined the queue.
        Carbon::setTestNow(now()->subMinutes(73));
        $live = $this->play($ely, 'Monday Night Open Play', now(), 4, 24, 11, 'rotate', '₱150');
        foreach ($members->reject(fn ($m) => $m->is($kai)) as $m) {
            $live->join($m);
        }
        $live->start();
        foreach (range(1, 18) as $g) {
            Carbon::setTestNow(now()->addMinutes(4));
            $this->randomResult($live);
        }
        $live->join($kai);
        $pair = collect($live->queue())->filter(fn ($e) => ! $e['locked'])->take(2)->pluck('id');
        if ($pair->count() === 2) {
            $live->lockPartners(...$pair->all());
        }
        Carbon::setTestNow();

        // Upcoming.
        $sat = Carbon::today()->next(Carbon::SATURDAY);
        $this->withJoins($this->play($ely, 'Saturday Sunrise Rally', $sat->copy()->setTime(6, 0), 3, 18, 11, 'winners', '₱120', 3), $members->take(9));
        $this->withJoins($this->play($davao, "Beginners' Open Court", $sat->copy()->addDay()->setTime(16, 0), 2, 12, 11, 'rotate', null, 3), $members->slice(4, 6));
        $this->withJoins($this->play($digos, 'Midweek Mixer', Carbon::today()->next(Carbon::WEDNESDAY)->setTime(19, 0), 3, 16, 15, 'rotate', '₱100', 3), $members->slice(8, 12));
    }

    private function organizer(string $name, string $email, string $club, string $city, int $courts, string $status, string $note, string $when): User
    {
        return User::factory()->create([
            'name' => $name, 'email' => $email, 'role' => 'organizer', 'organizer_status' => $status,
            'club' => $club, 'city' => $city, 'courts' => $courts, 'organizer_note' => $note,
            'created_at' => now()->modify($when), 'updated_at' => now()->modify($when),
        ]);
    }

    private function play(User $org, string $title, Carbon $at, int $courts, int $cap, int $to, string $rotation, ?string $fee, int $hours = 4): OpenPlay
    {
        return OpenPlay::create([
            'organizer_id' => $org->id, 'title' => $title, 'club' => $org->club, 'venue' => $org->club === self::CLUB ? 'Dinkers Hub, Panabo City' : $org->city,
            'starts_at' => $at, 'ends_at' => $at->copy()->addHours($hours), 'courts' => $courts, 'cap' => $cap,
            'race_to' => $to, 'rotation' => $rotation, 'fee' => $fee,
        ]);
    }

    private function withJoins(OpenPlay $play, $members): void
    {
        foreach ($members as $m) {
            $play->join($m);
        }
    }

    private function playSession(User $org, string $title, Carbon $at, $players, int $games): void
    {
        Carbon::setTestNow($at);
        $play = $this->play($org, $title, $at->copy(), 4, 24, 11, 'rotate', '₱150');
        $this->withJoins($play, $players);
        $play->start();
        foreach (range(1, $games) as $g) {
            Carbon::setTestNow(now()->addMinutes(9));
            $this->randomResult($play);
        }
        $play->end();
        Carbon::setTestNow();
    }

    private function randomResult(OpenPlay $play): void
    {
        $court = collect($play->boardCourts())->filter(fn ($c) => $c['a'])->sortBy('started_at')->first();
        if (! $court) {
            return;
        }
        $loser = mt_rand(2, 9);
        mt_rand(0, 1) ? $play->recordScore($court['n'], $play->race_to, $loser) : $play->recordScore($court['n'], $loser, $play->race_to);
    }
}
