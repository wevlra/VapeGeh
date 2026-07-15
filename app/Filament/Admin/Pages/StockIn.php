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

class StockIn extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithStockItems;
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
            'price' => $existing ? (float) $existing['price'] : (float) $record->purchase_price,
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

                $totalAmount = 0;

                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $newPrice = (float) $item['price'];
                    $newQty = (int) $item['qty'];
                    $totalAmount += ($newPrice * $newQty);

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

                    if ($stock) {
                        $stock->increment('qty', $newQty);
                    } else {
                        $stock = Stock::create([
                            'product_id' => $item['product_id'],
                            'location_id' => $data['location_id'],
                            'qty' => $newQty,
                            'price' => null,
                        ]);
                    }

                    $entry->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $newQty,
                        'unit_price' => $newPrice,
                    ]);
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
            count($items).' produk berhasil ditambahkan ke '.($location?->name ?? 'lokasi yang dipilih').'.'
        );

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
