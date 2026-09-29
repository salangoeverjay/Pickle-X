<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

/** Meet the developer: a public page about the person who builds PickleX. */
#[Title('Meet the developer · PickleX')]
class About extends Component
{
    public function render()
    {
        return view('livewire.about', [
            'back' => auth()->check() ? route(auth()->user()->homeRoute()) : route('home'),
        ]);
    }
}
