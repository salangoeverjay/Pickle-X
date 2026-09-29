@php($stepIndex = ['email' => 0, 'code' => 1, 'new' => 2, 'done' => 3][$step])

<x-auth-shell>
    <x-slot:aside class="w-[46%] bg-court text-white">
        <x-auth-court title="Back on court in a minute." text="Reset your password with a code from your email. Your queue spot, stats and open plays stay right where you left them." />
    </x-slot:aside>

    <x-auth-court :strip="true" />

    <div class="flex flex-1 items-center justify-center px-4 pt-5 pb-8 lg:px-12 lg:pt-6 lg:pb-14">
        <div class="flex w-full max-w-105 flex-col gap-4.5">
            <div role="list" aria-label="Reset steps" class="flex items-center gap-1.5">
                @foreach (['Email', 'Code', 'New password'] as $i => $label)
                    <span role="listitem" aria-label="Step {{ $i + 1 }} of 3: {{ $label }}{{ $i < $stepIndex ? ', done' : '' }}" @if ($i === $stepIndex) aria-current="step" @endif
                          @class(['h-1.5 flex-1 rounded-[3px] transition-colors duration-300', 'bg-ink' => $i <= $stepIndex, 'bg-line' => $i > $stepIndex])></span>
                @endforeach
                <span class="ml-1.5 shrink-0 font-mono text-[11px] tracking-widest text-ink3 uppercase">{{ $step === 'done' ? 'All set' : 'Step '.($stepIndex + 1).' of 3' }}</span>
            </div>

            @if ($step === 'email')
                <form wire:key="step-email" wire:submit="send" novalidate class="flex animate-fade flex-col gap-4.5">
                    <div class="flex flex-col gap-2">
                        <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Reset your password</h1>
                        <p class="m-0 text-[15px] leading-normal text-ink2">Enter the email you use for PickleX and we'll send you a 6-digit code.</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="rp-email" class="text-[13px] font-bold text-ink2">Email</label>
                        <input id="rp-email" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com" wire:model="email" @if ($error) aria-invalid="true" @endif class="input">
                    </div>
                    @if ($error)
                        <x-alert>{{ $error }}</x-alert>
                    @endif
                    <button type="submit" wire:loading.attr="disabled" class="h-13.5 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:opacity-60">Send code</button>
                    <a href="{{ route('login') }}" wire:navigate class="inline-flex h-10 items-center gap-1.5 self-center rounded-full px-3 text-sm font-bold text-ink2 transition hover:text-ink">
                        <x-icon name="chevron-left" :size="16" :stroke="2.2" />Back to log in
                    </a>
                </form>
            @elseif ($step === 'code')
                <form wire:key="step-code" wire:submit="verify" novalidate class="flex animate-fade flex-col gap-4.5">
                    <div class="flex flex-col gap-2">
                        <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Check your email</h1>
                        <p class="m-0 text-[15px] leading-normal text-ink2">If there's a PickleX account for <strong class="text-ink">{{ $this::mask($email) }}</strong>, a 6-digit code is on its way. It expires in 10 minutes.</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="rp-code" class="text-[13px] font-bold text-ink2">6-digit code</label>
                        <input id="rp-code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" wire:model="code"
                               x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 6)" @if ($error) aria-invalid="true" @endif
                               class="h-16 w-full rounded-2xl border-[1.5px] border-line bg-surface px-4 text-center font-mono text-[28px] font-semibold tracking-[.5em] text-ink outline-none focus:border-ink">
                    </div>
                    @if ($error)
                        <x-alert>{{ $error }}</x-alert>
                    @endif
                    <button type="submit" wire:loading.attr="disabled" class="h-13.5 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:opacity-60">Verify code</button>
                    <div class="flex flex-wrap items-center justify-center gap-x-3.5 gap-y-1 text-sm text-ink2">
                        <span>Didn't get it?</span>
                        <button type="button" wire:click="resend" wire:key="resend-{{ $sentAt }}" x-data="{ left: {{ $wait }} }" x-init="let t = setInterval(() => left > 0 ? left-- : clearInterval(t), 1000)"
                                x-bind:disabled="left > 0" x-text="left > 0 ? 'Resend in 0:' + String(left).padStart(2, '0') : 'Resend code'"
                                class="h-9 cursor-pointer px-1 text-sm font-bold text-ink underline decoration-lime decoration-3 underline-offset-4 disabled:cursor-not-allowed disabled:opacity-55">Resend code</button>
                        <button type="button" wire:click="changeEmail" class="h-9 cursor-pointer px-1 text-sm font-bold text-ink2 hover:text-ink">Use a different email</button>
                    </div>
                </form>
            @elseif ($step === 'new')
                <form wire:key="step-new" wire:submit="save" novalidate x-data="newPassword(@js($email))" class="flex animate-fade flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Set a new password</h1>
                        <p class="m-0 text-[15px] leading-normal text-ink2">Pick something you don't use anywhere else.</p>
                    </div>
                    <input type="text" autocomplete="username" value="{{ $email }}" hidden readonly>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label for="rp-pass" class="text-[13px] font-bold text-ink2">New password</label>
                            <button type="button" x-on:click="show = ! show" x-text="show ? 'Hide' : 'Show'" class="h-8 cursor-pointer px-1 text-[13px] font-bold text-ink2">Show</button>
                        </div>
                        <input id="rp-pass" type="password" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password" placeholder="At least 8 characters" x-model="$wire.password" aria-describedby="rp-rules" class="input">
                        <div aria-hidden="true" class="mt-0.5 grid grid-cols-4 gap-1">
                            @foreach ([1, 2, 3, 4] as $n)
                                <span class="h-1.25 rounded-[3px] transition-colors" x-bind:class="bar({{ $n }})"></span>
                            @endforeach
                        </div>
                        <span role="status" aria-live="polite" class="text-xs font-bold" x-bind:class="labelClass" x-text="label"></span>
                    </div>
                    <ul id="rp-rules" class="m-0 flex list-none flex-col gap-1.5 p-0">
                        <template x-for="[label, ok] in result.rules" x-bind:key="label">
                            <li class="flex items-center gap-2 text-[13px] font-semibold" x-bind:class="ok ? 'text-ink' : 'text-ink2'">
                                <span class="flex size-4.5 shrink-0 items-center justify-center rounded-full border-[1.5px] text-on-lime" x-bind:class="ok ? 'border-lime bg-lime' : 'border-line2'">
                                    <x-icon name="check" :size="11" :stroke="3" x-show="ok" />
                                </span>
                                <span x-text="label"></span>
                            </li>
                        </template>
                    </ul>
                    <div class="flex flex-col gap-2">
                        <label for="rp-confirm" class="text-[13px] font-bold text-ink2">Confirm new password</label>
                        <input id="rp-confirm" type="password" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password" placeholder="Type it again" x-model="$wire.confirm" class="input">
                        <span x-show="match !== null" x-cloak class="text-xs font-bold" x-bind:class="match ? 'text-lime-text' : 'text-ink2'" x-text="match ? 'Passwords match' : 'Doesn\'t match yet'"></span>
                    </div>
                    @if ($error)
                        <x-alert>{{ $error }}</x-alert>
                    @endif
                    <button type="submit" wire:loading.attr="disabled" class="h-13.5 cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 disabled:opacity-60">Update password</button>
                </form>
            @else
                <div wire:key="step-done" class="flex animate-fade flex-col items-start gap-4.5">
                    <span aria-hidden="true" class="flex size-16 animate-snap items-center justify-center rounded-[20px] border-2 border-cta-border bg-lime text-on-lime"><x-icon name="check" :size="30" :stroke="2.6" /></span>
                    <div class="flex flex-col gap-2">
                        <h1 class="m-0 font-display text-4xl leading-none font-extrabold tracking-[-0.04em] lg:text-[46px]">Password updated</h1>
                        <p class="m-0 text-[15px] leading-normal text-ink2">You can log in with your new password now. For your security, we signed you out on your other devices.</p>
                    </div>
                    <button type="button" wire:click="finish" class="h-13.5 w-full cursor-pointer rounded-full border-2 border-cta-border bg-lime text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Back to log in</button>
                </div>
            @endif
        </div>
    </div>
</x-auth-shell>
