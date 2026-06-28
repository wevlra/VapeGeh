<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Pages\Dashboard::getUrl() }}"
                color="gray"
            >
                Cancel
            </x-filament::button>
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
