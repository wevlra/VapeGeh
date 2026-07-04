<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\Vendor;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class StockIn extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Stok Masuk';

    protected static ?string $slug = 'stock-in';

    protected static ?string $title = 'Stok Masuk';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Stok Masuk';

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
            ->components([
                Wizard::make()
                    ->columnSpanFull()
                    ->steps([
                        Step::make('Informasi Umum')
                            ->description('Vendor dan lokasi')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('vendor_id')
                                        ->label('Vendor')
                                        ->options(fn () => Vendor::pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->label('Nama')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('contact_person')
                                                ->label('Kontak Person')
                                                ->maxLength(255),
                                            TextInput::make('phone')
                                                ->label('Telepon')
                                                ->tel()
                                                ->maxLength(50),
                                            TextInput::make('email')
                                                ->label('Email')
                                                ->email()
                                                ->maxLength(255),
                                            TextInput::make('address')
                                                ->label('Alamat')
                                                ->maxLength(255),
                                        ])
                                        ->createOptionUsing(function (array $data): int {
                                            return Vendor::create($data)->getKey();
                                        }),
                                    Select::make('location_id')
                                        ->label('Lokasi')
                                        ->options(fn () => Location::pluck('name', 'id'))
                                        ->searchable()
                                        ->default(fn () => Location::where('type', 'warehouse')->value('id'))
                                        ->required(),
                                ]),
                                Textarea::make('notes')
                                    ->label('Catatan (opsional)')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),
                        Step::make('Daftar Produk')
                            ->description('Pilih produk dan jumlah')
                            ->icon(Heroicon::OutlinedCube)
                            ->schema([
                                Repeater::make('items')
                                    ->label('Produk')
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->addActionLabel('Tambah Produk')
                                    ->table([
                                        TableColumn::make('Produk')->width('40%'),
                                        TableColumn::make('Jumlah')->width('15%'),
                                        TableColumn::make('Harga Beli')->width('20%'),
                                        TableColumn::make('Harga Jual Baru')->width('25%'),
                                    ])
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Produk')
                                            ->options(fn () => Product::orderBy('name')->get()
                                                ->mapWithKeys(fn (Product $p): array => [$p->id => "{$p->name}"])
                                                ->toArray())
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn ($state, callable $set) => $set('price', Product::find($state)?->purchase_price ?? 0)),
                                        TextInput::make('qty')
                                            ->label('Jumlah')
                                            ->integer()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required(),
                                        TextInput::make('price')
                                            ->label('Harga Beli')
                                            ->numeric()
                                            ->minValue(0)
                                            ->prefix('Rp')
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                            ->stripCharacters('.')
                                            ->required(),
                                        Select::make('selling_price_type')
                                            ->label('Harga Jual Baru')
                                            ->options(function (callable $get): array {
                                                $productId = $get('product_id');
                                                if (! $productId) {
                                                    return [];
                                                }

                                                $product = Product::find($productId);
                                                if (! $product) {
                                                    return [];
                                                }

                                                $options = [];

                                                $defaultPrice = (float) $product->default_price;
                                                if ($defaultPrice > 0) {
                                                    $options['default'] = 'Default: Rp '.number_format($defaultPrice, 0, ',', '.');
                                                }

                                                foreach ($product->prices as $price) {
                                                    $options['pp_'.$price->id] = "{$price->label}: Rp ".number_format((float) $price->price, 0, ',', '.');
                                                }

                                                $options['custom'] = 'Harga Kustom...';

                                                return $options;
                                            })
                                            ->live()
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                        TextInput::make('custom_selling_price')
                                            ->label('Harga Jual Kustom')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->visible(fn (callable $get) => $get('selling_price_type') === 'custom')
                                            ->required(fn (callable $get) => $get('selling_price_type') === 'custom'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $items = $data['items'] ?? [];
        $location = Location::find($data['location_id']);

        if (empty($items)) {
            Notification::make()
                ->title('Tidak ada produk')
                ->body('Tambahkan minimal satu produk.')
                ->danger()
                ->send();

            return;
        }

        try {
            $movement = DB::transaction(function () use ($data, $items) {
                $entry = StockEntry::create([
                    'type' => 'in',
                    'location_id' => $data['location_id'],
                    'vendor_id' => $data['vendor_id'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $newPrice = (float) $item['price'];
                    $newQty = (int) $item['qty'];

                    $stock = Stock::where('product_id', $item['product_id'])
                        ->where('location_id', $data['location_id'])
                        ->lockForUpdate()
                        ->first();

                    $oldQty = $stock ? $stock->qty : 0;
                    $oldPrice = (float) $product->purchase_price;

                    $avgPrice = ($oldQty > 0 && $oldPrice > 0)
                        ? round(($oldPrice * $oldQty + $newPrice * $newQty) / ($oldQty + $newQty), 2)
                        : $newPrice;

                    $product->update(['purchase_price' => round($avgPrice, 2)]);

                    $sellingPriceType = $item['selling_price_type'] ?? null;
                    if ($sellingPriceType === 'custom') {
                        $newSellingPrice = (float) ($item['custom_selling_price'] ?? 0);
                    } elseif ($sellingPriceType === 'default') {
                        $newSellingPrice = (float) $product->default_price;
                    } elseif ($sellingPriceType && str_starts_with($sellingPriceType, 'pp_')) {
                        $priceId = (int) str_replace('pp_', '', $sellingPriceType);
                        $priceModel = ProductPrice::find($priceId);
                        $newSellingPrice = $priceModel ? (float) $priceModel->price : null;
                    } else {
                        $newSellingPrice = null;
                    }

                    if ($stock) {
                        $stock->increment('qty', $newQty);
                        if ($newSellingPrice > 0) {
                            $stock->update(['price' => $newSellingPrice]);
                        }
                    } else {
                        $stock = Stock::create([
                            'product_id' => $item['product_id'],
                            'location_id' => $data['location_id'],
                            'qty' => $newQty,
                            'price' => $newSellingPrice > 0 ? $newSellingPrice : null,
                        ]);
                    }

                    $entry->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $newQty,
                        'unit_price' => $newPrice,
                    ]);
                }

                return StockMovement::create([
                    'product_id' => $items[0]['product_id'],
                    'location_id' => $data['location_id'],
                    'type' => 'in',
                    'quantity' => collect($items)->sum('qty'),
                    'notes' => $data['notes'] ?? null,
                    'related_type' => StockEntry::class,
                    'related_id' => $entry->id,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Stok masuk gagal')
                ->body($e->getMessage() ?: 'Terjadi kesalahan tak terduga.')
                ->danger()
                ->send();

            return;
        }

        $this->form->fill();

        Notification::make()
            ->title('Stok ditambahkan')
            ->body(count($items).' produk ditambahkan ke '.($location?->name ?? 'lokasi yang dipilih').'.')
            ->success()
            ->send();

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
