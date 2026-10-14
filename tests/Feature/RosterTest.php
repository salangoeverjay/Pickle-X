<?php

namespace Tests\Feature;

use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Models\OpenPlay;
use App\Models\User;
use App\Support\PaidList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RosterTest extends TestCase
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
            'starts_at' => now()->addDays(2)->setTime(18, 0), 'ends_at' => now()->addDays(2)->setTime(21, 0), 'courts' => 2, 'cap' => 12, 'fee' => '₱120',
        ]);
    }

    public function test_roster_lists_players_in_join_order_with_payment(): void
    {
        $play = $this->play();
        $mika = User::factory()->create(['name' => 'Mika Lim', 'email' => 'mika@example.com']);
        $ben = User::factory()->create(['name' => 'Ben Tan']);
        $play->join($mika);
        $this->travel(1)->minutes();
        $play->join($ben);
        $this->travel(1)->minutes();
        $play->addPaidPlayers(PaidList::rows('Grace Ong', [PaidList::key('Grace Ong') => 'guest']));
        DB::table('pay_claims')->insert(['open_play_id' => $play->id, 'user_id' => $ben->id, 'created_at' => now(), 'updated_at' => now()]);

        $c = Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->assertSee("See who's in")
            ->call('openRoster', $play->id)
            ->assertSee('Upcoming open play · players')
            ->assertSeeInOrder(['Mika Lim', 'Ben Tan', 'Grace Ong'])
            ->assertSee('mika@example.com')->assertSee('Joined in the app')->assertSee('Guest · no PickleX account · from your paid list')
            ->assertSee('Says they sent ₱120')
            ->assertSee('₱120')->assertSee('of ₱360');

        $c->set('rosterF', 'unpaid')->assertSee('2 of 3 players')->assertDontSee('Grace Ong')
            ->set('rosterF', 'all')->set('rosterQ', 'mika@')->assertSee('1 of 3 players')->assertDontSee('Ben Tan')
            ->set('rosterQ', 'zzz')->assertSee('No one here matches');

        $ben2 = $play->participants()->where('user_id', $ben->id)->first();
        $c->set('rosterQ', '')->call('setPaid', $ben2->id, true)->assertDispatched('toast', message: "{$ben2->name} marked as paid");
        $this->assertTrue($ben2->fresh()->paid);

        $c->call('setPaid', $ben2->id, false);
        $this->assertFalse($ben2->fresh()->paid);
        $this->assertSame(0, DB::table('pay_claims')->where('user_id', $ben->id)->count(), 'not paid clears the claim');

        $c->call('rosterEdit')->assertSet('rosterId', null)->assertSet('editId', $play->id)->assertSet('create', true);
    }

    public function test_free_plays_have_no_payment_and_paid_list_members_are_marked(): void
    {
        $play = $this->play(['fee' => null]);
        $kai = User::factory()->create(['name' => 'Kai Reyes', 'role' => 'member']);
        $play->addPaidPlayers(PaidList::rows($kai->email, [PaidList::key($kai->email) => $kai->id]));

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->call('openRoster', $play->id)
            ->assertSee('PickleX accounts')->assertSee('nothing to collect')->assertSee('Added from your paid list')
            ->assertDontSee('Unpaid');
    }

    public function test_only_your_upcoming_plays(): void
    {
        $other = OpenPlay::create(['organizer_id' => User::factory()->create(['organizer_status' => 'approved'])->id, 'title' => 'Theirs', 'club' => 'X', 'venue' => 'Y',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'courts' => 1, 'cap' => 8]);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->call('openRoster', $other->id)->assertNotFound();
    }
}
