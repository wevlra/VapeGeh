<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\Buyer;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
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
use Filament\Support\RawJs;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class StockOut extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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
                                View::make('filament.admin.pages.selected-items-preview'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $addItemAction = Action::make('addItem')
            ->modalHeading('Jumlah Stok Keluar')
            ->modalDescription(fn (Product $record): string => $record->name)
            ->modalSubmitActionLabel('Simpan')
            ->modalIcon(null)
            ->form(function (Product $record) {
                $existing = collect($this->items)->firstWhere('product_id', $record->id);

                return [
                    Select::make('price_id')
                        ->label('Harga Jual')
                        ->options(fn () => $this->getPriceOptions($record->id))
                        ->default($existing['price_id'] ?? null)
                        ->live()
                        ->required(),
                    TextInput::make('manual_price')
                        ->label('Harga Manual')
                        ->numeric()
                        ->prefix('Rp')
                        ->default($existing['manual_price'] ?? null)
                        ->visible(fn (callable $get) => $get('price_id') === 'manual')
                        ->required(fn (callable $get) => $get('price_id') === 'manual'),
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
                            $newStock = max(0, $oldStock - $qty);

                            return new HtmlString("<div class='text-center text-lg text-gray-500 font-medium'>{$oldStock} &rarr; {$newStock}</div>");
                        }),
                ];
            })
            ->action(function (array $data, Product $record) {
                $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $record->id);

                // Resolve price for display in preview
                $priceId = $data['price_id'];
                $resolvedPrice = $priceId === 'manual'
                    ? (float) ($data['manual_price'] ?? 0)
                    : (float) (ProductPrice::find((int) $priceId)?->price ?? 0);

                $itemData = [
                    'product_id' => $record->id,
                    'name' => $record->name,
                    'qty' => (int) $data['qty'],
                    'price_id' => $priceId,
                    'manual_price' => $priceId === 'manual' ? (float) ($data['manual_price'] ?? 0) : null,
                    'price' => $resolvedPrice, // for the selected-items-preview
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
                        TextColumn::make('default_price')
                            ->label('Harga Jual Default')
                            ->formatStateUsing(fn (float $state): string => 'Jual: Rp '.number_format($state, 0, ',', '.'))
                            ->color('gray')
                            ->action($addItemAction),
                    ]),
                ]),
            ]);
    }

    protected function getPriceOptions(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        $options = Product::find($productId)
            ?->prices()
            ->get()
            ->mapWithKeys(fn (ProductPrice $p): array => [
                $p->id => "{$p->label}: Rp ".number_format((float) $p->price, 0, ',', '.'),
            ])
            ->toArray() ?? [];

        $options['manual'] = 'Manual (isi sendiri)';

        return $options;
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
            Notification::make()
                ->title('Tidak ada produk')
                ->body('Tambahkan minimal satu produk.')
                ->danger()
                ->send();

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
                    $priceId = $item['price_id'];
                    $isManual = $priceId === 'manual';
                    $unitPrice = $isManual
                        ? (float) ($item['manual_price'] ?? 0)
                        : (float) (ProductPrice::find((int) $priceId)?->price ?? 0);

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
                    $priceId = $item['price_id'];
                    $isManual = $priceId === 'manual';
                    $unitPrice = $isManual
                        ? (float) ($item['manual_price'] ?? 0)
                        : (float) (ProductPrice::find((int) $priceId)?->price ?? 0);
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
        $this->items = [];

        Notification::make()
            ->title('Stok dikeluarkan')
            ->body(count($items).' produk berhasil dikeluarkan.')
            ->success()
            ->send();

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
