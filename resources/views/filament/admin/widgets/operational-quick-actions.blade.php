<x-filament-widgets::widget>
    <x-filament::section heading="Opeational">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Resources\Incomes\IncomeResource::getUrl('create') }}"
                color="success"
                icon="heroicon-o-arrow-down-tray"
                class="w-full justify-center"
            >
                Income
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Resources\Expenses\ExpenseResource::getUrl('create') }}"
                color="danger"
                icon="heroicon-o-arrow-up-tray"
                class="w-full justify-center"
            >
                Expense
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Resources\Sales\SaleResource::getUrl('index') }}"
                color="info"
                icon="heroicon-o-shopping-cart"
                class="w-full justify-center"
            >
                View Sales
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
