<?php

namespace App\Filament\Admin\Pages;

use App\Models\Buyer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
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

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Stok Keluar';

    protected static ?string $slug = 'stock-out';

    protected static ?string $title = 'Stok Keluar';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Stok Keluar';

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
                    ->label('Produk')
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
                    ->label('Harga (Harga Jual)')
                    ->options(fn (callable $get) => $this->getPriceOptions($get('product_id')))
                    ->helperText(fn (callable $get) => $this->getPriceHelper($get('price_id')))
                    ->searchable()
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Select::make('location_id')
                    ->label('Dari Lokasi')
                    ->options(fn (callable $get) => $this->getLocationOptionsWithStock($get('product_id')))
                    ->getOptionLabelUsing(fn ($value) => $this->getLocationLabel($value))
                    ->searchable()
                    ->required()
                    ->live()
                    ->helperText(fn (callable $get) => $this->getStockHelper($get('product_id'), $get('location_id'))),
                TextInput::make('qty')
                    ->label('Jumlah')
                    ->required()
                    ->integer()
                    ->minValue(1),
                Select::make('buyer_id')
                    ->label('Pembeli (opsional)')
                    ->options(fn () => Buyer::pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Buyer::create($data)->getKey();
                    })
                    ->columnSpanFull(),
                Repeater::make('additional_costs')
                    ->label('Biaya Tambahan (opsional)')
                    ->schema([
                        TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->placeholder('Misal: Pengiriman, Kemasan')
                            ->columnSpan(2),
                        TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->addActionLabel('Tambah Biaya')
                    ->defaultItems(0)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Catatan (opsional)')
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

        return 'Harga per unit: Rp '.number_format((float) $price->price, 0, ',', '.').' × jumlah = subtotal';
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
                $stock->location_id => "{$stock->location->name} ({$stock->qty} tersedia)",
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

        return $stock ? "{$location->name} ({$stock->qty} tersedia)" : $location->name;
    }

    protected function getStockHelper(?int $productId, ?int $locationId): string
    {
        $stock = $this->getStockRecord($productId, $locationId);

        if (! $productId) {
            return 'Pilih produk terlebih dahulu.';
        }

        if (! $locationId) {
            return '';
        }

        if (! $stock) {
            return 'Tidak ada stok di lokasi ini.';
        }

        return "{$stock->qty} tersedia.";
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
            $result = DB::transaction(function () use ($data, $productId, $locationId, $qty, $priceId) {
                $stock = Stock::where('product_id', $productId)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->qty < $qty) {
                    throw new DomainException('Stok tidak mencukupi.');
                }

                $stock->qty -= $qty;
                $stock->save();

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
                    'related_type' => Stock::class,
                    'related_id' => $stock->id,
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'additional_costs' => $additionalCosts ?: null,
                    'notes' => $data['notes'] ?? null,
                ]);

                return compact('unitPrice', 'subtotal', 'totalCost');
            });
        } catch (DomainException $e) {
            Notification::make()
                ->title('Stok keluar gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Silakan coba lagi atau hubungi administrator.')
                ->danger()
                ->send();

            return;
        }

        $this->form->fill();

        $body = $qty.' unit dikeluarkan. Subtotal: Rp '.number_format($result['subtotal'], 0, ',', '.').' ('.$qty.' × Rp '.number_format($result['unitPrice'], 0, ',', '.').')';
        if ($result['totalCost'] > 0) {
            $body .= ' + Biaya Tambahan: Rp '.number_format($result['totalCost'], 0, ',', '.');
        }

        Notification::make()
            ->title('Stok dikeluarkan')
            ->body($body)
            ->success()
            ->send();
    }
}
