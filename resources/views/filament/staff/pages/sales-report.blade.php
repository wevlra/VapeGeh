<x-filament-panels::page>
    <div class="flex flex-wrap gap-1.5">
        @foreach (['all' => 'Semua Waktu', 'today' => 'Hari Ini', 'week' => '7 Hari Terakhir', 'month' => '30 Hari Terakhir'] as $value => $label)
            <x-filament::button
                :color="$this->period === $value ? 'primary' : 'gray'"
                size="sm"
                wire:click="$set('period', '{{ $value }}')"
            >
                {{ $label }}
            </x-filament::button>
        @endforeach
    </div>

    {{ $this->content }}
</x-filament-panels::page>
