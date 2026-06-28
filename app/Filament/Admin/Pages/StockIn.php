<?php

namespace App\Filament\Admin\Pages;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Vendor;
use BackedEnum;
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

class StockIn extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock In';

    protected static ?string $slug = 'stock-in';

    protected static ?string $title = 'Stock In';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Stock In';

    protected string $view = 'filament.admin.pages.stock-in';

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
                    ->afterStateUpdated(fn ($state, callable $set) => $set('price', Product::find($state)?->purchase_price ?? 0))
                    ->columnSpanFull(),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->options(fn () => Vendor::pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_person')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Vendor::create($data)->getKey();
                    }),
                Select::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::pluck('name', 'id'))
                    ->searchable()
                    ->default(fn () => Location::where('type', 'warehouse')->value('id'))
                    ->required(),
                TextInput::make('qty')
                    ->label('Quantity')
                    ->required()
                    ->integer()
                    ->minValue(1),
                TextInput::make('price')
                    ->label('Purchase Price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $product = Product::findOrFail($data['product_id']);
        $newPrice = (float) $data['price'];
        $newQty = (int) $data['qty'];

        $existingStock = Stock::where('product_id', $product->id)
            ->where('location_id', $data['location_id'])
            ->first();
        $oldQty = $existingStock ? $existingStock->qty : 0;
        $oldPrice = (float) $product->purchase_price;

        if ($oldQty > 0 && $oldPrice > 0) {
            $avgPrice = round(($oldPrice * $oldQty + $newPrice * $newQty) / ($oldQty + $newQty), 2);
        } else {
            $avgPrice = $newPrice;
        }

        try {
            DB::transaction(function () use ($data, $product, $avgPrice, $newQty) {
                $product->update(['purchase_price' => round($avgPrice, 2)]);

                $stock = Stock::where('product_id', $data['product_id'])
                    ->where('location_id', $data['location_id'])
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->increment('qty', $newQty);
                } else {
                    $stock = Stock::create([
                        'product_id' => $data['product_id'],
                        'location_id' => $data['location_id'],
                        'qty' => $newQty,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $data['product_id'],
                    'location_id' => $data['location_id'],
                    'type' => 'in',
                    'quantity' => $newQty,
                    'notes' => $data['notes'] ?? null,
                    'related_type' => Stock::class,
                    'related_id' => $stock->id,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Stock in failed')
                ->body($e->getMessage() ?: 'An unexpected error occurred.')
                ->danger()
                ->send();

            return;
        }

        $this->form->fill();

        Notification::make()
            ->title('Stock added')
            ->body("{$product->name} — {$newQty} units added to stock at ".($product->stocks->first()?->location?->name ?? 'the selected location').'.')
            ->success()
            ->send();
    }
}
