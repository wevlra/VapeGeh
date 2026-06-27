<?php

use Livewire\Component;

new class extends Component {};
?>

<div class="flex min-h-screen items-center justify-center bg-zinc-950 px-6">
    <div class="w-full max-w-md">
        <img
            src="/assets/images/logo-wordmark-dark-tr.png"
            alt="VapeGeh"
        >

        <div class="rounded-3xl border border-zinc-800 bg-zinc-900 p-2">
            <a
                href="/admin/login"
                class="group flex items-center gap-4 rounded-2xl p-4 transition hover:border-amber-500/30 hover:bg-white/5"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 ring-1 ring-amber-500/20">
                    <x-filament::icon
                        icon="heroicon-o-cog-6-tooth"
                        class="h-7 w-7 text-amber-500"
                    />
                </div>

                <div class="flex-1">
                    <h2 class="font-semibold text-white">
                        Administrator
                    </h2>

                    <p class="mt-1 text-sm text-gray-400">
                        Manage inventory, users, reports, and system settings.
                    </p>
                </div>

                <x-filament::icon
                    icon="heroicon-o-arrow-right"
                    class="h-5 w-5 text-gray-500 transition group-hover:translate-x-1 group-hover:text-amber-500"
                />
            </a>

            <div class="mx-4 border-t border-zinc-800"></div>

            <a
                href="/staff/login"
                class="group flex items-center gap-4 rounded-2xl p-4 transition hover:border-amber-500/30 hover:bg-white/5"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 ring-1 ring-amber-500/20">
                    <x-filament::icon
                        icon="heroicon-o-user-group"
                        class="h-7 w-7 text-amber-500"
                    />
                </div>

                <div class="flex-1">
                    <h2 class="font-semibold text-white">
                        Staff
                    </h2>

                    <p class="mt-1 text-sm text-gray-400">
                        Access POS, sales, stock movement, and daily operations.
                    </p>
                </div>

                <x-filament::icon
                    icon="heroicon-o-arrow-right"
                    class="h-5 w-5 text-gray-500 transition group-hover:translate-x-1 group-hover:text-amber-500"
                />
            </a>
        </div>

        <p class="mt-6 text-center text-xs text-gray-500">
            © {{ date('Y') }} VapeGeh
        </p>
    </div>
</div>
