<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('PickleX | Open play, organized')]
class Home extends Component
{
    public function render()
    {
        // Sample queue for the hero's scrolling ticker.
        $queue = collect([
            ['Mika Lim', '#FFEFA3'], ['Ben Tan', '#E2D2FF'], ['Ria Torres', '#CDEBB0'], ['Kai Santos', '#BCD9F7'],
            ['Jojo Bautista', '#BDEBE3'], ['Leo Soriano', '#E2D2FF'], ['Dana Chua', '#FFEFA3'], ['Ana Reyes', '#FFCBBB'],
        ])->map(fn ($p, $i) => [
            'pos' => $i + 1,
            'name' => $p[0],
            'initials' => collect(explode(' ', $p[0]))->map(fn ($w) => $w[0])->join(''),
            'color' => $p[1],
        ]);

        return view('livewire.home', [
            // Doubled so the CSS loop (-50%) is seamless.
            'ticker' => $queue->concat($queue),
            'waiting' => $queue->count(),
            'marquee' => ['Open play', 'Fair queue', 'Partner lock', 'Live courts', 'Real stats', 'No DUPR'],
        ]);
    }
}
