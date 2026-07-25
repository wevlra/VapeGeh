<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Filament\Concerns\InteractsWithStockItems;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Buyer;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnitEnum;

class EditStockOut extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithStockItems;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Edit Stok Keluar';

    protected static ?string $slug = 'stock-out/{record}/edit';

    protected static ?string $title = 'Edit Stok Keluar';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Edit Stok Keluar';

    protected string $view = 'filament.admin.pages.stock-out';

    public ?array $data = [];

    public array $items = [];

    public StockEntry $entry;

    public function mount(StockEntry $record): void
    {
        $this->entry = $record->load('items.product');

        $this->data = [
            'location_id' => $record->location_id,
            'buyer_id' => $record->buyer_id,
            'payment_method' => $record->payment_method,
            'additional_costs' => $record->additional_costs ?? [],
            'notes' => $record->notes,
            'price_id' => 'price_1',
        ];

        $this->form->fill($this->data);

        $this->items = $record->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'Produk',
                'qty' => $item->qty,
                'price_id' => 'custom',
                'price' => (float) $item->unit_price,
            ];
        })->toArray();
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
                            ->description('Lokasi dan detail')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('location_id')
                                        ->label('Dari Lokasi')
                                        ->options(fn () => Location::where('status', 'active')->pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->helperText('Pilih lokasi pengambilan stok.'),
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
                                        ->createOptionUsing(function (array $data): string {
                                            return Buyer::create($data)->getKey();
                                        }),
                                    Select::make('payment_method')
                                        ->label('Metode Pembayaran (opsional)')
                                        ->options([
                                            'cash' => 'Tunai',
                                            'transfer' => 'Transfer',
                                            'qris' => 'QRIS',
                                        ]),
                                    Select::make('price_id')
                                        ->label('Tipe Harga')
                                        ->options([
                                            'price_1' => 'Harga 1',
                                            'price_2' => 'Harga 2',
                                            'reseller' => 'Harga Reseller',
                                        ])
                                        ->default('price_1')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (callable $set) {
                                            $this->items = [];
                                        }),
                                ]),
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
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                            ->stripCharacters('.')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('Tambah Biaya')
                                    ->defaultItems(0),
                                Textarea::make('notes')
                                    ->label('Catatan (opsional)')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),
                        Step::make('Daftar Produk')
                            ->description('Pilih produk dan harga jual')
                            ->icon(Heroicon::OutlinedCube)
                            ->schema([
                                EmbeddedTable::make(),
                                View::make('filament.admin.components.selected-items-summary'),
                            ]),
                    ]),
            ]);
    }

    protected function buildItemData(Product $record, int $qty, ?array $existing): array
    {
        $priceId = $this->data['price_id'] ?? 'price_1';
        if ($priceId === 'reseller') {
            $resolvedPrice = (float) $record->reseller_price;
        } elseif ($priceId === 'price_1') {
            $resolvedPrice = (float) $record->price_1;
        } elseif ($priceId === 'price_2') {
            $resolvedPrice = (float) $record->price_2;
        } else {
            $resolvedPrice = 0;
        }

        return [
            'product_id' => $record->id,
            'name' => $record->name,
            'qty' => $qty,
            'price_id' => $priceId,
            'price' => $resolvedPrice, // for the selected-items-preview
        ];
    }

    public function table(Table $table): Table
    {

        return $table
            ->query(Product::query()->orderBy('name'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->label('Nama Produk')
                            ->weight('bold')
                            ->searchable(),
                        TextColumn::make('price_1')
                            ->label('Harga 1 Default')
                            ->getStateUsing(function (Product $record) {
                                $priceId = $this->data['price_id'] ?? 'price_1';
                                if ($priceId === 'reseller') {
                                    return (float) $record->reseller_price;
                                }
                                if ($priceId === 'price_1') {
                                    return (float) $record->price_1;
                                }
                                if ($priceId === 'price_2') {
                                    return (float) $record->price_2;
                                }

                                return (float) $record->price_1;
                            })
                            ->formatStateUsing(function ($state): string {
                                return 'Harga: Rp '.number_format((float) $state, 0, ',', '.');
                            })
                            ->color('gray'),
                        TextColumn::make('stock_info')
                            ->label('Stok')
                            ->getStateUsing(function (Product $record) {
                                $locationId = $this->data['location_id'] ?? null;
                                if (! $locationId) {
                                    return 'Pilih lokasi terlebih dahulu';
                                }
                                $stock = $record->stocks()->where('location_id', $locationId)->first();

                                return $stock ? $stock->qty : 0;
                            })
                            ->formatStateUsing(fn ($state) => is_numeric($state) ? "Stok {$state}" : $state)
                            ->color(function ($state) {
                                if (! is_numeric($state)) {
                                    return 'gray';
                                }

                                return $state <= 0 ? 'danger' : ($state < 10 ? 'warning' : 'success');
                            })
                            ->badge(fn ($state) => is_numeric($state)),
                    ]),
                    ViewColumn::make('controls')
                        ->label('Kontrol')
                        ->view('filament.tables.columns.product-inline-controls')
                        ->extraAttributes(['class' => 'w-full md:w-auto flex justify-end'])
                        ->state(fn () => [
                            'showPrice' => false,
                        ]),
                ])->from('md'),
            ]);
    }

    public function removeItem(string $productId): void
    {
        $this->items = collect($this->items)
            ->reject(fn ($item) => $item['product_id'] === $productId)
            ->values()
            ->toArray();
    }

    public function updateItemQty(string $productId, $qty): void
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            $this->removeItem($productId);

            return;
        }

        $record = Product::find($productId);
        if (! $record) {
            return;
        }

        $locationId = $this->data['location_id'] ?? null;
        if ($locationId) {
            $stock = $record->stocks()->where('location_id', $locationId)->first();
            $available = $stock ? $stock->qty : 0;
            if ($qty > $available) {
                $qty = $available;
                $this->notifyWarning("Stok tidak mencukupi. Kuantitas disesuaikan ke stok maksimal ({$qty})");

                if ($qty <= 0) {
                    $this->removeItem($productId);

                    return;
                }
            }
        }

        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);

        $itemData = $this->buildItemData($record, $qty, $existingIndex !== false ? $this->items[$existingIndex] : null);

        if ($existingIndex !== false) {
            $this->items[$existingIndex] = $itemData;
        } else {
            $this->items[] = $itemData;
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $items = $this->items;
        $locationId = $data['location_id'];

        if (empty($items)) {
            $this->notifyError(
                'Tidak Ada Produk',
                'Tambahkan minimal satu produk.'
            );

            return;
        }

        try {
            $movement = DB::transaction(function () use ($data, $items, $locationId) {
                // Restore old stock
                foreach ($this->entry->items as $oldItem) {
                    $stock = Stock::where('product_id', $oldItem->product_id)
                        ->where('location_id', $this->entry->location_id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $stock->qty += $oldItem->qty;
                        $stock->save();
                    } else {
                        Stock::create([
                            'product_id' => $oldItem->product_id,
                            'location_id' => $this->entry->location_id,
                            'qty' => $oldItem->qty,
                        ]);
                    }
                }

                // Delete old items and movements and income
                StockMovement::where('related_type', StockEntry::class)
                    ->where('related_id', $this->entry->id)
                    ->delete();
                $this->entry->items()->delete();
                Income::where('description', 'like', "%(Entry #{$this->entry->id})%")->delete();

                // Update entry
                $this->entry->update([
                    'location_id' => $locationId,
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'additional_costs' => $data['additional_costs'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $totalAmount = 0;

                foreach ($items as $item) {
                    $productId = $item['product_id'];
                    $qty = (int) $item['qty'];
                    $unitPrice = (float) $item['price'];
                    $totalAmount += ($qty * $unitPrice);

                    $stock = Stock::where('product_id', $productId)
                        ->where('location_id', $locationId)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->qty < $qty) {
                        $product = Product::find($productId);

                        throw new DomainException(
                            'Stok tidak mencukupi untuk '.($product?->name ?? 'produk #'.$productId)
                            .'. Tersedia: '.($stock ? $stock->qty : 0)
                        );
                    }

                    $stock->qty -= $qty;
                    $stock->save();

                    $this->entry->items()->create([
                        'product_id' => $productId,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                    ]);
                }

                $additionalCostsTotal = 0;
                if (is_array($this->entry->additional_costs)) {
                    foreach ($this->entry->additional_costs as $cost) {
                        $additionalCostsTotal += (float) ($cost['amount'] ?? 0);
                    }
                }
                $totalIncome = $totalAmount + $additionalCostsTotal;

                if ($totalIncome > 0) {
                    $buyer = Buyer::find($data['buyer_id'] ?? null);
                    $buyerName = $buyer ? $buyer->name : 'Pelanggan';
                    Income::create([
                        'location_id' => $locationId,
                        'category' => 'sale',
                        'description' => "Stok Keluar ke {$buyerName} (Entry #{$this->entry->id})",
                        'amount' => $totalIncome,
                        'date' => now(),
                    ]);
                }

                return StockMovement::create([
                    'product_id' => $items[0]['product_id'],
                    'location_id' => $locationId,
                    'type' => 'out',
                    'quantity' => -collect($items)->sum('qty'),
                    'unit_price' => 0,
                    'related_type' => StockEntry::class,
                    'related_id' => $this->entry->id,
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'additional_costs' => $data['additional_costs'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (DomainException $e) {
            $this->notifyError(
                'Update Stok Keluar Gagal',
                $e->getMessage()
            );

            return;
        } catch (Throwable $e) {
            report($e);

            $this->notifyError(
                'Terjadi Kesalahan',
                'Silakan coba lagi atau hubungi administrator.'
            );

            return;
        }

        $this->notifySuccess(
            'Stok Diperbarui',
            'Data stok keluar berhasil diperbarui.'
        );

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
