<?php

namespace Database\Seeders;

use App\Models\OpenPlay;
use App\Models\User;
use App\Support\PaidList;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        // A few regulars already picked PickleX avatars, so pictures show up around the app.
        foreach (['Rico Dizon' => 'paddle', 'Mika Lim' => 'happy', 'Gab Navarro' => 'streak', 'Bea Castillo' => 'night', 'Ria Torres' => 'dill', 'Dana Chua' => 'champ'] as $name => $preset) {
            $members->firstWhere('name', $name)->update(['avatar' => "preset:{$preset}"]);
        }

        // Some regulars dressed up their avatar (border) and queue spot (background).
        $looks = [
            'Rico Dizon' => ['gold', null], 'Ben Tan' => ['lime', 'shimmer'], 'Mika Lim' => ['spin', null], 'Jojo Bautista' => ['orbit', 'barber'],
            'Paolo Cruz' => ['court', null], 'Gab Navarro' => ['fire', null], 'Nico Garcia' => ['comet', null],
            'Joy Villanueva' => ['heart', null], 'Ria Torres' => ['kitchen', 'confetti'], 'Enzo Aquino' => [null, 'flow'], 'Marco Rivera' => [null, 'waves'],
        ];
        foreach ($looks as $name => [$frame, $bg]) {
            $members->firstWhere('name', $name)->forceFill(['frame' => $frame, 'queue_bg' => $bg])->save();
        }

        // Sponsors (people who cover some of the app's costs), acknowledged by an admin, wear their tier's look.
        foreach (['Dana Chua' => 'gold', 'Bea Castillo' => 'silver', 'Leo Soriano' => 'bronze'] as $name => $tier) {
            $members->firstWhere('name', $name)->forceFill(['sponsor_tier' => $tier, 'frame' => "sp-{$tier}", 'queue_bg' => "sp-{$tier}"])->save();
        }

        // Two regulars show a display name instead of their full name.
        $members->firstWhere('name', 'Enzo Aquino')->update(['display_name' => 'Zo', 'show_display_name' => true]);
        $members->firstWhere('name', 'Marco Rivera')->update(['display_name' => 'Coach Marco', 'show_display_name' => true]);

        // The app developer's own account: an admin marked it as developer, so the exclusive look is unlocked (and worn).
        User::factory()->create(['name' => 'Jayvee', 'email' => 'jayvee@picklex.app', 'avatar_color' => '#E2D2FF', 'avatar' => 'preset:rocket', 'club' => 'PickleX team'])
            ->forceFill(['is_developer' => true, 'frame' => 'dev', 'queue_bg' => 'dev'])->save();

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
            // Ely organizes and, some nights, plays too (one account, two modes).
            $players = $members->shuffle()->take(mt_rand(12, 18))->push($kai)->when($i % 3 === 0, fn ($c) => $c->push($ely))->unique('id');
            $this->playSession($ely, $i % 2 ? 'Saturday Sunrise Rally' : 'Monday Night Open Play', $day, $players, mt_rand(14, 22));
        }

        // Tonight: live for 73 minutes. Two late arrivals and Kai are in line but not checked in yet,
        // and two waiting players are locked as partners.
        Carbon::setTestNow(now()->subMinutes(73));
        $live = $this->play($ely, 'Monday Night Open Play', now(), 4, 24, 11, 'rotate', '₱150');
        [$early, $late] = $members->reject(fn ($m) => $m->is($kai))->values()->partition(fn ($m, $i) => $i < 17);
        $this->withJoins($live, $early);
        $live->participants()->update(['checked_in_at' => now()]);
        $live->start();
        foreach (range(1, 18) as $g) {
            Carbon::setTestNow(now()->addMinutes(4));
            $this->randomResult($live);
        }
        $this->withJoins($live, $late->push($kai));
        $ready = $live->readyIds();
        $pair = collect($live->queue())->filter(fn ($e) => ! $e['locked'] && OpenPlay::isAvailable($e, $ready))->take(2)->pluck('id');
        if ($pair->count() === 2) {
            $live->lockPartners(...$pair->all());
        }
        Carbon::setTestNow();
        $this->attachDemoPayQr($live);

        // Upcoming.
        $sat = Carbon::today()->next(Carbon::SATURDAY);
        $sunrise = $this->play($ely, 'Saturday Sunrise Rally', $sat->copy()->setTime(6, 0), 3, 18, 11, 'winners', '₱120', 3);
        $this->attachDemoPayQr($sunrise);
        // The organizer linked each paid name to its account (Grace Ong is a guest).
        $paid = "Rico Dizon\nmika@picklex.test\nBen Tan\nCarla Mendoza\nPaolo Cruz\nTin Ramos\nGab Navarro\nLiza Uy\nGrace Ong";
        $links = collect(explode("\n", $paid))->mapWithKeys(fn ($l) => [PaidList::key($l) => User::where('email', $l)->orWhere('name', $l)->value('id') ?? 'guest'])->all();
        $sunrise->addPaidPlayers(PaidList::rows($paid, $links));
        // Three more joined in the app and haven't paid; one says they sent it.
        $inApp = $members->reject(fn ($m) => $m->is($kai) || $sunrise->participants()->where('user_id', $m->id)->exists())->take(3)->values();
        foreach ($inApp as $i => $m) {
            Carbon::setTestNow(now()->addMinutes(20 + $i * 90));
            $sunrise->join($m);
        }
        Carbon::setTestNow();
        DB::table('pay_claims')->insert(['open_play_id' => $sunrise->id, 'user_id' => $inApp[1]->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->withJoins($this->play($davao, "Beginners' Open Court", $sat->copy()->addDay()->setTime(16, 0), 2, 12, 11, 'rotate', null, 3), $members->slice(4, 6));
        $this->withJoins($this->play($digos, 'Midweek Mixer', Carbon::today()->next(Carbon::WEDNESDAY)->setTime(19, 0), 3, 16, 15, 'rotate', '₱100', 3), $members->slice(8, 12));
    }

    /** Demo GCash QR. It encodes a note, not a real payment account. */
    public function attachDemoPayQr(OpenPlay $play): void
    {
        $png = (new QRCode(new QROptions(['outputInterface' => QRGdImagePNG::class, 'outputBase64' => false, 'scale' => 10])))
            ->render('PickleX demo payment QR. Not a real account.');
        DB::table('payment_qrs')->updateOrInsert(['open_play_id' => $play->id], ['data' => base64_encode($png), 'created_at' => now(), 'updated_at' => now()]);
        $play->update(['pay_method' => 'gcash', 'pay_name' => 'Ely Ramos', 'pay_number' => '0917 555 0142', 'pay_qr_version' => substr(sha1($png), 0, 12)]);
    }

    private function organizer(string $name, string $email, string $club, string $city, int $courts, string $status, string $note, string $when): User
    {
        return User::factory()->create([
            'name' => $name, 'email' => $email, 'organizer_status' => $status,
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
        $play->participants()->update(['checked_in_at' => now()]);
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
