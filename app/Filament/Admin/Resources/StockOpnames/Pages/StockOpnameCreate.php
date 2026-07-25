<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Concerns\InteractsWithStockItems;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockOpname;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
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
use Illuminate\Support\Str;
use Throwable;

class StockOpnameCreate extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithStockItems;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static string $resource = StockOpnameResource::class;

    protected static ?string $title = 'Buat Penyesuaian Stok';

    protected ?string $heading = 'Buat Penyesuaian Stok';

    protected string $view = 'filament.admin.pages.stock-opname-create';

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
                        Step::make('Lokasi')
                            ->description('Pilih lokasi dan catatan')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('location_id')
                                            ->label('Lokasi')
                                            ->options(fn () => Location::where('status', 'active')->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set) {
                                                $this->items = [];
                                            }),
                                        Hidden::make('price_type')
                                            ->default('reseller_price'),
                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Step::make('Input Stok Fisik')
                            ->description('Masukkan stok fisik untuk setiap produk')
                            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                            ->schema([
                                EmbeddedTable::make(),
                            ]),
                    ]),
            ]);
    }

    protected function buildItemData(Product $record, int $qty, ?array $existing): array
    {
        $locationId = $this->data['location_id'] ?? null;
        $systemQty = $locationId
            ? (int) Stock::where('product_id', $record->id)->where('location_id', $locationId)->value('qty')
            : 0;

        $actualQty = $qty;
        $difference = $actualQty - $systemQty;

        return [
            'product_id' => $record->id,
            'name' => $record->name,
            'system_qty' => $systemQty,
            'actual_qty' => $actualQty,
            'qty' => $actualQty, // alias for preview rendering
            'difference' => $difference,
            'notes' => $existing['notes'] ?? null,
        ];
    }

    protected function getItemQty(string $productId): int
    {
        $existing = collect($this->items)->firstWhere('product_id', $productId);
        if ($existing) {
            return (int) ($existing['qty'] ?? 0);
        }

        $locationId = $this->data['location_id'] ?? null;

        return $locationId
            ? (int) Stock::where('product_id', $productId)->where('location_id', $locationId)->value('qty')
            : 0;
    }

    public function updateItemQty(string $productId, $qty): void
    {
        $qty = (int) $qty;
        if ($qty < 0) {
            $qty = 0;
        }

        $record = Product::find($productId);
        if (! $record) {
            return;
        }

        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);
        $itemData = $this->buildItemData($record, $qty, $existingIndex !== false ? $this->items[$existingIndex] : null);

        if ($itemData['difference'] === 0) {
            if ($existingIndex !== false) {
                unset($this->items[$existingIndex]);
                $this->items = array_values($this->items);
            }

            return;
        }

        if ($existingIndex !== false) {
            $this->items[$existingIndex] = $itemData;
        } else {
            $this->items[] = $itemData;
        }
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
                    ]),
                    ViewColumn::make('controls')
                        ->label('Kontrol Fisik')
                        ->view('filament.tables.columns.product-inline-controls')
                        ->extraAttributes(['class' => 'w-full md:w-auto flex justify-end'])
                        ->state(fn () => [
                            'showPrice' => false,
                            'showDifference' => true,
                            'qtyLabel' => 'Fisik:',
                            'defaultQty' => function (Product $record) {
                                $locationId = $this->data['location_id'] ?? null;

                                return $locationId
                                    ? (int) Stock::where('product_id', $record->id)->where('location_id', $locationId)->value('qty')
                                    : 0;
                            },
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
        $items = collect($this->items)->filter(fn ($item) => $item['difference'] !== 0)->values()->toArray();

        if (empty($items)) {
            $this->notifyError('Tidak Ada Produk', 'Tambahkan minimal satu produk dengan selisih stok.');

            return;
        }

        try {
            $opname = DB::transaction(function () use ($data, $items) {
                $opname = StockOpname::create([
                    'opname_number' => 'SO-'.strtoupper(Str::random(8)),
                    'location_id' => $data['location_id'],
                    'price_type' => $data['price_type'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'status' => 'draft',
                ]);

                foreach ($items as $item) {
                    $opname->items()->create([
                        'product_id' => $item['product_id'],
                        'system_qty' => $item['system_qty'],
                        'actual_qty' => $item['actual_qty'],
                        'difference' => $item['difference'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                return $opname;
            });
        } catch (Throwable $e) {
            report($e);
            $this->notifyError('Terjadi Kesalahan', 'Silakan coba lagi atau hubungi administrator.');

            return;
        }

        $this->notifySuccess('Stock Opname Dibuat', 'Penyesuaian stok berhasil dibuat dengan status draft.');
        $this->redirect(StockOpnameResource::getUrl('view', ['record' => $opname->id]));
    }
}
