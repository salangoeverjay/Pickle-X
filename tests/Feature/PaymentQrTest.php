<?php

namespace Tests\Feature;

use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentQrTest extends TestCase
{
    use RefreshDatabase;

    private function qrDataUrl(): string
    {
        $img = imagecreatetruecolor(300, 300);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,'.base64_encode(ob_get_clean());
    }

    private function publish(User $org, array $fields, ?string $qr)
    {
        return Livewire::actingAs($org)->test(OrganizerPlays::class)
            ->set($fields + ['title' => 'Paid Night', 'venue' => 'Court A', 'date' => now()->addDay()->format('Y-m-d')])
            ->call('publish', $qr);
    }

    public function test_publishing_with_a_payment_qr(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);

        $this->publish($org, ['payName' => 'Ely Ramos'], $this->qrDataUrl())
            ->assertSet('error', 'Add the fee per player so people know how much to send to your QR.');
        $this->publish($org, ['fee' => '150'], $this->qrDataUrl())
            ->assertSet('error', 'Add the account name on your QR so players can double-check before they pay.');
        $this->publish($org, ['fee' => '150', 'payName' => 'Ely Ramos'], 'data:image/png;base64,bm9wZQ==')
            ->assertSet('error', "We couldn't use that QR image. Upload a PNG, JPG or WebP screenshot of your QR.");

        $this->publish($org, ['fee' => '150', 'payName' => 'Ely Ramos', 'payNumber' => '0917 555 0142', 'payMethod' => 'maya'], $this->qrDataUrl())
            ->assertSet('error', null)
            ->assertDispatched('toast', message: 'Open play published with a Maya QR. Members can join now.');

        $play = OpenPlay::firstWhere('title', 'Paid Night');
        $this->assertSame(['maya', 'Ely Ramos', '0917 555 0142', '₱150'], [$play->pay_method, $play->pay_name, $play->pay_number, $play->fee]);
        $this->actingAs($org)->get($play->payQrUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get($play->payQrUrl().'?download=1')->assertHeader('Content-Disposition', 'attachment; filename="picklex-paid-night-qr.png"');

        // The next play starts from the saved QR and details.
        Livewire::actingAs($org)->test(OrganizerPlays::class)
            ->assertSet('payName', 'Ely Ramos')->assertSet('payMethod', 'maya')
            ->assertSee('Saved from your last open play')
            ->set(['title' => 'Paid Night 2', 'venue' => 'Court A', 'date' => now()->addDays(2)->format('Y-m-d'), 'fee' => '₱150'])
            ->call('publish', 'saved')->assertSet('error', null);
        $this->assertSame($play->pay_qr_version, OpenPlay::firstWhere('title', 'Paid Night 2')->pay_qr_version);
    }

    public function test_members_pay_claim_and_see_paid(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);
        $this->publish($org, ['fee' => '150', 'payName' => 'Ely Ramos'], $this->qrDataUrl());
        $play = OpenPlay::firstWhere('title', 'Paid Night');

        $member = User::factory()->create();
        Livewire::actingAs($member)->test(MemberPlays::class)
            ->assertSee('Pay ₱150')
            ->call('openPay', $play->id)
            ->assertSee('Pay with GCash')->assertSee('Send to')->assertSee('Ely Ramos')->assertSee('Save QR')
            ->call('claimPayment', $play->id)
            ->assertDispatched('toast', message: 'Thanks. The organizer will mark you as paid once they see it.')
            ->assertSet('payFor', null)
            ->assertSee('Payment sent · view QR');
        $this->assertDatabaseHas('pay_claims', ['open_play_id' => $play->id, 'user_id' => $member->id]);

        // On the organizer's paid list: shows Paid instead of the Pay button.
        $play->addPaidPlayers([['line' => $member->name, 'user' => $member, 'how' => 'name', 'name' => $member->name]]);
        Livewire::actingAs($member)->test(MemberPlays::class)->assertDontSee('Pay ₱150')->assertSeeHtml('Paid</span>');

        // No QR on this play: nothing to open.
        $plain = OpenPlay::create(['organizer_id' => $org->id, 'title' => 'Free Play', 'club' => 'Dink Club', 'venue' => 'Court A', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'courts' => 2, 'cap' => 12]);
        Livewire::actingAs($member)->test(MemberPlays::class)->call('openPay', $plain->id)->assertNotFound();
    }
}
