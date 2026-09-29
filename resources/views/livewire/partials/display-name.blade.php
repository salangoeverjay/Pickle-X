{{-- Name other players see: full name or display name. Local until Save profile; the preview follows the fields. --}}
@php($url = $user->avatarUrl())
<div id="set-dname" role="group" aria-labelledby="dn-h"
     x-data="{
        get d() { return ($wire.displayName || '').trim() },
        get full() { return ($wire.name || '').trim() },
        get useD() { return $wire.showAs === 'display' && this.d !== '' },
        get shown() { return this.useD ? this.d : (this.full || 'Your name') },
        get letters() { const p = this.shown.split(/\s+/); return ((p[0] || '?').charAt(0) + (p[1] || '').charAt(0)).toUpperCase() },
        pick(o) {
            if (o === 'display' && ! this.d) { document.getElementById('sf-dname')?.focus(); $dispatch('toast', { message: 'Type a display name first' }); return }
            $wire.$set('showAs', o, false)
        },
     }"
     class="flex scroll-mt-5 flex-col gap-3.5 rounded-[22px] border border-line bg-bg p-3.5 lg:p-5">
    <div class="flex flex-col gap-0.75">
        <h3 id="dn-h" class="m-0 font-display text-lg font-bold tracking-[-0.02em]">Name other players see</h3>
        <span class="text-[13px] leading-[1.4] text-ink3">Shown in the queue, rankings and open plays.</span>
    </div>
    <div role="radiogroup" aria-labelledby="dn-h" class="grid gap-2.5 lg:grid-cols-2">
        @foreach (['full' => 'Full name', 'display' => 'Display name'] as $o => $label)
            <button type="button" role="radio" x-on:click="pick('{{ $o }}')"
                    :aria-checked="(($wire.showAs === 'display' ? 'display' : 'full') === '{{ $o }}').toString()"
                    @if ($o === 'display') :aria-disabled="(! d).toString()" :class="! d && 'opacity-55'" @endif
                    class="flex min-h-16.5 min-w-0 cursor-pointer items-center gap-3 rounded-2xl border-2 px-3.5 py-2.5 text-left text-ink transition active:scale-97"
                    x-bind:style="(($wire.showAs === 'display' ? 'display' : 'full') === '{{ $o }}') ? 'border-color: var(--color-ink); background: var(--color-surface)' : 'border-color: var(--color-line)'">
                <span aria-hidden="true" class="flex size-5.5 shrink-0 items-center justify-center rounded-full border-2"
                      x-bind:style="'border-color: ' + ((($wire.showAs === 'display' ? 'display' : 'full') === '{{ $o }}') ? 'var(--color-ink)' : 'var(--color-line2)')">
                    <span x-show="($wire.showAs === 'display' ? 'display' : 'full') === '{{ $o }}'" class="size-2.5 animate-snap rounded-full bg-ink"></span>
                </span>
                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <span class="text-sm font-extrabold">{{ $label }}</span>
                    <span class="truncate text-[13px] text-ink2" x-text="{{ $o === 'full' ? "full || 'Your full name'" : "d || 'Add a display name first'" }}"></span>
                </span>
            </button>
        @endforeach
    </div>
    <div role="status" aria-live="polite" class="flex items-center gap-3 rounded-2xl border-[1.5px] border-dashed border-line2 bg-surface px-3.5 py-3">
        @if ($url)
            <x-avatar :src="$url" :frame="$user->frameClass()" :name="$user->name" :color="$user->color()" class="size-10" aria-hidden="true" />
        @else
            <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-full text-[13px] font-extrabold text-on-lime {{ $user->frameClass() }}" style="background: {{ $user->color() }}" x-text="letters"></span>
        @endif
        <span class="flex min-w-0 flex-1 flex-col gap-0.5">
            <span class="font-mono text-[11px] tracking-widest text-ink3 uppercase">Others see you as</span>
            <span class="truncate text-base font-extrabold" x-text="shown"></span>
            <span class="text-xs leading-[1.4] text-ink3" x-text="useD ? 'Your full name stays on your account. Organizers still see it next to your email.' : 'Add a display name to show a nickname instead.'"></span>
        </span>
    </div>
</div>
