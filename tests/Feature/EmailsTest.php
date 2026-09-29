<?php

namespace Tests\Feature;

use App\Livewire\Admin\Requests;
use App\Livewire\Auth\Signup;
use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\CourtBoard;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EmailsTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->org = User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Dink Club']);
    }

    private function play(array $attrs = []): OpenPlay
    {
        return OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Friday Rally', 'club' => 'Dink Club', 'venue' => 'Court A',
            'starts_at' => now()->addDay()->setTime(18, 0), 'ends_at' => now()->addDay()->setTime(21, 0), 'courts' => 1, 'cap' => 8,
        ]);
    }

    private function sent(string $template, User $to): PickleXMail
    {
        $mail = Mail::sent(PickleXMail::class, fn ($m) => $m->template === $template && $m->hasTo($to->email))->first();
        $this->assertNotNull($mail, "No {$template} email to {$to->email}");

        return $mail;
    }

    public function test_signup_sends_a_welcome_email(): void
    {
        $this->play(['visibility' => 'public']);

        Livewire::test(Signup::class)
            ->set('name', 'Kai Santos')->set('email', 'kai@example.com')->set('password', 'Dinks4days!')
            ->call('signup');

        $mail = $this->sent('welcome', User::where('email', 'kai@example.com')->first());
        $this->assertSame('Welcome to PickleX, Kai', $mail->heading);
        $html = $mail->render();
        $this->assertStringContainsString('Welcome to the queue, Kai.', $html);
        $this->assertStringContainsString('Friday Rally', $html);
        $this->assertStringContainsString("Join an open play, check in at the courts, and we'll tell you when you're up.", html_entity_decode($html, ENT_QUOTES));
    }

    public function test_admin_decisions_email_the_applicant_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pia = User::factory()->create(['name' => 'Pia Lacson', 'organizer_status' => 'pending', 'club' => 'Samal Dinkers', 'city' => 'Samal', 'courts' => 2]);

        Livewire::actingAs($admin)->test(Requests::class)->call('approve', $pia->id)->call('approve', $pia->id);
        $this->assertCount(1, Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'organizer-approved'));
        $this->assertStringContainsString("You're approved, Pia.", html_entity_decode($this->sent('organizer-approved', $pia)->render(), ENT_QUOTES));

        Livewire::actingAs($admin)->test(Requests::class)->call('decline', $pia->id)->call('reopen', $pia->id);
        $this->assertSame('About your organizer request for Samal Dinkers', $this->sent('organizer-declined', $pia)->heading);
        Mail::assertSent(PickleXMail::class, 2);
    }

    public function test_joining_emails_the_member_and_the_organizer(): void
    {
        $play = $this->play(['fee' => '₱120']);
        $kai = User::factory()->create(['name' => 'Kai Santos']);

        Livewire::actingAs($kai)->test(MemberPlays::class)->call('joinPlay', $play->id);

        $mine = $this->sent('joined', $kai);
        $this->assertSame("You're in: Friday Rally, {$play->starts_at->format('D M j')}", $mine->heading);
        $this->assertStringContainsString('Not paid yet', $mine->render());

        $alert = $this->sent('player-joined', $this->org);
        $this->assertSame('Kai Santos joined Friday Rally', $alert->heading);
        $this->assertStringContainsString("1 of 8 spots filled. Kai hasn't paid yet.", $alert->preheader);
        $alert->render();
    }

    public function test_leaving_and_rejoining_sends_the_join_emails_once(): void
    {
        $play = $this->play();
        $kai = User::factory()->create();

        Livewire::actingAs($kai)->test(MemberPlays::class)
            ->call('joinPlay', $play->id)->call('leavePlay', $play->id)->call('joinPlay', $play->id);

        $this->assertTrue($play->activeParticipants()->where('user_id', $kai->id)->exists());
        $this->assertCount(1, Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'player-joined'));
        $this->assertCount(1, Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'joined'));
    }

    public function test_organizers_can_turn_off_join_alerts(): void
    {
        $this->org->setPref('notify.joins', false);
        $this->org->save();
        $play = $this->play();

        Livewire::actingAs($kai = User::factory()->create())->test(MemberPlays::class)->call('joinPlay', $play->id);

        $this->sent('joined', $kai);
        Mail::assertNotSent(PickleXMail::class, fn ($m) => $m->template === 'player-joined');
    }

    public function test_publishing_announces_to_players_from_earlier_open_plays(): void
    {
        $old = $this->play(['status' => 'done', 'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(3)]);
        [$regular, $quiet, $stranger] = User::factory(3)->create();
        $quiet->setPref('notify.newPlays', false);
        $quiet->save();
        foreach ([$regular, $quiet] as $u) {
            $old->participants()->create(['user_id' => $u->id, 'name' => $u->name]);
        }

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('openCreate')->set('title', 'Sunrise Rally')->set('venue', 'Court B')->call('publish');

        $mail = $this->sent('new-play', $regular);
        $this->assertStringStartsWith('New open play: Sunrise Rally, ', $mail->heading);
        $this->assertSame('Dink Club via PickleX', $mail->fromName);
        $mail->render();
        Mail::assertNotSent(PickleXMail::class, fn ($m) => $m->hasTo($quiet->email) || $m->hasTo($stranger->email) || $m->hasTo($this->org->email));
    }

    public function test_ending_a_session_sends_recaps(): void
    {
        $play = $this->play(['fee' => '₱150', 'courts' => 1]);
        $players = User::factory(4)->create();
        $players->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->participants()->where('user_id', $players[0]->id)->update(['paid' => true]);
        $play->start();
        $play->recordScore(1, 11, 7);

        $players[3]->setPref('notify.sessionRecap', false);
        $players[3]->save();

        Livewire::actingAs($this->org)->test(CourtBoard::class)->call('endSession');

        foreach ($players->take(3) as $u) {
            $mail = $this->sent('recap', $u);
            $this->assertStringStartsWith('Your Friday Rally recap: ', $mail->heading);
            $this->assertStringContainsString('See full standings', $mail->render());
        }
        Mail::assertNotSent(PickleXMail::class, fn ($m) => $m->template === 'recap' && $m->hasTo($players[3]->email));

        $org = $this->sent('recap-organizer', $this->org);
        $this->assertSame('Friday Rally recap: 4 players, 1 games', $org->heading);
        $html = html_entity_decode($org->render(), ENT_QUOTES);
        $this->assertStringContainsString('₱150 collected', $html);
        $this->assertStringContainsString('3 to follow up:', $html);
        $this->assertStringContainsString('Download standings as CSV', $html);
    }
}
