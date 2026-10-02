<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users;
use App\Livewire\Auth\Login;
use App\Mail\PickleXMail;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\OpenPlay;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class UsersAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->super = User::factory()->create(['name' => 'Pat Admin', 'role' => 'admin']);
    }

    public function test_only_admins_see_users(): void
    {
        $member = User::factory()->create();
        $organizer = User::factory()->create(['organizer_status' => 'approved']);

        $this->actingAs($member)->get(route('admin.users'))->assertForbidden();
        $this->actingAs($organizer)->get(route('admin.users'))->assertForbidden();
        $this->actingAs($this->super)->get(route('admin.requests'))->assertSee(route('admin.users'));
        $this->actingAs($this->super)->get(route('admin.users'))->assertOk()
            ->assertSee('Change roles, suspend or restore accounts')->assertDontSee('Super admin');
    }

    public function test_lists_filters_and_searches_accounts(): void
    {
        User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Panabo']);
        User::factory()->create(['name' => 'Toni Chan', 'suspended_at' => now()]);
        User::factory()->create(['name' => 'Kai Santos']);

        Livewire::actingAs($this->super)->test(Users::class)
            ->assertSee('4 accounts')->assertSeeInOrder(['Pat Admin', 'YOU', 'Ely Ramos', 'Kai Santos', 'Toni Chan'])
            ->set('tab', 'organizer')->assertSee('Ely Ramos')->assertDontSee('Kai Santos')->assertSee('1 of 4 accounts')
            ->set('tab', 'suspended')->assertSee('Toni Chan')->assertDontSee('Ely Ramos')
            ->set('tab', 'all')->set('q', 'kai')->assertSee('Kai Santos')->assertDontSee('Toni Chan')
            ->set('q', 'zzz')->assertSee('No accounts match "zzz".');
    }

    public function test_the_list_pages_by_ten(): void
    {
        foreach (range(1, 11) as $i) {
            User::factory()->create(['name' => sprintf('Player %02d', $i)]);
        }

        Livewire::actingAs($this->super)->test(Users::class)
            ->assertSee('Showing 1-10 of 12 accounts')->assertSee('Page 1 of 2')->assertSee('Player 09')->assertDontSee('Player 11')
            ->call('nextPage', 'usersPage')->assertSee('Showing 11-12 of 12 accounts')->assertSee('Player 11')->assertDontSee('Player 09')
            ->set('q', 'player 1')->assertSee('Showing 1-2 of 2 accounts')->assertSee('Page 1 of 1');
    }

    public function test_roles_change_but_never_your_own(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos', 'club' => 'Panabo']);

        Livewire::actingAs($this->super)->test(Users::class)
            ->call('open', $kai->id)->assertSee('Also hosts open plays and runs the court board for Panabo.')
            ->call('pickRole', 'organizer')->call('saveRole')->assertDispatched('toast', message: 'Kai Santos is now an Organizer · we emailed Kai')
            ->call('pickRole', 'admin')->assertSee('Reviews organizer requests and manages users, developers and sponsors.')
            ->call('saveRole')->assertDispatched('toast', message: 'Kai Santos is now an Admin · we emailed Kai');
        $this->assertSame('admin', $kai->fresh()->accountRole());
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'role-changed' && $m->hasTo($kai->email)
            && $m->heading === 'Your PickleX role changed to Organizer' && str_contains($m->render(), "You're now an Organizer, Kai")
            && str_contains($m->render(), 'host open plays for Panabo'));
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'role-changed' && $m->data['was'] === 'organizer' && $m->data['role'] === 'admin'
            && str_contains($m->render(), 'Open the admin dashboard'));

        Livewire::actingAs($this->super)->test(Users::class)->call('open', $this->super->id)
            ->assertSee('This is your account. Another admin has to change your role.')
            ->call('pickRole', 'member')->call('saveRole')->assertForbidden();
        $this->assertSame('admin', $this->super->fresh()->accountRole());
    }

    public function test_suspended_accounts_are_signed_out_and_cannot_log_in(): void
    {
        $kai = User::factory()->create(['email' => 'kai@example.com', 'password' => 'password', 'remember_token' => 'old-token']);
        DB::table('sessions')->insert(['id' => 's1', 'user_id' => $kai->id, 'payload' => '', 'last_activity' => time()]);
        $next = OpenPlay::create(['organizer_id' => $this->super->id, 'title' => 'Friday', 'club' => 'Club', 'venue' => 'Gym',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'courts' => 1, 'cap' => 8]);
        $next->join($kai);

        Livewire::actingAs($this->super)->test(Users::class)->call('open', $kai->id)->call('suspend')
            ->assertDispatched('toast', message: "{$kai->name} is suspended and can't log in · we emailed {$kai->firstName()}")->assertSee('Restore');
        $this->assertSame(0, $next->activeParticipants()->count());
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'account-suspended' && $m->hasTo('kai@example.com')
            && str_contains($m->render(), 'Your account is suspended'));
        $this->assertTrue($kai->fresh()->isSuspended());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $kai->id)->count());
        $this->assertNotSame('old-token', $kai->fresh()->remember_token);

        Auth::logout();
        Livewire::test(Login::class)->set('email', 'kai@example.com')->set('password', 'password')->call('login')
            ->assertHasErrors(['email' => Login::SUSPENDED]);
        $this->assertGuest();

        Livewire::actingAs($this->super)->test(Users::class)->call('open', $kai->id)->call('restore');
        Auth::logout();
        Livewire::test(Login::class)->set('email', 'kai@example.com')->set('password', 'password')->call('login')->assertHasNoErrors();
    }

    public function test_remember_me_sets_a_30_day_cookie(): void
    {
        User::factory()->create(['email' => 'kai@example.com', 'password' => 'password']);
        Livewire::test(Login::class)->set('email', 'kai@example.com')->set('password', 'password')->set('remember', true)->call('login');

        $this->assertNotNull(auth()->user()->remember_token);
        $this->assertSame(60 * 24 * 30, config('auth.guards.web.remember'));
    }

    public function test_password_reset_and_sign_out_everywhere(): void
    {
        Mail::fake();
        $kai = User::factory()->create(['name' => 'Kai Santos']);
        DB::table('sessions')->insert(['id' => 's2', 'user_id' => $kai->id, 'payload' => '', 'last_activity' => time()]);

        Livewire::actingAs($this->super)->test(Users::class)->call('open', $kai->id)
            ->call('sendReset')->assertDispatched('toast', message: "Password reset email sent to {$kai->email}")
            ->call('signOut')->assertDispatched('toast', message: 'Signed Kai out on every device · Remember me cleared');

        $this->assertTrue(DB::table('password_reset_tokens')->where('email', $kai->email)->exists());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $kai->id)->count());
    }

    public function test_deleting_keeps_games_as_former_player(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);
        $play = OpenPlay::create(['organizer_id' => $this->super->id, 'title' => 'Night', 'club' => 'Club', 'venue' => 'Gym',
            'starts_at' => now(), 'ends_at' => now()->addHours(2), 'courts' => 1, 'cap' => 8, 'status' => 'done']);
        $p = Participant::create(['open_play_id' => $play->id, 'user_id' => $kai->id, 'name' => $kai->name]);
        $game = Game::create(['open_play_id' => $play->id, 'court' => 1, 'score_a' => 11, 'score_b' => 4, 'winner' => 'a', 'started_at' => now(), 'played_at' => now()]);
        GamePlayer::create(['game_id' => $game->id, 'participant_id' => $p->id, 'user_id' => $kai->id, 'team' => 'a', 'won' => true]);

        Livewire::actingAs($this->super)->test(Users::class)->call('open', $kai->id)
            ->set('confirmDelete', true)->assertSee("Delete Kai Santos's account?", false)
            ->call('delete')->assertDispatched('toast', message: "Deleted Kai Santos's account · we emailed Kai");
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'account-deleted' && $m->hasTo($kai->email) && $m->data['byAdmin']
            && str_contains($m->render(), 'Your account was deleted'));

        $this->assertModelMissing($kai);
        $this->assertSame('Former player', $p->fresh()->name);
        $this->assertNull($p->fresh()->user_id);
        $this->assertSame(1, GamePlayer::where('game_id', $game->id)->count());
    }
}
