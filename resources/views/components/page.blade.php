{{-- Standard screen body inside the app shell. --}}
<div {{ $attributes->merge(['class' => 'flex animate-screen flex-col gap-5 px-4 pt-5 pb-7 md:gap-6 md:px-7 md:pt-7 md:pb-10 lg:gap-7 lg:px-12 lg:pt-10 lg:pb-14']) }}>
    {{ $slot }}
</div>
