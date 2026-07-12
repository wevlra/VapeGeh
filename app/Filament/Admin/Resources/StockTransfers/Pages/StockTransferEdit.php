<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
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

class StockTransferEdit extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
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
                if ((float) $item->price == (float) $item->product->default_price) {
                    $sellingPriceType = 'default';
                } else {
                    foreach ($item->product->prices as $p) {
                        if ((float) $item->price == (float) $p->price) {
                            $sellingPriceType = 'pp_'.$p->id;
                            break;
                        }
                    }
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
                        $this->record->load('items');
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
                                            ->options(fn () => Location::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                        Select::make('to_location_id')
                                            ->label('Ke Lokasi')
                                            ->options(fn () => Location::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->different('from_location_id'),
                                    ]),
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(2),
                            ]),
                        Step::make('Daftar Item')
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
            ->modalHeading('Edit ke Transfer')
            ->modalDescription(fn (Product $record): string => $record->name)
            ->modalSubmitActionLabel('Simpan')
            ->modalIcon(null)
            ->form(function (Product $record) {
                $existing = collect($this->items)->firstWhere('product_id', $record->id);

                return [
                    Select::make('price')
                        ->label('Harga Jual Baru (Opsional)')
                        ->options(function () use ($record) {
                            $options = [];
                            $defaultPrice = (float) $record->default_price;
                            if ($defaultPrice > 0) {
                                $options['default'] = 'Default: Rp '.number_format($defaultPrice, 0, ',', '.');
                            }
                            foreach ($record->prices as $price) {
                                $options['pp_'.$price->id] = "{$price->label}: Rp ".number_format((float) $price->price, 0, ',', '.');
                            }

                            return $options;
                        })
                        ->default($existing['selling_price_type'] ?? null)
                        ->searchable()
                        ->preload()
                        ->nullable(),
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
                            $fromLocationId = $this->data['from_location_id'] ?? null;
                            $oldStock = $fromLocationId
                                ? (int) Stock::where('product_id', $record->id)->where('location_id', $fromLocationId)->value('qty')
                                : 0;
                            $newStock = max(0, $oldStock - $qty);

                            return new HtmlString("<div class='text-center text-lg text-gray-500 font-medium'>Stok Sumber: {$oldStock} &rarr; {$newStock}</div>");
                        }),
                ];
            })
            ->action(function (array $data, Product $record) {
                $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $record->id);

                $sellingPriceType = $data['price'] ?? null;
                $resolvedPrice = null;
                if ($sellingPriceType === 'default') {
                    $resolvedPrice = (float) $record->default_price;
                } elseif ($sellingPriceType && str_starts_with($sellingPriceType, 'pp_')) {
                    $priceId = (int) str_replace('pp_', '', $sellingPriceType);
                    $resolvedPrice = (float) (ProductPrice::find($priceId)?->price ?? 0);
                }

                $itemData = [
                    'product_id' => $record->id,
                    'name' => $record->name,
                    'qty' => (int) $data['qty'],
                    'selling_price_type' => $sellingPriceType,
                    'price' => $resolvedPrice, // for selected-items-preview
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
                        'price' => $item['price'] > 0 ? $item['price'] : null,
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
