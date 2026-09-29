<?php

namespace Tests\Feature;

use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EditOpenPlayTest extends TestCase
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
            'starts_at' => now()->addDays(2)->setTime(18, 0), 'ends_at' => now()->addDays(2)->setTime(21, 0), 'courts' => 2, 'cap' => 12,
        ]);
    }

    public function test_create_goes_through_a_review_before_publishing(): void
    {
        $c = Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('openCreate')->set('title', 'Sunrise Rally')->set('venue', 'Court B')->set('fee', '150')
            ->call('showReview')
            ->assertSet('review', true)
            ->assertSee('Review open play')->assertSee('Check the details before you publish')
            ->assertSee('₱150 per player')->assertSee('No QR, players pay at the venue')
            ->assertSee('Publish open play');
        $this->assertSame(0, OpenPlay::count());

        $c->call('backToForm')->assertSet('review', false)->assertSee('Review details')
            ->call('showReview')->call('publish')
            ->assertSet('create', false)
            ->assertDispatched('toast', message: 'Open play published. Members can join now.');
        $this->assertSame(1, OpenPlay::count());
    }

    public function test_action_names_do_not_clash_with_properties(): void
    {
        // $wire.review would read the $review property instead of calling a method of the same name.
        $props = array_map(fn ($p) => $p->getName(), (new \ReflectionClass(OrganizerPlays::class))->getProperties(\ReflectionProperty::IS_PUBLIC));
        foreach ((new \ReflectionClass(OrganizerPlays::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            $this->assertNotContains($m->getName(), $props, "{$m->getName()} is both a property and an action");
        }
    }

    public function test_editing_keeps_the_same_qr_version(): void
    {
        $play = $this->play(['fee' => '₱120', 'pay_method' => 'gcash', 'pay_name' => 'Ely Ramos', 'pay_qr_version' => 'aaaaaaaaaaaa']);
        DB::table('payment_qrs')->insert(['open_play_id' => $play->id, 'data' => base64_encode('png-bytes'), 'created_at' => now(), 'updated_at' => now()]);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('edit', $play->id)->set('title', 'Friday Rally 2')->call('showReview', 'saved')->call('publish', 'saved');

        $this->assertSame(['Friday Rally 2', 'aaaaaaaaaaaa'], [$play->fresh()->title, $play->fresh()->pay_qr_version]);
    }

    public function test_review_catches_errors_first(): void
    {
        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('openCreate')->call('showReview')
            ->assertSet('review', false)->assertSet('error', 'Add a title and a venue so members know where to go.');
    }

    public function test_editing_highlights_changes_and_emails_players(): void
    {
        $play = $this->play(['fee' => '₱120']);
        $mika = User::factory()->create(['name' => 'Mika Lim']);
        $play->join($mika);
        $play->participants()->where('user_id', $mika->id)->update(['paid' => true]);
        $play->participants()->create(['name' => 'Walk In Guest']);

        $c = Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('edit', $play->id)
            ->assertSet('editId', $play->id)->assertSet('title', 'Friday Rally')->assertSet('starts', '18:00')
            ->assertSet('paid', $mika->email)
            ->assertSee('Edit open play')->assertSee('Delete this open play')
            ->call('showReview')->assertSee('Nothing has changed yet')->assertSeeHtml('disabled');

        $c->call('backToForm')->set('starts', '19:00')->set('ends', '22:00')->call('showReview')
            ->assertSee('Review changes')->assertSee('1 change to review')->assertSee('CHANGED')
            ->assertSee('1 player who joined gets an email about what changed.')
            ->call('publish')
            ->assertDispatched('toast', message: 'Friday Rally updated · 1 player notified');

        $this->assertSame('19:00', $play->fresh()->starts_at->format('H:i'));
        $this->assertSame(2, $play->activeParticipants()->count(), 'paid players are not added twice');

        $mail = Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'play-updated' && $m->hasTo($mika->email))->first();
        $this->assertNotNull($mail);
        $this->assertSame('New time: Friday Rally now starts at 7:00 PM', $mail->heading);
        $this->assertSame('Dink Club via PickleX', $mail->fromName);
        $html = html_entity_decode($mail->render(), ENT_QUOTES);
        $this->assertStringContainsString('Was ', $html);
        $this->assertStringContainsString('Your ₱120 payment still counts.', $html);
    }

    public function test_cap_changes_and_cap_only_edits_dont_email(): void
    {
        $play = $this->play(['cap' => 8]);
        User::factory(5)->create()->each(fn ($u) => $play->join($u));

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('edit', $play->id)->set('cap', 4)->call('showReview')
            ->assertSet('error', '5 players are already in this open play. Set the player cap to at least 5.')
            ->set('cap', 10)->call('showReview')->assertSee('1 change to review')->assertDontSee('get an email')
            ->call('publish')->assertDispatched('toast', message: 'Friday Rally updated');

        $this->assertSame(10, $play->fresh()->cap);
        Mail::assertNotSent(PickleXMail::class, fn ($m) => $m->template === 'play-updated');
    }

    public function test_deleting_warns_and_emails_players(): void
    {
        $play = $this->play(['fee' => '₱120']);
        [$mika, $ben] = User::factory(2)->create();
        $play->join($mika);
        $play->join($ben);
        $play->participants()->where('user_id', $mika->id)->update(['paid' => true]);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('askDelete', $play->id)
            ->assertSee('Delete Friday Rally?')->assertSee('2 players who joined get an email')
            ->assertSee('1 player has already paid ₱120.', false)
            ->call('cancelDelete')->assertDontSee('Delete Friday Rally?')
            ->call('askDelete', $play->id)->call('deletePlay')
            ->assertDispatched('toast', message: 'Friday Rally deleted · 2 players notified');

        $this->assertNull(OpenPlay::find($play->id));
        $paidMail = Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'play-cancelled' && $m->hasTo($mika->email))->first();
        $this->assertSame('Cancelled: Friday Rally on '.$play->starts_at->format('D, M j'), $paidMail->heading);
        $this->assertStringContainsString('About your ₱120 payment', html_entity_decode($paidMail->render(), ENT_QUOTES));
        $unpaid = Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'play-cancelled' && $m->hasTo($ben->email))->first();
        $this->assertStringNotContainsString('About your', html_entity_decode($unpaid->render(), ENT_QUOTES));
    }

    public function test_only_your_upcoming_plays_can_be_edited_or_deleted(): void
    {
        $live = $this->play(['status' => 'live']);
        $other = OpenPlay::create(['organizer_id' => User::factory()->create(['organizer_status' => 'approved'])->id, 'title' => 'Theirs', 'club' => 'X', 'venue' => 'Y',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'courts' => 1, 'cap' => 8]);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->call('edit', $live->id)->assertNotFound();
        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->call('askDelete', $other->id)->assertNotFound();
    }
}
