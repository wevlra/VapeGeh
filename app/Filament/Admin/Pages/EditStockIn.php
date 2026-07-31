<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Filament\Concerns\InteractsWithStockItems;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\Vendor;
use BackedEnum;
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

class EditStockIn extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithStockItems;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Edit Stok Masuk';

    protected static ?string $slug = 'stock-in/{record}/edit';

    protected static ?string $title = 'Edit Stok Masuk';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = 'Edit Stok Masuk';

    protected string $view = 'filament.admin.pages.stock-in';

    public ?array $data = [];

    public array $items = [];

    public StockEntry $entry;

    public function mount(StockEntry $record): void
    {
        $this->entry = $record->load('items.product');

        $this->data = [
            'vendor_id' => $record->vendor_id,
            'location_id' => $record->location_id,
            'notes' => $record->notes,
        ];

        $this->form->fill($this->data);

        $this->items = $record->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'Produk',
                'qty' => $item->qty,
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
                                        ->options(fn () => Location::where('status', 'active')->pluck('name', 'id'))
                                        ->searchable()
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
                                View::make('filament.admin.components.selected-items-summary'),
                            ]),
                    ]),
            ]);
    }

    protected function buildItemData(Product $record, int $qty, ?array $existing): array
    {
        return [
            'product_id' => $record->id,
            'name' => $record->name,
            'qty' => $qty,
            'price' => $existing ? (float) $existing['purchase_price'] : (float) $record->purchase_price,
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
                        TextColumn::make('purchase_price')
                            ->label('Harga Beli Terakhir')
                            ->formatStateUsing(fn (float $state): string => 'Harga beli: Rp '.number_format($state, 0, ',', '.'))
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
                            'showPrice' => true,
                            'priceLabel' => 'Harga Beli:',
                            'defaultPriceCallable' => fn (Product $p) => $p->purchase_price,
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
                // Restore old stock (subtract qty and recompute WAC from remaining movements)
                foreach ($this->entry->items as $oldItem) {
                    $stock = Stock::where('product_id', $oldItem->product_id)
                        ->where('location_id', $this->entry->location_id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $remainingQty = $stock->qty - $oldItem->qty;
                        if ($remainingQty <= 0) {
                            $stock->qty = 0;
                            $stock->purchase_price = null;
                        } else {
                            // Recompute WAC from stock_movements excluding this entry's movements
                            $remainingValue = DB::table('stock_movements')
                                ->where('product_id', $oldItem->product_id)
                                ->where('location_id', $this->entry->location_id)
                                ->where('type', 'in')
                                ->where('related_type', StockEntry::class)
                                ->where('related_id', '!=', $this->entry->id)
                                ->selectRaw('SUM(quantity * unit_price) as total_value, SUM(quantity) as total_qty')
                                ->first();

                            if ($remainingValue && $remainingValue->total_qty > 0) {
                                $stock->purchase_price = round($remainingValue->total_value / $remainingValue->total_qty, 2);
                            }
                            $stock->qty = $remainingQty;
                        }
                        $stock->save();
                    }
                }

                // Delete old items and movements and expenses
                StockMovement::where('related_type', StockEntry::class)
                    ->where('related_id', $this->entry->id)
                    ->delete();
                $this->entry->items()->delete();
                Expense::where('description', 'like', "%(Entry #{$this->entry->id})%")->delete();

                // Update entry
                $this->entry->update([
                    'location_id' => $locationId,
                    'vendor_id' => $data['vendor_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $totalAmount = 0;

                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $newPrice = (float) $item['price'];
                    $newQty = (int) $item['qty'];
                    $totalAmount += ($newPrice * $newQty);

                    $stock = Stock::where('product_id', $item['product_id'])
                        ->where('location_id', $locationId)
                        ->lockForUpdate()
                        ->first();

                    // Update product master price as simple average (for reference only)
                    $oldPrice = (float) $product->purchase_price;

                    if ($stock) {
                        // Weighted average cost: (oldQty × oldStockWAC + newQty × newPrice) / (oldQty + newQty)
                        // Always use stock->purchase_price (the running WAC), NOT product->purchase_price
                        $oldStockPrice = (float) ($stock->purchase_price > 0 ? $stock->purchase_price : $newPrice);
                        $oldStockQty = (int) $stock->qty;
                        $newTotalQty = $oldStockQty + $newQty;
                        $wac = $newTotalQty > 0
                            ? (($oldStockQty * $oldStockPrice) + ($newQty * $newPrice)) / $newTotalQty
                            : $newPrice;

                        $stock->qty += $newQty;
                        $stock->purchase_price = round($wac, 2);
                        $stock->save();
                    } else {
                        $wac = $newPrice;
                        $stock = Stock::create([
                            'product_id' => $item['product_id'],
                            'location_id' => $locationId,
                            'qty' => $newQty,
                            'purchase_price' => round($newPrice, 2),
                        ]);
                    }

                    // Sync product master price with WAC (same as stock purchase_price)
                    $product->update(['purchase_price' => round($wac, 2)]);

                    $this->entry->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $newQty,
                        'unit_price' => $newPrice,
                    ]);
                }

                if ($totalAmount > 0) {
                    $vendor = Vendor::find($data['vendor_id']);
                    $vendorName = $vendor ? $vendor->name : 'Vendor';
                    Expense::create([
                        'location_id' => $locationId,
                        'category' => 'purchase',
                        'description' => "Stok Masuk dari {$vendorName} (Entry #{$this->entry->id})",
                        'amount' => $totalAmount,
                        'date' => now(),
                    ]);
                }

                return StockMovement::create([
                    'product_id' => $items[0]['product_id'],
                    'location_id' => $locationId,
                    'type' => 'in',
                    'quantity' => collect($items)->sum('qty'),
                    'notes' => $data['notes'] ?? null,
                    'related_type' => StockEntry::class,
                    'related_id' => $this->entry->id,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            $this->notifyError(
                'Update Stok Masuk Gagal',
                $e->getMessage() ?: 'Terjadi kesalahan tak terduga.'
            );

            return;
        }

        $this->notifySuccess(
            'Stok Diperbarui',
            'Data stok masuk berhasil diperbarui.'
        );

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
