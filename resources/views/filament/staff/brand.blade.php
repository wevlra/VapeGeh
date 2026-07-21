@if (request()->is('staff/login'))
    <style>
        .fi-logo { height: auto !important; }
    </style>
    <div class="flex flex-col items-center justify-center w-full pb-4">
        <img src="{{ asset('assets/images/logo-light-tr.png') }}" class="h-16 hidden dark:block mb-2" alt="Logo">
        <img src="{{ asset('assets/images/logo-dark-tr.png') }}" class="h-16 dark:hidden mb-2" alt="Logo">
        <span class="font-bold text-xl">VAPEGEH STAF</span>
    </div>
@else
    <div class="flex items-center gap-2">
        <img src="{{ asset('assets/images/logo-light-tr.png') }}" class="h-8 hidden dark:block" alt="Logo">
        <img src="{{ asset('assets/images/logo-dark-tr.png') }}" class="h-8 dark:hidden" alt="Logo">
        <span class="font-bold">VAPEGEH STAF</span>
    </div>
@endif
