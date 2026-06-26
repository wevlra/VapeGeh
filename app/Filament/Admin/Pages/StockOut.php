<?php

namespace App\Filament\Admin\Pages;

use App\Models\Buyer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
use Closure;
use DomainException;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class StockOut extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Out';

    protected static ?string $slug = 'stock-out';

    protected static ?string $title = 'Stock Out';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Stock Out';

    protected string $view = 'filament.admin.pages.stock-out';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::orderBy('name')->get()
                        ->mapWithKeys(fn (Product $p): array => [$p->id => "{$p->sku} — {$p->name}"])
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->refreshStockOptions();
                        $this->data['price_id'] = null;
                    })
                    ->columnSpanFull(),
                Select::make('price_id')
                    ->label('Price (Selling Price)')
                    ->options(fn (callable $get) => $this->getPriceOptions($get('product_id')))
                    ->helperText(fn (callable $get) => $this->getPriceHelper($get('price_id')))
                    ->searchable()
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Select::make('location_id')
                    ->label('From Location')
                    ->options(fn (callable $get) => $this->getLocationOptionsWithStock($get('product_id')))
                    ->getOptionLabelUsing(fn ($value) => $this->getLocationLabel($value))
                    ->searchable()
                    ->required()
                    ->live()
                    ->helperText(fn (callable $get) => $this->getStockHelper($get('product_id'), $get('location_id'))),
                TextInput::make('qty')
                    ->label('Quantity')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->rules([
                        fn (callable $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $stock = $this->getStockRecord($get('product_id'), $get('location_id'));
                            if ($stock && $value > $stock->qty) {
                                $fail("Quantity exceeds available stock ({$stock->qty} available).");
                            }
                            if (! $stock || $stock->qty < 1) {
                                $fail('No stock available at the selected location.');
                            }
                        },
                    ]),
                Select::make('buyer_id')
                    ->label('Buyer (optional)')
                    ->options(fn () => Buyer::pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Buyer::create($data)->getKey();
                    })
                    ->columnSpanFull(),
                Repeater::make('additional_costs')
                    ->label('Additional Costs (optional)')
                    ->schema([
                        TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->placeholder('e.g. Shipping, Packaging')
                            ->columnSpan(2),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->addActionLabel('Add Cost')
                    ->defaultItems(0)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    protected function refreshStockOptions(): void
    {
        $this->data['location_id'] = null;
    }

    protected function getPriceOptions(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        return Product::find($productId)
            ?->prices()
            ->get()
            ->mapWithKeys(fn (ProductPrice $p): array => [
                $p->id => "{$p->label}: Rp ".number_format((float) $p->price, 0, ',', '.'),
            ])
            ->toArray() ?? [];
    }

    protected function getPriceHelper(?int $priceId): string
    {
        if (! $priceId) {
            return '';
        }

        $price = ProductPrice::find($priceId);

        if (! $price) {
            return '';
        }

        return 'Unit price: Rp '.number_format((float) $price->price, 0, ',', '.').' × qty = subtotal';
    }

    protected function getLocationOptionsWithStock(?int $productId): array
    {
        if (! $productId) {
            return Location::pluck('name', 'id')->toArray();
        }

        return Stock::where('product_id', $productId)
            ->where('qty', '>', 0)
            ->with('location')
            ->get()
            ->mapWithKeys(fn (Stock $stock) => [
                $stock->location_id => "{$stock->location->name} ({$stock->qty} available)",
            ])
            ->toArray();
    }

    protected function getLocationLabel($value): string
    {
        $location = Location::find($value);
        if (! $location) {
            return '';
        }
        $stock = Stock::where('location_id', $value)->first();

        return $stock ? "{$location->name} ({$stock->qty} available)" : $location->name;
    }

    protected function getStockHelper(?int $productId, ?int $locationId): string
    {
        $stock = $this->getStockRecord($productId, $locationId);

        if (! $productId) {
            return 'Select a product first.';
        }

        if (! $locationId) {
            return '';
        }

        if (! $stock) {
            return 'No stock at this location.';
        }

        return "{$stock->qty} available.";
    }

    protected function getStockRecord(?int $productId, ?int $locationId): ?Stock
    {
        if (! $productId || ! $locationId) {
            return null;
        }

        return Stock::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $productId = (int) $data['product_id'];
        $locationId = (int) $data['location_id'];
        $qty = (int) $data['qty'];
        $priceId = (int) $data['price_id'];

        try {
            DB::transaction(function () use ($data, $productId, $locationId, $qty, $priceId) {
                $stock = Stock::where('product_id', $productId)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->qty < $qty) {
                    throw new DomainException('Insufficient stock.');
                }

                $stock->decrement('qty', $qty);

                $price = ProductPrice::find($priceId);
                $unitPrice = $price ? (float) $price->price : 0;
                $subtotal = $unitPrice * $qty;

                $additionalCosts = $data['additional_costs'] ?? [];
                $totalCost = collect($additionalCosts)->sum(fn ($cost) => (float) ($cost['amount'] ?? 0));

                StockMovement::create([
                    'product_id' => $productId,
                    'location_id' => $locationId,
                    'type' => 'out',
                    'quantity' => -$qty,
                    'unit_price' => $unitPrice,
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'additional_costs' => $additionalCosts ?: null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            });
        } catch (DomainException $e) {
            Notification::make()
                ->title('Stock out failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->form->fill();

        $unitPrice = ProductPrice::find($priceId)?->price ?? 0;
        $subtotal = $unitPrice * $qty;
        $additionalCosts = $data['additional_costs'] ?? [];
        $totalCost = collect($additionalCosts)->sum(fn ($cost) => (float) ($cost['amount'] ?? 0));

        $message = 'Stock removed. Subtotal: Rp '.number_format($subtotal, 0, ',', '.').' ('.$qty.' × Rp '.number_format($unitPrice, 0, ',', '.').')';
        if ($totalCost > 0) {
            $message .= ' + Additional: Rp '.number_format($totalCost, 0, ',', '.');
        }

        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
