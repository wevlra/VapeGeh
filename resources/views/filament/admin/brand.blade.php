@if (request()->is('admin/login'))
    <style>
        .fi-logo { height: auto !important; }
    </style>
    <div class="flex flex-col items-center justify-center w-full pb-4">
        <img src="{{ asset('assets/images/logo-stacked-light-tr.png') }}" class="h-50 hidden dark:block -mb-10" alt="Logo">
        <img src="{{ asset('assets/images/logo-stacked-dark-tr.png') }}" class="h-50 dark:hidden -mb-10" alt="Logo">
        <span class="font-bold text-xl">ADMIN</span>
    </div>
@else
    <div class="flex items-center gap-2">
        <img src="{{ asset('assets/images/logo-light-tr.png') }}" class="h-8 hidden dark:block" alt="Logo">
        <img src="{{ asset('assets/images/logo-dark-tr.png') }}" class="h-8 dark:hidden" alt="Logo">
        <span class="font-bold">ADMIN PANEL</span>
    </div>
@endif
