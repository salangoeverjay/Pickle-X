<div role="alert" {{ $attributes->merge(['class' => 'flex animate-fade items-start gap-2.5 rounded-[14px] border border-hot-line bg-hot-soft px-3.5 py-3 text-sm leading-[1.45] font-semibold text-hot']) }}>
    <svg class="mt-px shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v5M12 16.5v.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/></svg>
    <span>{{ $slot }}</span>
</div>
