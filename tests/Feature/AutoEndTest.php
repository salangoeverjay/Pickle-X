<?php

namespace Tests\Feature;

use App\Livewire\Organizer\CourtBoard;
use App\Livewire\Organizer\OpenPlays;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AutoEndTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(now()->parse('2026-09-28 18:00'));
        $this->org = User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Panabo']);
    }

    /** A live session that ends at 10:00 PM tonight. */
    private function live(): OpenPlay
    {
        $play = OpenPlay::create(['organizer_id' => $this->org->id, 'title' => 'Monday Night', 'club' => 'Panabo', 'venue' => 'Gym',
            'starts_at' => now(), 'ends_at' => now()->setTime(22, 0), 'courts' => 1, 'cap' => 8]);
        User::factory(4)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();

        return $play;
    }

    public function test_the_board_notes_when_it_ends_and_warns_in_the_last_hour(): void
    {
        $this->live();
        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Ends on its own at 10:00 AM tomorrow, 12 hours after the 10:00 PM end time.')->assertDontSee('End it now');

        $this->travelTo(now()->parse('2026-09-29 09:35'));
        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Ends automatically in 25 minutes (12 hours after the 10:00 PM end time).')->assertSee('End it now')
            ->assertDontSee('Ends on its own at');
    }

    public function test_the_scheduler_ends_it_12_hours_after_its_end_time(): void
    {
        $play = $this->live();

        $this->travelTo(now()->parse('2026-09-29 09:59'));
        $this->artisan('open-plays:auto-end')->expectsOutput('Ended 0 open plays automatically.');
        $this->assertSame('live', $play->fresh()->status);

        $this->travelTo(now()->parse('2026-09-29 10:00'));
        $this->artisan('open-plays:auto-end')->expectsOutput('Ended 1 open play automatically.');
        $play->refresh();
        $this->assertSame('done', $play->status);
        $this->assertTrue($play->auto_ended);

        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'auto-ended' && $m->hasTo($this->org->email)
            && $m->heading === 'Monday Night ended automatically' && str_contains($m->render(), 'We ended Monday Night for you'));
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'recap-organizer');

        Livewire::actingAs($this->org)->test(OpenPlays::class)->assertSee('Ended automatically at 10:00 AM, 12 hours after its end time');
    }

    public function test_opening_the_board_after_the_deadline_ends_it_too(): void
    {
        $play = $this->live();
        $this->travelTo(now()->parse('2026-09-29 10:05'));

        Livewire::actingAs($this->org)->test(CourtBoard::class)->assertRedirect(route('organizer.plays'));
        $this->assertTrue($play->fresh()->auto_ended);
        $this->assertSame('Monday Night ended automatically, 12 hours after its 10:00 PM end time', session('toast'));
    }
}
