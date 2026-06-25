<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#020617]">
    {{-- Subtle ambient glow --}}
    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
        <div class="h-[600px] w-[600px] rounded-full bg-[#F59E0B] opacity-[0.03] blur-[120px]"></div>
    </div>

    <div class="relative w-full max-w-xl px-6 py-12">
        {{-- Brand --}}
        <div class="mb-12 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-[#1E293B] bg-[#0F172A]">
                <svg viewBox="0 0 24 24" fill="none" class="h-8 w-8 text-[#F59E0B]">
                    <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1 class="font-sans text-4xl font-bold tracking-tight text-[#F8FAFC]">VapeGeh</h1>
            <p class="mt-3 text-lg text-[#64748B]">Multi-Branch Inventory & Sales System</p>
        </div>

        {{-- Role cards --}}
        <div class="grid gap-5">
            {{-- Admin --}}
            <a href="/admin/login"
               wire:navigate
               class="group relative overflow-hidden rounded-2xl border border-[#1E293B] bg-[#0F172A]/80 p-[1px] transition-all duration-300 hover:border-[#F59E0B]/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F59E0B] focus-visible:ring-offset-2 focus-visible:ring-offset-[#020617]">
                <div class="relative flex items-center gap-5 rounded-[15px] bg-[#0F172A] p-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F59E0B]/10 text-[#F59E0B] transition-colors duration-300 group-hover:bg-[#F59E0B]/20">
                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                            <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="font-sans text-lg font-semibold text-[#F8FAFC]">Admin</h2>
                        <p class="mt-0.5 text-sm leading-relaxed text-[#64748B]">Manage inventory, users, stock transfers, and reports</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-[#475569] transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            {{-- Staff --}}
            <a href="/staff/login"
               wire:navigate
               class="group relative overflow-hidden rounded-2xl border border-[#1E293B] bg-[#0F172A]/80 p-[1px] transition-all duration-300 hover:border-[#F59E0B]/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F59E0B] focus-visible:ring-offset-2 focus-visible:ring-offset-[#020617]">
                <div class="relative flex items-center gap-5 rounded-[15px] bg-[#0F172A] p-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F59E0B]/10 text-[#F59E0B] transition-colors duration-300 group-hover:bg-[#F59E0B]/20">
                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                            <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M22 21v-2a4 4 0 00-3-3.87" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="font-sans text-lg font-semibold text-[#F8FAFC]">Staff</h2>
                        <p class="mt-0.5 text-sm leading-relaxed text-[#64748B]">Manage sales, view stock, and input transactions</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-[#475569] transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-center text-xs text-[#334155]">
            &copy; {{ date('Y') }} VapeGeh. All rights reserved.
        </p>
    </div>
</div>
