<x-filament-panels::page>
    <div>
        @foreach (['all' => 'All Time', 'today' => 'Today', 'week' => 'Last 7 Days', 'month' => 'Last 30 Days'] as $value => $label)
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
