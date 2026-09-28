@props(['active' => false])

<button type="button" role="tab" aria-selected="{{ $active ? 'true' : 'false' }}" {{ $attributes->class([
    'inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-full px-4.5 text-sm font-bold transition-colors',
    'bg-ink text-bg' => $active,
    'text-ink2 hover:text-ink' => ! $active,
]) }}>{{ $slot }}</button>
