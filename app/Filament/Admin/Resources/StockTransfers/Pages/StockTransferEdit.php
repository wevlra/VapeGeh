<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\InteractsWithStockItems;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Filament\Actions\Action;
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

class StockTransferEdit extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithStockItems;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static string $resource = StockTransferResource::class;

    protected static ?string $title = 'Edit Transfer Stok';

    protected ?string $heading = 'Edit Transfer Stok';

    protected string $view = 'filament.admin.pages.stock-transfer-edit';

    public \Illuminate\Database\Eloquent\Model|int|string|null $record;

    public ?array $data = [];

    public array $items = [];

    public function mount(int|string $record): void
    {
        $this->record = StockTransfer::with('items.product')->findOrFail($record);

        if ($this->record->status !== 'pending') {
            $this->notifyWarning(
                'Transfer Tidak Dapat Diedit',
                "Transfer \"{$this->record->transfer_number}\" saat ini \"{$this->record->status}\". Hanya transfer tertunda yang dapat diedit."
            );

            $this->redirect(StockTransferResource::getUrl('index'));
        }

        $this->form->fill([
            'from_location_id' => $this->record->from_location_id,
            'to_location_id' => $this->record->to_location_id,
            'notes' => $this->record->notes,
        ]);

        $this->items = $this->record->items->map(function ($item) {
            $sellingPriceType = null;
            if ($item->price) {
                if ((float) $item->price == (float) $item->product->selling_price) {
                    $sellingPriceType = 'default';
                } elseif ((float) $item->price == (float) $item->product->reseller_price) {
                    $sellingPriceType = 'reseller';
                } elseif ((float) $item->price == (float) $item->product->price_1) {
                    $sellingPriceType = 'price_1';
                } elseif ((float) $item->price == (float) $item->product->price_2) {
                    $sellingPriceType = 'price_2';
                }
            }

            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'qty' => $item->qty,
                'price' => $item->price,
                'selling_price_type' => $sellingPriceType,
            ];
        })->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Transfer Stok')
                ->modalDescription(fn (): string => "Yakin ingin menyelesaikan transfer {$this->record->transfer_number}?")
                ->action(function (): void {
                    try {
                        app(CompleteStockTransfer::class)->execute($this->record, auth()->user());
                        $this->notifySuccess(
                            'Transfer Selesai',
                            "Transfer \"{$this->record->transfer_number}\" telah selesai. Stok telah dipindahkan antar lokasi."
                        );
                        $this->redirect(StockTransferResource::getUrl('view', ['record' => $this->record->id]));
                    } catch (\DomainException $e) {
                        $this->notifyError('Gagal Menyelesaikan Transfer Stok', $e->getMessage());
                    }
                }),
            Action::make('cancel')
                ->label('Batal')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Transfer Stok')
                ->modalDescription(fn (): string => "Batalkan transfer {$this->record->transfer_number}? Ini tidak akan memengaruhi stok.")
                ->action(function (): void {
                    DB::transaction(function () {
                        $this->record->load('items.product');
                        foreach ($this->record->items as $item) {
                            StockMovement::create([
                                'product_id' => $item->product_id,
                                'location_id' => $this->record->from_location_id,
                                'type' => 'adjustment',
                                'quantity' => 0,
                                'notes' => "Transfer #{$this->record->transfer_number} cancelled",
                                'related_type' => StockTransfer::class,
                                'related_id' => $this->record->id,
                                'created_by' => auth()->id(),
                            ]);
                        }
                        $this->record->status = 'cancelled';
                        $this->record->save();
                    });

                    $this->notifyWarning(
                        'Transfer Dibatalkan',
                        "Transfer \"{$this->record->transfer_number}\" telah dibatalkan. Tidak ada stok yang terpengaruh."
                    );
                    $this->redirect(StockTransferResource::getUrl('view', ['record' => $this->record->id]));
                }),
        ];
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
                            ->description('Pilih sumber dan tujuan')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('from_location_id')
                                            ->label('Dari Lokasi')
                                            ->options(fn () => Location::where('status', 'active')->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                        Select::make('to_location_id')
                                            ->label('Ke Lokasi')
                                            ->options(fn () => Location::where('status', 'active')->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->different('from_location_id'),
                                        Select::make('price_id')
                                            ->label('Update Harga Tujuan (Ops.)')
                                            ->options([
                                                'default' => 'Harga Default',
                                                'reseller' => 'Harga Reseller',
                                                'price_1' => 'Harga 1',
                                                'price_2' => 'Harga 2',
                                            ])
                                            ->placeholder('Tetap / Tidak Diubah')
                                            ->nullable()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set) {
                                                $this->items = [];
                                            }),
                                    ]),
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(2),
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
        $sellingPriceType = $this->data['price_id'] ?? null;
        $resolvedPrice = null;
        if ($sellingPriceType === 'default') {
            $resolvedPrice = 0;
        } elseif ($sellingPriceType === 'reseller') {
            $resolvedPrice = (float) $record->reseller_price;
        } elseif ($sellingPriceType === 'price_1') {
            $resolvedPrice = (float) $record->price_1;
        } elseif ($sellingPriceType === 'price_2') {
            $resolvedPrice = (float) $record->price_2;
        }

        return [
            'product_id' => $record->id,
            'name' => $record->name,
            'qty' => $qty,
            'selling_price_type' => $sellingPriceType,
            'price' => $resolvedPrice, // for selected-items-preview
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
                            ->label('Harga Jual')
                            ->getStateUsing(function (Product $record) {
                                $priceId = $this->data['price_id'] ?? null;
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
                                $priceId = $this->data['price_id'] ?? null;
                                if ($priceId === null) {
                                    return 'Harga Tujuan: Tetap';
                                }

                                return 'Harga Tujuan: Rp '.number_format((float) $state, 0, ',', '.');
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

        if (empty($items)) {
            $this->notifyError('Tidak Ada Produk', 'Tambahkan minimal satu produk.');

            return;
        }

        try {
            DB::transaction(function () use ($data, $items) {
                $this->record->update([
                    'from_location_id' => $data['from_location_id'],
                    'to_location_id' => $data['to_location_id'],
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->record->items()->delete();

                foreach ($items as $item) {
                    $this->record->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'price' => $item['price'] !== null ? (float) $item['price'] : null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            $this->notifyError('Terjadi Kesalahan', 'Silakan coba lagi atau hubungi administrator.');

            return;
        }

        $this->notifySuccess('Transfer Diperbarui', 'Transfer stok berhasil diperbarui.');
        $this->redirect(StockTransferResource::getUrl('view', ['record' => $this->record->id]));
    }
}
