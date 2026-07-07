<div class="w-full">
    {{-- Desktop View --}}
    <div class="hidden md:block">
        {{ $getRecord()->transfer_number }}
    </div>

    {{-- Mobile View --}}
    <div class="flex items-center justify-between w-full md:hidden py-1.5 gap-x-4">
        <div class="flex flex-col min-w-0">
            <span class="text-sm font-semibold text-gray-950 dark:text-white truncate">
                {{ $getRecord()->transfer_number }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                {{ $getRecord()->fromLocation->name ?? '-' }} → {{ $getRecord()->toLocation->name ?? '-' }}
            </span>
        </div>
        <div class="flex flex-col items-end shrink-0">
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
            <x-filament::badge :color="$statusColor" size="sm">
                {{ $statusLabel }}
            </x-filament::badge>
            <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                {{ $getRecord()->created_at ? $getRecord()->created_at->format('d M Y') : '-' }}
            </span>
        </div>
    </div>
</div>
