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
                    'pending' => 'text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-400/10 ring-warning-600/20',
                    'completed' => 'text-success-600 dark:text-success-400 bg-success-50 dark:bg-success-400/10 ring-success-600/20',
                    'cancelled' => 'text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-400/10 ring-danger-600/20',
                    default => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-400/10 ring-gray-600/20',
                };
            @endphp
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                {{ $statusLabel }}
            </span>
            <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                {{ $getRecord()->created_at ? $getRecord()->created_at->format('d M Y') : '-' }}
            </span>
        </div>
    </div>
</div>
