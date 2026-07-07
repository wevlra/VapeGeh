<div class="w-full">
    {{-- Desktop View --}}
    <div class="hidden md:block">
        {{ $getRecord()->transfer_number }}
    </div>

    {{-- Mobile View --}}
    <div class="flex flex-col w-full md:hidden py-1 gap-y-1">
        <div class="flex items-start justify-between w-full gap-x-4">
            <span class="text-sm font-semibold text-gray-950 dark:text-white truncate">
                {{ $getRecord()->transfer_number }}
            </span>
            <span class="text-[10px] text-gray-400 dark:text-gray-500 whitespace-nowrap pt-0.5 shrink-0">
                {{ $getRecord()->created_at ? $getRecord()->created_at->format('d M Y') : '-' }}
            </span>
        </div>
        <div class="flex items-center justify-between w-full gap-x-4">
            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ $getRecord()->fromLocation->name ?? '-' }} → {{ $getRecord()->toLocation->name ?? '-' }}
            </span>
            @php
                $status = $getRecord()->status;
                $statusLabel = match($status) {
                    'pending' => 'Tertunda',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($status),
                };
                $statusColor = match($status) {
                    'pending' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                };
            @endphp
            <div class="shrink-0">
                <x-filament::badge :color="$statusColor" size="sm">
                    {{ $statusLabel }}
                </x-filament::badge>
            </div>
        </div>
    </div>
</div>
