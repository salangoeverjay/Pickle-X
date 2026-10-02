<?php

namespace App\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

/** Pages for lists that are already in memory (x-pager renders them). One page name per list. */
trait Pages
{
    use WithPagination;

    protected function pageOf(iterable $items, int $per, string $name): LengthAwarePaginator
    {
        $items = $items instanceof Collection ? $items->values() : collect($items)->values();
        $page = min(max(1, $this->getPage($name)), max(1, (int) ceil($items->count() / $per)));

        return new LengthAwarePaginator($items->forPage($page, $per)->values(), $items->count(), $per, $page, ['pageName' => $name]);
    }
}
