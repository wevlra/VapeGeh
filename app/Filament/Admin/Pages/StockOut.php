<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\Buyer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
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
                                Repeater::make('items')
                                    ->label('Produk')
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->addActionLabel('Tambah Produk')
                                    ->table([
                                        TableColumn::make('Produk')->width('45%'),
                                        TableColumn::make('Harga Jual')->width('30%'),
                                        TableColumn::make('Jumlah')->width('25%'),
                                    ])
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Produk')
                                            ->options(fn () => Product::orderBy('name')->get()
                                                ->mapWithKeys(fn (Product $p): array => [$p->id => "{$p->sku} — {$p->name}"])
                                                ->toArray())
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('price_id', null)),
                                        Select::make('price_id')
                                            ->label('Harga Jual')
                                            ->options(fn (callable $get) => $this->getPriceOptions($get('product_id')))
                                            ->helperText(fn (callable $get) => $this->getPriceHelper($get('price_id')))
                                            ->required()
                                            ->searchable()
                                            ->live(),
                                        TextInput::make('qty')
                                            ->label('Jumlah')
                                            ->integer()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required(),
                                    ]),
                            ]),
                    ]),
            ]);
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

        return 'Rp '.number_format((float) $price->price, 0, ',', '.');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $items = $data['items'] ?? [];
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
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($items as $item) {
                    $productId = (int) $item['product_id'];
                    $qty = (int) $item['qty'];
                    $priceId = (int) $item['price_id'];

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

                    $price = ProductPrice::find($priceId);
                    $unitPrice = $price ? (float) $price->price : 0;

                    $entry->items()->create([
                        'product_id' => $productId,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
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

        Notification::make()
            ->title('Stok dikeluarkan')
            ->body(count($items).' produk berhasil dikeluarkan.')
            ->success()
            ->send();

        $this->redirect(HistoryResource::getUrl('view', ['record' => $movement->id]));
    }
}
