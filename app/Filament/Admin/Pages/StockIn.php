<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class StockIn extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Stok Masuk';

    protected static ?string $slug = 'stock-in';

    protected static ?string $title = 'Stok Masuk';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Stok Masuk';

    protected string $view = 'filament.admin.pages.stock-in';

    public ?array $data = [];

    public array $items = [];

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
                                EmbeddedTable::make(),
                                View::make('filament.admin.pages.selected-items-preview'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $addItemAction = Action::make('addItem')
            ->modalHeading('Jumlah Stok Masuk')
            ->modalDescription(fn (Product $record): string => $record->name)
            ->modalSubmitActionLabel('Simpan')
            ->modalIcon(null)
            ->form(function (Product $record) {
                $existing = collect($this->items)->firstWhere('product_id', $record->id);

                return [
                    TextInput::make('price')
                        ->label('Harga Beli')
                        ->numeric()
                        ->prefix('Rp')
                        ->default($existing['price'] ?? $record->purchase_price)
                        ->required(),
                    Select::make('selling_price_type')
                        ->label('Harga Jual Baru')
                        ->options(function () use ($record) {
                            $options = [];
                            $defaultPrice = (float) $record->default_price;
                            if ($defaultPrice > 0) {
                                $options['default'] = 'Default: Rp '.number_format($defaultPrice, 0, ',', '.');
                            }
                            foreach ($record->prices as $price) {
                                $options['pp_'.$price->id] = "{$price->label}: Rp ".number_format((float) $price->price, 0, ',', '.');
                            }
                            $options['custom'] = 'Harga Kustom...';

                            return $options;
                        })
                        ->default($existing['selling_price_type'] ?? null)
                        ->live()
                        ->nullable(),
                    TextInput::make('custom_selling_price')
                        ->label('Harga Jual Kustom')
                        ->numeric()
                        ->prefix('Rp')
                        ->default($existing['custom_selling_price'] ?? null)
                        ->visible(fn (callable $get) => $get('selling_price_type') === 'custom')
                        ->required(fn (callable $get) => $get('selling_price_type') === 'custom'),
                    Grid::make(['default' => 3])
                        ->schema([
                            Actions::make([
                                Action::make('decrement')
                                    ->button()
                                    ->label('')
                                    ->icon('heroicon-m-minus')
                                    ->color('gray')
                                    ->action(fn (Set $set, Get $get) => $set('qty', max(1, (int) $get('qty') - 1))),
                            ])->alignCenter(),
                            TextInput::make('qty')
                                ->hiddenLabel()
                                ->integer()
                                ->minValue(1)
                                ->default($existing['qty'] ?? 1)
                                ->required()
                                ->live()
                                ->extraAttributes(['class' => 'text-center text-3xl font-bold border-none bg-transparent focus:ring-0']),
                            Actions::make([
                                Action::make('increment')
                                    ->button()
                                    ->label('')
                                    ->icon('heroicon-m-plus')
                                    ->action(fn (Set $set, Get $get) => $set('qty', (int) $get('qty') + 1)),
                            ])->alignCenter(),
                        ]),
                    Placeholder::make('stock_comparison')
                        ->hiddenLabel()
                        ->content(function (Get $get) use ($record) {
                            $qty = (int) ($get('qty') ?? 1);
                            $locationId = $this->data['location_id'] ?? null;
                            $oldStock = $locationId
                                ? (int) Stock::where('product_id', $record->id)->where('location_id', $locationId)->value('qty')
                                : 0;
                            $newStock = $oldStock + $qty;

                            return new HtmlString("<div class='text-center text-lg text-gray-500 font-medium'>{$oldStock} &rarr; {$newStock}</div>");
                        }),
                ];
            })
            ->action(function (array $data, Product $record) {
                $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $record->id);

                $itemData = [
                    'product_id' => $record->id,
                    'name' => $record->name,
                    'qty' => (int) $data['qty'],
                    'price' => (float) $data['price'],
                    'selling_price_type' => $data['selling_price_type'] ?? null,
                    'custom_selling_price' => $data['selling_price_type'] === 'custom' ? (float) ($data['custom_selling_price'] ?? 0) : null,
                ];

                if ($existingIndex !== false) {
                    $this->items[$existingIndex] = $itemData;
                } else {
                    $this->items[] = $itemData;
                }
            });

        return $table
            ->query(Product::query()->orderBy('name'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->label('Nama Produk')
                            ->weight('bold')
                            ->searchable()
                            ->action($addItemAction),
                        TextColumn::make('purchase_price')
                            ->label('Harga Beli Terakhir')
                            ->formatStateUsing(fn (float $state): string => 'Beli: Rp '.number_format($state, 0, ',', '.'))
                            ->color('gray')
                            ->action($addItemAction),
                    ]),
                    Stack::make([
                        TextColumn::make('default_price')
                            ->label('Harga Jual Default')
                            ->formatStateUsing(fn (float $state): string => 'Jual: Rp '.number_format($state, 0, ',', '.'))
                            ->alignEnd()
                            ->color('gray')
                            ->action($addItemAction),
                    ])->space(1),
                ]),
            ]);
    }

    public function removeItem(int $productId): void
    {
        $this->items = collect($this->items)
            ->reject(fn ($item) => $item['product_id'] === $productId)
            ->values()
            ->toArray();
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $items = $this->items;
        $location = Location::find($data['location_id']);

        if (empty($items)) {
            $this->notifyError(
                'Tidak Ada Produk',
                'Tambahkan minimal satu produk.'
            );

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

                $totalAmount = 0;
                foreach ($items as $item) {
                    $newPrice = (float) $item['price'];
                    $newQty = (int) $item['qty'];
                    $totalAmount += ($newPrice * $newQty);
                }

                if ($totalAmount > 0) {
                    $vendor = Vendor::find($data['vendor_id']);
                    $vendorName = $vendor ? $vendor->name : 'Vendor';
                    Expense::create([
                        'location_id' => $data['location_id'],
                        'category' => 'purchase',
                        'description' => "Stok Masuk dari {$vendorName} (Entry #{$entry->id})",
                        'amount' => $totalAmount,
                        'date' => now(),
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

            $this->notifyError(
                'Stok Masuk Gagal',
                $e->getMessage() ?: 'Terjadi kesalahan tak terduga.'
            );

            return;
        }

        $this->form->fill();
        $this->items = [];

        $this->notifySuccess(
            'Stok Ditambahkan',
            count($items).' produk ditambahkan ke '.($location?->name ?? 'lokasi yang dipilih').'.'
        );

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
