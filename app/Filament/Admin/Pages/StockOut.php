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

class StockOut extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithStockItems;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Stok Keluar';

    protected static ?string $slug = 'stock-out';

    protected static ?string $title = 'Stok Keluar';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Stok Keluar';

    protected string $view = 'filament.admin.pages.stock-out';

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
                            ->description('Lokasi dan detail')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('location_id')
                                        ->label('Dari Lokasi')
                                        ->options(fn () => Location::pluck('name', 'id'))
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
                                        ->createOptionUsing(function (array $data): int {
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
                                        ->label('Tipe Harga Jual')
                                        ->options([
                                            'default' => 'Harga Default',
                                            'reseller' => 'Harga Reseller',
                                            'price_1' => 'Harga 1',
                                            'price_2' => 'Harga 2',
                                        ])
                                        ->default('default')
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
                            ]),
                    ]),
            ]);
    }

    protected function buildItemData(Product $record, int $qty, ?array $existing): array
    {
        $priceId = $this->data['price_id'] ?? 'default';
        if ($priceId === 'default') {
            $resolvedPrice = (float) $record->selling_price;
        } elseif ($priceId === 'reseller') {
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
                        TextColumn::make('selling_price')
                            ->label('Harga Jual Default')
                            ->getStateUsing(function (Product $record) {
                                $priceId = $this->data['price_id'] ?? 'default';
                                if ($priceId === 'reseller') {
                                    return (float) $record->reseller_price;
                                }
                                if ($priceId === 'price_1') {
                                    return (float) $record->price_1;
                                }
                                if ($priceId === 'price_2') {
                                    return (float) $record->price_2;
                                }

                                return (float) $record->selling_price;
                            })
                            ->formatStateUsing(function ($state): string {
                                return 'Harga: Rp '.number_format((float) $state, 0, ',', '.');
                            })
                            ->color('gray'),
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
        $locationId = (int) $data['location_id'];

        if (empty($items)) {
            $this->notifyError(
                'Tidak Ada Produk',
                'Tambahkan minimal satu produk.'
            );

            return;
        }

        try {
            $movement = DB::transaction(function () use ($data, $items, $locationId) {
                $entry = StockEntry::create([
                    'type' => 'out',
                    'location_id' => $locationId,
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'additional_costs' => $data['additional_costs'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($items as $item) {
                    $productId = (int) $item['product_id'];
                    $qty = (int) $item['qty'];
                    $unitPrice = (float) $item['price'];

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

                    $entry->items()->create([
                        'product_id' => $productId,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                    ]);
                }

                $totalAmount = 0;
                foreach ($items as $item) {
                    $unitPrice = (float) $item['price'];
                    $totalAmount += ((int) $item['qty'] * $unitPrice);
                }

                $additionalCostsTotal = 0;
                if (is_array($entry->additional_costs)) {
                    foreach ($entry->additional_costs as $cost) {
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
                        'description' => "Stok Keluar ke {$buyerName} (Entry #{$entry->id})",
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
                    'related_id' => $entry->id,
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'additional_costs' => $data['additional_costs'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (DomainException $e) {
            $this->notifyError(
                'Stok Keluar Gagal',
                $e->getMessage()
            );

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->notifyError(
                'Terjadi Kesalahan',
                'Silakan coba lagi atau hubungi administrator.'
            );

            return;
        }

        $this->form->fill();
        $this->items = [];

        $this->notifySuccess(
            'Stok Dikeluarkan',
            count($items).' produk berhasil dikeluarkan dari sistem.'
        );

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
