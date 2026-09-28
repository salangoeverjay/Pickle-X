{{-- Profile picture row + picker dialog (logic: resources/js/avatar-picker.js). --}}
@props(['user', 'picker'])

@php
    $btn = 'transition hover:brightness-96 active:scale-97';
    $upl = 'relative cursor-pointer overflow-hidden has-focus-visible:outline-2 has-focus-visible:outline-offset-3 has-focus-visible:outline-focus';
    $fileAccept = 'image/png,image/jpeg,image/webp,image/gif';
@endphp

<div x-data="avatarPicker(@js($picker))" class="flex flex-wrap items-center gap-5">
    <button type="button" x-on:click="show()" aria-label="Change profile picture" class="relative shrink-0 cursor-pointer rounded-full shadow-[0_0_0_4px_var(--color-surface),0_0_0_5.5px_var(--color-line)] {{ $btn }}">
        <x-avatar :src="$user->avatarUrl()" :name="$user->name" :color="$user->color()" class="size-23 text-[30px]" />
        <span aria-hidden="true" class="absolute -right-1 -bottom-1 flex size-8.5 items-center justify-center rounded-full border-3 border-surface bg-ink text-bg"><x-icon name="camera" :size="16" /></span>
    </button>

    <div class="flex min-w-55 flex-1 flex-col gap-3">
        <div class="flex flex-col gap-0.5">
            <span class="text-[15px] font-bold">Profile picture</span>
            <span class="text-[13px] leading-[1.4] text-ink3">{{ $user->avatarLabel() ? $user->avatarLabel().' · shown in queues, on courts and in rankings.' : 'Showing your initials. Upload a photo or pick a PickleX avatar.' }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <label class="{{ $upl }} inline-flex h-11 items-center gap-2 rounded-full bg-ink px-4 text-sm font-bold text-bg {{ $btn }}">
                <x-icon name="upload" :size="18" />Upload photo
                <input type="file" accept="{{ $fileAccept }}" x-on:change="pick($event)" aria-label="Upload a profile photo" class="absolute inset-0 cursor-pointer opacity-0">
            </label>
            <button type="button" x-on:click="show('presets')" class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-line2 px-4 text-sm font-bold text-ink {{ $btn }}">
                <x-icon name="avatars" :size="18" />Choose an avatar
            </button>
            @if ($user->avatar)
                <button type="button" x-on:click="remove()" class="h-11 cursor-pointer rounded-full px-3.5 text-sm font-bold text-hot {{ $btn }}">Remove</button>
            @endif
        </div>
    </div>

    @teleport('body')
        <div x-show="open" x-cloak x-on:keydown.escape.window="close()" class="fixed inset-0 z-66 flex items-end justify-center lg:items-center lg:p-6">
            <button type="button" x-on:click="close()" aria-label="Close profile picture" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

            <div role="dialog" aria-modal="true" aria-labelledby="av-title" x-trap.noscroll="open"
                 class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-160 lg:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Account settings</span>
                        <h2 id="av-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Profile picture</h2>
                    </div>
                    <button type="button" x-on:click="close()" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink {{ $btn }}"><x-icon name="x" :size="18" /></button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-6 pt-4.5 pb-5.5">
                    {{-- How it will look --}}
                    <div class="flex items-center gap-4 rounded-[20px] border border-line bg-surface p-3.5">
                        <template x-if="preview.photo">
                            <span aria-hidden="true" class="relative size-21 shrink-0 overflow-hidden rounded-full bg-white"><img :src="crop.src" alt="" draggable="false" :style="imgStyle(84)" class="absolute max-w-none"></span>
                        </template>
                        <template x-if="!preview.photo">
                            <span aria-hidden="true" class="flex size-21 shrink-0 items-center justify-center rounded-full bg-cover bg-center text-[28px] font-extrabold text-on-lime transition-colors"
                                  :style="`background-color:${preview.color};${preview.url ? `background-image:url('${preview.url}')` : ''}`" x-text="preview.url ? '' : @js($user->initials())"></span>
                        </template>
                        <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                            <div class="flex min-w-0 flex-col gap-0.5">
                                <span class="truncate text-base font-bold">{{ $user->name }}</span>
                                <span role="status" aria-live="polite" class="truncate text-[13px] text-ink3" x-text="preview.label"></span>
                            </div>
                            <div aria-hidden="true" class="inline-flex h-10 items-center gap-2 self-start rounded-full border border-line bg-bg pr-3 pl-1">
                                <template x-if="preview.photo">
                                    <span class="relative size-8 shrink-0 overflow-hidden rounded-full bg-white"><img :src="crop.src" alt="" draggable="false" :style="imgStyle(32)" class="absolute max-w-none"></span>
                                </template>
                                <template x-if="!preview.photo">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-cover bg-center text-[11px] font-extrabold text-on-lime"
                                          :style="`background-color:${preview.color};${preview.url ? `background-image:url('${preview.url}')` : ''}`" x-text="preview.url ? '' : @js($user->initials())"></span>
                                </template>
                                <span class="text-[13px] font-bold">{{ $user->firstName() }}</span>
                                <span class="font-mono text-[10px] tracking-widest text-ink3">IN QUEUE</span>
                            </div>
                        </div>
                    </div>

                    <div role="tablist" aria-label="Picture type" class="flex shrink-0 rounded-2xl border-[1.5px] border-line bg-surface2 p-1">
                        @foreach (['presets' => 'Avatars', 'upload' => 'Upload photo', 'initials' => 'Initials'] as $key => $label)
                            <button type="button" role="tab" id="av-tab-{{ $key }}" aria-controls="av-panel" x-bind:aria-selected="tab === '{{ $key }}'" x-on:click="tab = '{{ $key }}'; error = ''"
                                    :class="tab === '{{ $key }}' ? 'bg-surface text-ink shadow-[0_1px_2px_rgba(18,23,15,.12),0_4px_12px_-6px_rgba(18,23,15,.25)]' : 'text-ink2'"
                                    class="min-h-10.5 flex-1 cursor-pointer rounded-xl px-2 text-sm font-bold transition-[background-color,color,box-shadow]">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div id="av-panel" role="tabpanel" :aria-labelledby="'av-tab-' + tab" class="flex flex-col gap-3.5">
                        {{-- PickleX avatars --}}
                        <div x-show="tab === 'presets'" class="flex animate-fade flex-col gap-3.5">
                            <div role="radiogroup" aria-label="PickleX avatars" class="grid grid-cols-4 gap-2.5 lg:grid-cols-6">
                                @foreach ($picker['presets'] as $id => $preset)
                                    <button type="button" role="radio" x-bind:aria-checked="sel === '{{ $id }}'" aria-label="{{ $preset['label'] }}" x-on:click="sel = '{{ $id }}'; error = ''"
                                            :class="sel === '{{ $id }}' ? 'border-ink bg-surface' : 'border-line'"
                                            class="relative flex cursor-pointer flex-col items-center gap-1.5 rounded-[18px] border-2 px-1 py-2 text-ink {{ $btn }}">
                                        <img src="{{ $preset['url'] }}" alt="" class="aspect-square w-full max-w-17 rounded-full shadow-[0_0_0_1px_var(--color-line)]">
                                        <span aria-hidden="true" class="max-w-full truncate text-center text-xs leading-[1.2] font-bold">{{ $preset['label'] }}</span>
                                        <span x-show="sel === '{{ $id }}'" aria-hidden="true" class="absolute top-1 right-1 flex size-6 animate-snap items-center justify-center rounded-full border-2 border-bg bg-lime text-on-lime"><x-icon name="check" :size="13" :stroke="2.6" /></span>
                                    </button>
                                @endforeach
                            </div>
                            <p class="m-0 text-[13px] text-ink3">Made for PickleX. Anyone in your open plays sees it next to your name.</p>
                        </div>

                        {{-- Upload + crop --}}
                        <div x-show="tab === 'upload'" class="animate-fade">
                            <label x-show="!crop" x-on:dragenter.prevent="over = true" x-on:dragover.prevent="over = true" x-on:dragleave="$el.contains($event.relatedTarget) || (over = false)" x-on:drop.prevent="dropped($event)"
                                   :class="over ? 'border-ink bg-lime-soft' : 'border-line2 bg-surface'"
                                   class="{{ $upl }} flex min-h-50 flex-col items-center justify-center gap-2 rounded-[22px] border-2 border-dashed px-5 py-6 text-center transition-colors lg:min-h-57.5">
                                <span aria-hidden="true" class="mb-1 flex size-14.5 animate-float items-center justify-center rounded-[18px] border-2 border-cta-border bg-lime text-on-lime"><x-icon name="camera" :size="26" /></span>
                                <span role="status" aria-live="polite" class="text-[17px] font-extrabold">
                                    <span x-show="busy">Opening your photo…</span>
                                    <span x-show="!busy && over">Drop it here</span>
                                    <span x-show="!busy && !over"><span class="lg:hidden">Choose a photo</span><span class="hidden lg:inline">Drag a photo here</span></span>
                                </span>
                                <span class="max-w-80 text-[13px] leading-[1.45] text-ink3"><span class="lg:hidden">Take a new one or pick from your library.</span><span class="hidden lg:inline">or click to browse.</span> JPG, PNG, WebP or GIF up to 5 MB.</span>
                                @if (str_starts_with((string) $user->avatar, 'photo:'))
                                    <span class="text-xs font-bold text-ink2">This replaces your current photo.</span>
                                @endif
                                <input type="file" accept="{{ $fileAccept }}" x-on:change="pick($event)" aria-label="Choose a photo to upload" class="absolute inset-0 cursor-pointer opacity-0">
                            </label>

                            <template x-if="crop">
                                <div class="flex animate-fade flex-col items-center gap-5 lg:flex-row">
                                    <div tabindex="0" role="group" aria-label="Photo position. Drag, or use the arrow keys to move and plus or minus to zoom."
                                         x-on:pointerdown="down($event)" x-on:pointermove="move($event)" x-on:pointerup="drag = null" x-on:pointercancel="drag = null" x-on:keydown="key($event)"
                                         class="relative size-60 shrink-0 cursor-grab touch-none overflow-hidden rounded-3xl bg-[#0A0E0B] select-none focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-focus active:cursor-grabbing">
                                        <img :src="crop.src" alt="" draggable="false" :style="imgStyle(240)" class="pointer-events-none absolute max-w-none">
                                        <span aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_70.4%,rgba(10,14,11,.58)_70.9%)]"></span>
                                        <span aria-hidden="true" class="pointer-events-none absolute inset-0 rounded-full border-2 border-white/92"></span>
                                    </div>
                                    <div class="flex w-full min-w-0 flex-1 flex-col gap-3.5">
                                        <div class="flex flex-col gap-2">
                                            <label for="av-zoom" class="flex justify-between text-[13px] font-bold text-ink2"><span>Zoom</span><span class="font-mono font-semibold text-ink3" x-text="Math.round(crop.zoom * 100) + '%'"></span></label>
                                            <div class="flex items-center gap-2">
                                                <button type="button" x-on:click="set({ zoom: crop.zoom - 0.25 })" aria-label="Zoom out" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink {{ $btn }}"><x-icon name="minus" :size="18" /></button>
                                                <input id="av-zoom" type="range" min="100" max="300" step="1" :value="Math.round(crop.zoom * 100)" x-on:input="set({ zoom: $event.target.value / 100 })" class="h-7 min-w-0 flex-1 cursor-pointer accent-lime-text">
                                                <button type="button" x-on:click="set({ zoom: crop.zoom + 0.25 })" aria-label="Zoom in" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-surface text-ink {{ $btn }}"><x-icon name="plus" :size="18" /></button>
                                            </div>
                                        </div>
                                        <p class="m-0 flex items-start gap-2 text-[13px] leading-[1.45] text-ink2"><x-icon name="move" :size="18" />Drag the photo to line your face up inside the circle.</p>
                                        <div class="flex items-center gap-2.5 rounded-[14px] border border-line bg-surface py-2.5 pr-2.5 pl-3.5">
                                            <span class="min-w-0 flex-1 truncate text-[13px] font-semibold" x-text="crop.name"></span>
                                            <label class="{{ $upl }} inline-flex h-9 shrink-0 items-center rounded-full border-[1.5px] border-line2 px-3.5 text-[13px] font-bold text-ink {{ $btn }}">
                                                Choose another
                                                <input type="file" accept="{{ $fileAccept }}" x-on:change="pick($event)" aria-label="Choose a different photo" class="absolute inset-0 cursor-pointer opacity-0">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Initials --}}
                        <div x-show="tab === 'initials'" class="flex animate-fade flex-col gap-3">
                            <span id="av-color" class="text-[13px] font-bold text-ink2">Circle color</span>
                            <div role="radiogroup" aria-labelledby="av-color" class="flex flex-wrap gap-2.5">
                                @foreach ($picker['colors'] as $hex => $label)
                                    <button type="button" role="radio" x-bind:aria-checked="color === '{{ $hex }}'" aria-label="{{ $label }}" x-on:click="color = '{{ $hex }}'; error = ''"
                                            :class="color === '{{ $hex }}' ? 'border-ink' : 'border-transparent'"
                                            class="size-13 cursor-pointer rounded-full border-3 p-0 text-[13px] font-extrabold text-on-lime shadow-[inset_0_0_0_3px_var(--color-bg)] {{ $btn }}" style="background: {{ $hex }}">{{ $user->initials() }}</button>
                                @endforeach
                            </div>
                            <p class="m-0 text-[13px] text-ink3">Your initials on a colored circle, the PickleX default.</p>
                        </div>
                    </div>

                    <div x-show="error" x-cloak role="alert" class="flex animate-fade items-start gap-2.5 rounded-[14px] border border-hot-line bg-hot-soft px-3.5 py-3 text-sm leading-[1.45] font-semibold text-hot">
                        <x-icon name="alert" :size="18" class="mt-px" /><span x-text="error"></span>
                    </div>
                </div>

                <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                    <button type="button" x-on:click="close()" class="h-13 cursor-pointer rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink {{ $btn }}">Cancel</button>
                    <button type="button" x-on:click="save()" x-bind:disabled="!dirty || saving" x-text="saving ? 'Saving…' : saveLabel"
                            class="h-13 flex-1 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-[15px] font-extrabold text-on-lime shadow-cta disabled:cursor-not-allowed disabled:opacity-45 {{ $btn }}">Use this avatar</button>
                </div>
            </div>
        </div>
    @endteleport
</div>
