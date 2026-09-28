<x-auth-shell>
    <x-slot:aside class="w-[42%] bg-ink text-bg">
        <div aria-hidden="true" class="cta-ball absolute top-30 -right-35 size-105 animate-spin-slow rounded-full"></div>

        <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="relative self-start">
            <x-logo class="text-[28px]" />
        </a>

        <div class="relative flex max-w-105 flex-col gap-6.5">
            <h2 class="m-0 font-display text-[52px] leading-[.95] font-extrabold tracking-[-0.045em] text-balance">One app for the whole open play.</h2>
            <div class="flex flex-col gap-4">
                <div class="flex gap-3.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-lime font-mono text-[13px] font-semibold text-on-lime">M</span>
                    <div>
                        <div class="text-base font-bold">Members</div>
                        <div class="text-sm leading-normal opacity-82">Join open plays, see your place in the queue, and track stats and rankings.</div>
                    </div>
                </div>
                <div class="flex gap-3.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full border-[1.5px] border-current font-mono text-[13px] font-semibold">O</span>
                    <div>
                        <div class="text-base font-bold">Organizers</div>
                        <div class="text-sm leading-normal opacity-82">Host sessions and run the court board. A PickleX admin approves each organizer first.</div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:aside>

    <div class="flex flex-1 justify-center px-4 pt-1 pb-8 lg:px-12 lg:pt-2 lg:pb-12">
        @if (! $sent)
            <form wire:submit="signup" novalidate class="flex w-full max-w-115 flex-col gap-4.5">
                <div class="flex flex-col gap-2">
                    <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Create your account</h1>
                    <p class="m-0 text-[15px] text-ink2">Members can play right away. Organizer accounts are approved by a PickleX admin.</p>
                </div>

                <div role="radiogroup" aria-label="Account type" class="grid grid-cols-2 gap-2.5">
                    @foreach ([
                        ['member', 'Member', 'Join open plays, track stats and rankings', 'users', 'bg-lime text-on-lime'],
                        ['organizer', 'Organizer', 'Host open plays · needs admin approval', 'court', 'bg-court text-white'],
                    ] as [$value, $label, $hint, $icon, $iconClass])
                        <label class="group relative flex cursor-pointer flex-col items-start gap-2 rounded-[18px] border-2 border-line p-4 text-ink transition active:scale-97 has-checked:border-ink has-checked:bg-surface has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus">
                            <input type="radio" name="role" value="{{ $value }}" wire:model.live="role" class="sr-only">
                            <span class="flex size-9.5 items-center justify-center rounded-xl {{ $iconClass }}"><x-icon :name="$icon" :stroke="1.9" /></span>
                            <span class="text-base font-extrabold">{{ $label }}</span>
                            <span class="text-[13px] leading-[1.4] text-ink2">{{ $hint }}</span>
                            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border-2 border-line2 group-has-checked:border-ink">
                                <span class="size-2.5 rounded-full group-has-checked:bg-ink"></span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <x-field label="Full name" id="su-name" autocomplete="name" placeholder="Juan Dela Cruz" wire:model="name" />

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <x-field label="Email" id="su-email" type="email" autocomplete="email" placeholder="you@example.com" wire:model="email" />
                    <x-field label="Password" id="su-password" type="password" autocomplete="new-password" placeholder="8+ characters" wire:model="password" />
                </div>

                @if ($role === 'organizer')
                    <div class="flex animate-fade flex-col gap-4.5 border-t border-dashed border-line2 pt-1">
                        <span class="mt-2.5 font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">About your open plays</span>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            <x-field label="Club or group name" id="su-club" placeholder="e.g. Panabo Dink Society" wire:model="club" />
                            <x-field label="City" id="su-city" placeholder="e.g. Panabo City" wire:model="city" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <span id="su-courts" class="text-[13px] font-bold text-ink2">Courts you run</span>
                            <div x-data role="group" aria-labelledby="su-courts" class="flex h-13 max-w-55 items-center gap-2 rounded-[14px] border-[1.5px] border-line bg-surface px-1.5">
                                <button type="button" x-on:click="$wire.courts = Math.max(1, $wire.courts - 1)" aria-label="Fewer courts" class="flex size-10 cursor-pointer items-center justify-center rounded-[10px] bg-surface2 text-ink">
                                    <x-icon name="minus" :size="18" :stroke="2.2" />
                                </button>
                                <span x-text="$wire.courts" class="flex-1 text-center font-mono text-[17px] font-semibold">{{ $courts }}</span>
                                <button type="button" x-on:click="$wire.courts = Math.min(20, $wire.courts + 1)" aria-label="More courts" class="flex size-10 cursor-pointer items-center justify-center rounded-[10px] bg-surface2 text-ink">
                                    <x-icon name="plus" :size="18" :stroke="2.2" />
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="su-note" class="text-[13px] font-bold text-ink2">Tell the admin about your sessions</label>
                            <textarea id="su-note" rows="3" placeholder="Which nights you play, how many regulars, anything that helps approval." wire:model="note" class="input h-auto resize-y py-3.5 leading-normal"></textarea>
                        </div>

                        <div class="flex items-start gap-3 rounded-2xl border border-hot-line bg-hot-soft px-4 py-3.5">
                            <x-icon name="shield" :stroke="1.9" class="mt-px text-hot" />
                            <span class="text-sm leading-[1.45] text-ink">A PickleX admin reviews every organizer. You can log in to organizer tools once your request is approved.</span>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <x-alert>{{ $errors->first() }}</x-alert>
                @endif

                <button type="submit" class="h-13.5 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:cursor-not-allowed disabled:opacity-45" wire:loading.attr="disabled">
                    {{ $role === 'organizer' ? 'Send organizer request' : 'Create member account' }}
                </button>

                <p class="mt-0 mb-3 text-center text-sm text-ink2">
                    Already have an account?
                    <a href="{{ route('login') }}" wire:navigate class="font-bold text-ink underline decoration-lime decoration-3 underline-offset-4">Log in</a>
                </p>
            </form>
        @else
            <div class="flex w-full max-w-115 flex-col gap-5.5 pt-3 lg:pt-10">
                <div class="flex size-19 animate-snap items-center justify-center rounded-full border-2 border-on-lime bg-lime text-on-lime">
                    <x-icon name="check" :size="36" :stroke="2.6" />
                </div>

                <div class="flex flex-col gap-2.5">
                    <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Request sent</h1>
                    <p class="m-0 text-base leading-[1.55] text-ink2">
                        Thanks, {{ Str::before(trim($name), ' ') ?: 'there' }}. A PickleX admin will review <strong class="text-ink">{{ $club }}</strong>.
                        Log in with <strong class="text-ink">{{ $email }}</strong> once you're approved.
                    </p>
                </div>

                <ol class="m-0 flex list-none flex-col rounded-[20px] border border-line bg-surface p-4.5">
                    <li class="flex gap-3.5">
                        <div class="flex flex-col items-center">
                            <span class="flex size-6.5 items-center justify-center rounded-full bg-ink text-bg"><x-icon name="check" :size="14" :stroke="3" /></span>
                            <span class="min-h-5.5 w-0.5 flex-1 bg-ink"></span>
                        </div>
                        <div class="pb-4"><div class="font-bold">Request submitted</div><div class="text-[13px] text-ink3">Just now</div></div>
                    </li>
                    <li class="flex gap-3.5">
                        <div class="flex flex-col items-center">
                            <span class="size-6.5 animate-live rounded-full border-2 border-on-lime bg-lime"></span>
                            <span class="min-h-5.5 w-0.5 flex-1 bg-line2"></span>
                        </div>
                        <div class="pb-4"><div class="font-bold">Admin review</div><div class="text-[13px] text-ink3">In progress</div></div>
                    </li>
                    <li class="flex gap-3.5">
                        <div class="flex flex-col items-center">
                            <span class="size-6.5 rounded-full border-2 border-dashed border-line2"></span>
                        </div>
                        <div><div class="font-bold text-ink2">Organizer tools unlocked</div><div class="text-[13px] text-ink3">Create open plays and run the court board</div></div>
                    </li>
                </ol>

                <div class="flex flex-wrap gap-2.5">
                    @if (config('app.demo'))
                        <form method="POST" action="{{ route('demo-login') }}">
                            @csrf
                            <button type="submit" name="role" value="admin" class="inline-flex h-13 cursor-pointer items-center gap-2 rounded-full bg-ink px-5.5 text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">
                                <x-icon name="shield" :size="18" :stroke="1.9" />Review as admin (demo)
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex h-13 items-center rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-semibold text-ink transition hover:brightness-96 active:scale-97">Back to home</a>
                </div>
            </div>
        @endif
    </div>
</x-auth-shell>
