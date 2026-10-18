<?php

namespace Tests\Feature;

use App\Livewire\Auth\Signup;
use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\CourtBoard;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Livewire\Settings;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Every notification switch sends a real email (and only when it's on). */
class NotificationEmailsTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(now()->parse('2026-09-30 17:00'));
        $this->org = User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Panabo']);
    }

    private function play(array $attrs = []): OpenPlay
    {
        return OpenPlay::create($attrs + ['organizer_id' => $this->org->id, 'title' => 'Wednesday Night', 'club' => 'Panabo', 'venue' => 'Gym',
            'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(4), 'courts' => 1, 'cap' => 16]);
    }

    private function off(User $user, string $key): User
    {
        $user->setPref("notify.{$key}", false);
        $user->save();

        return $user;
    }

    private function sent(string $template): Collection
    {
        return Mail::sent(PickleXMail::class, fn ($m) => $m->template === $template);
    }

    private function sentTo(string $template): array
    {
        return $this->sent($template)->map(fn ($m) => $m->to[0]['address'])->sort()->values()->all();
    }

    public function test_settings_say_email_only(): void
    {
        $this->actingAs($this->org)->withSession(['mode' => 'organizer'])->get(route('settings'))
            ->assertSee('A player says they paid')->assertDontSee('A court is open with players waiting')
            ->assertSee("PickleX notifies you by email only, at {$this->org->email}. No push notifications and no text messages.")
            ->assertSee('Account and security emails are always sent.')
            ->assertDontSee('Sent by push and SMS');

        $this->actingAs(User::factory()->create())->get(route('settings'))
            ->assertSee('In case the app isn&#039;t open at the courts', false)
            ->assertSee('(optional)')->assertSee('PickleX never sends texts.')->assertDontSee('for queue alerts');
    }

    public function test_pay_claim_emails_the_organizer_once(): void
    {
        $play = $this->play(['fee' => '₱150', 'pay_qr_version' => 'abcdefabcdef', 'pay_name' => 'Ely Ramos']);
        $kai = User::factory()->create(['name' => 'Kai Santos']);
        $play->join($kai);

        Livewire::actingAs($kai)->test(MemberPlays::class)->call('claimPayment', $play->id)->call('claimPayment', $play->id);
        $this->assertSame([$this->org->email], $this->sentTo('pay-claimed'));
        $mail = $this->sent('pay-claimed')->first();
        $this->assertSame('Kai Santos says they paid for Wednesday Night', $mail->heading);
        $this->assertStringContainsString('Kai says they paid', $mail->render());

        $this->off($this->org, 'payClaims');
        $ana = User::factory()->create();
        $play->join($ana);
        Livewire::actingAs($ana)->test(MemberPlays::class)->call('claimPayment', $play->id);
        $this->assertCount(1, $this->sent('pay-claimed'));
    }

    public function test_session_reminder_goes_out_once_an_hour_before(): void
    {
        $play = $this->play();
        $this->play(['starts_at' => now()->addHours(2)]);
        $quiet = $this->play(['organizer_id' => $this->off(User::factory()->create(['organizer_status' => 'approved']), 'reminder')->id]);

        $this->artisan('open-plays:remind')->expectsOutput('Reminded 2 open plays.');
        $this->artisan('open-plays:remind')->expectsOutput('Reminded 0 open plays.');

        $this->assertSame([$this->org->email], $this->sentTo('session-reminder'));
        $mail = $this->sent('session-reminder')->first();
        $this->assertSame('Wednesday Night starts at 6:00 PM', $mail->heading);
        $this->assertStringContainsString('Before the first game', $mail->render());
        $this->assertNotNull($play->fresh()->reminder_sent_at);
        $this->assertNotNull($quiet->fresh()->reminder_sent_at);
    }

    public function test_going_live_and_up_next_email_the_players(): void
    {
        $play = $this->play(['starts_at' => now()]);
        $players = User::factory(8)->create()->each(fn ($u) => $play->join($u));
        $this->off($players[1], 'liveStart');
        $this->off($players[6], 'upNext');
        $play->participants()->where('user_id', '!=', $players[7]->id)->update(['checked_in_at' => now()]);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->call('startSession', $play->id);
        $this->assertCount(7, $this->sent('live-started'));
        $this->assertNotContains($players[1]->email, $this->sentTo('live-started'));
        $this->assertStringContainsString('Wednesday Night is live', $this->sent('live-started')->first()->render());
        $this->assertCount(0, $this->sent('up-next'), 'Only 3 checked in for the next game');

        // The 8th player checks in: the next game is full, its players hear once.
        Livewire::actingAs($players[7])->test(MemberPlays::class)->call('checkInPlay', $play->id);
        $expected = fn () => collect(($g = $play->fresh()->nextGroup())['full'] ? [...$g['a']['ids'], ...$g['b']['ids']] : [])
            ->map(fn ($id) => $play->participant($id)->user)->reject(fn ($u) => $u->is($players[6]))->pluck('email')->sort()->values()->all();
        $first = $expected();
        $this->assertNotEmpty($first);
        $this->assertSame($first, $this->sentTo('up-next'));
        $this->assertStringContainsString("You're up next", $this->sent('up-next')->first()->render());

        // Board changes that keep the same next game don't repeat it; a new next game does.
        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)->call('toggleAutoFill')->call('toggleAutoFill');
        $this->assertCount(count($first), $this->sent('up-next'));
        $board->call('openScore', 1)->set('scoreA', 11)->set('scoreB', 5)->call('saveScore');
        $this->assertGreaterThan(count($first), $this->sent('up-next')->count());
        $this->assertNotContains($players[6]->email, $this->sentTo('up-next'));
    }

    public function test_organizer_requests_email_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->off(User::factory()->create(['role' => 'admin']), 'requests');

        Livewire::actingAs(User::factory()->create(['name' => 'Mia Cruz']))->test(Settings::class)
            ->set('applyClub', 'Samal Dinkers')->set('applyCity', 'Samal')->call('requestOrganizer');
        $this->assertSame([$admin->email], $this->sentTo('organizer-request'));
        $mail = $this->sent('organizer-request')->first();
        $this->assertSame('New organizer request: Samal Dinkers', $mail->heading);
        $this->assertStringContainsString('Mia Cruz wants to host', $mail->render());

        Livewire::withQueryParams(['role' => 'organizer'])->test(Signup::class)
            ->set(['name' => 'Jo Lim', 'email' => 'jo@example.com', 'password' => 'password123', 'club' => 'Davao Smash', 'city' => 'Davao'])
            ->call('signup');
        $this->assertCount(2, $this->sent('organizer-request'));
    }

    public function test_weekly_emails_on_monday(): void
    {
        // Wednesday: one session, one game.
        $play = $this->play(['starts_at' => now()]);
        $players = User::factory(4)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();
        $play->recordScore(1, 11, 7);
        $play->end();
        foreach ([$players[0], $players[1], $this->org] as $u) {
            $u->setPref('notify.'.($u->is($this->org) ? 'clubRecap' : 'recap'), true);
            $u->save();
        }
        $admin = User::factory()->create(['role' => 'admin', 'preferences' => ['notify' => ['adminRecap' => true]]]);
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['organizer_status' => 'pending']);

        $this->travelTo(now()->parse('2026-10-05 08:00'));
        $this->artisan('emails:weekly')->expectsOutput('Weekly emails for 4 players and 1 organizer.');

        $this->assertSame(collect([$players[0], $players[1]])->pluck('email')->sort()->values()->all(), $this->sentTo('weekly-recap'));
        $recap = $this->sent('weekly-recap')->first();
        $this->assertMatchesRegularExpression('/^Your week on PickleX: 1 game, [01]-[01]$/', $recap->heading);
        $this->assertStringContainsString('Mon, Sep 28 to Sun, Oct 4', $recap->render());

        $this->assertSame([$this->org->email], $this->sentTo('club-summary'));
        $club = $this->sent('club-summary')->first();
        $this->assertSame('Panabo last week: 1 session, 1 game', $club->heading);
        $this->assertStringContainsString('Busiest night', $club->render());

        $this->assertSame([$admin->email], $this->sentTo('platform-summary'));
        $platform = $this->sent('platform-summary')->first();
        $this->assertStringContainsString('1 session', $platform->heading);
        $this->assertStringContainsString('1 organizer request waiting.', $platform->render());

        // Nothing went out the week before.
        Mail::fake();
        $this->travelTo(now()->parse('2026-09-28 08:00'));
        $this->artisan('emails:weekly')->expectsOutput('Weekly emails for 0 players and 0 organizers.');
        $this->assertCount(0, $this->sent('weekly-recap'));
    }
}
