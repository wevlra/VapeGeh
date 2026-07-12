<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Actions\CompleteStockOpname;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockOpname;
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

class StockOpnameEdit extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static string $resource = StockOpnameResource::class;

    protected static ?string $title = 'Edit Penyesuaian Stok';

    protected ?string $heading = 'Edit Penyesuaian Stok';

    protected string $view = 'filament.admin.pages.stock-opname-edit';

    public \Illuminate\Database\Eloquent\Model|int|string|null $record;

    public ?array $data = [];

    public array $items = [];

    public function mount(int|string $record): void
    {
        $this->record = StockOpname::with('items.product')->findOrFail($record);

        if ($this->record->status !== 'draft') {
            $this->notifyWarning(
                'Penyesuaian Tidak Dapat Diedit',
                "Penyesuaian \"{$this->record->opname_number}\" sudah selesai dan tidak dapat diedit."
            );

            $this->redirect(StockOpnameResource::getUrl('index'));
        }

        $this->form->fill([
            'location_id' => $this->record->location_id,
            'notes' => $this->record->notes,
        ]);

        $this->items = $this->record->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'system_qty' => $item->system_qty,
                'actual_qty' => $item->actual_qty,
                'qty' => $item->actual_qty, // alias for preview rendering
                'difference' => $item->difference,
                'notes' => $item->notes,
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
                ->modalHeading('Selesaikan Stock Opname')
                ->modalDescription(fn (): string => "Yakin ingin menyelesaikan opname {$this->record->opname_number}? Stok di lokasi akan disesuaikan berdasarkan stok fisik.")
                ->action(function (): void {
                    try {
                        app(CompleteStockOpname::class)->execute($this->record);
                        $this->notifySuccess(
                            'Stock Opname Selesai',
                            "Opname \"{$this->record->opname_number}\" telah selesai."
                        );
                        $this->redirect(StockOpnameResource::getUrl('view', ['record' => $this->record->id]));
                    } catch (\DomainException $e) {
                        $this->notifyError('Gagal Menyelesaikan Stock Opname', $e->getMessage());
                    }
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
                            ->description('Pilih lokasi dan catatan')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('location_id')
                                            ->label('Lokasi')
                                            ->options(fn () => Location::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set) {
                                                $this->items = [];
                                            }),
                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(2),
                                    ]),
                            ]),
                        Step::make('Input Stok Fisik')
                            ->description('Masukkan stok fisik untuk setiap produk')
                            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
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
            ->modalHeading('Edit Stok Fisik')
            ->modalDescription(fn (Product $record): string => $record->name)
            ->modalSubmitActionLabel('Simpan')
            ->modalIcon(null)
            ->form(function (Product $record) {
                $locationId = $this->data['location_id'] ?? null;
                $existing = collect($this->items)->firstWhere('product_id', $record->id);
                $systemQty = $locationId
                    ? (int) Stock::where('product_id', $record->id)->where('location_id', $locationId)->value('qty')
                    : 0;

                return [
                    Placeholder::make('system_qty_display')
                        ->label('Stok Sistem Saat Ini')
                        ->content(new HtmlString("<div class='text-2xl font-bold'>{$systemQty}</div>")),
                    Grid::make(['default' => 3])
                        ->schema([
                            Actions::make([
                                Action::make('decrement')
                                    ->button()
                                    ->label('')
                                    ->icon('heroicon-m-minus')
                                    ->color('gray')
                                    ->action(fn (Set $set, Get $get) => $set('actual_qty', max(0, (int) $get('actual_qty') - 1))),
                            ])->alignCenter(),
                            TextInput::make('actual_qty')
                                ->hiddenLabel()
                                ->integer()
                                ->minValue(0)
                                ->default($existing['actual_qty'] ?? $systemQty)
                                ->required()
                                ->live()
                                ->extraAttributes(['class' => 'text-center text-3xl font-bold border-none bg-transparent focus:ring-0']),
                            Actions::make([
                                Action::make('increment')
                                    ->button()
                                    ->label('')
                                    ->icon('heroicon-m-plus')
                                    ->action(fn (Set $set, Get $get) => $set('actual_qty', (int) $get('actual_qty') + 1)),
                            ])->alignCenter(),
                        ]),
                    Placeholder::make('difference')
                        ->hiddenLabel()
                        ->content(function (Get $get) use ($systemQty) {
                            $actualQty = (int) ($get('actual_qty') ?? 0);
                            $diff = $actualQty - $systemQty;
                            $color = $diff > 0 ? 'text-green-500' : ($diff < 0 ? 'text-red-500' : 'text-gray-500');
                            $sign = $diff > 0 ? '+' : '';

                            return new HtmlString("<div class='text-center text-lg {$color} font-medium'>Selisih: {$sign}{$diff}</div>");
                        }),
                    TextInput::make('notes')
                        ->label('Catatan Item')
                        ->default($existing['notes'] ?? null),
                ];
            })
            ->action(function (array $data, Product $record) {
                $locationId = $this->data['location_id'] ?? null;
                $systemQty = $locationId
                    ? (int) Stock::where('product_id', $record->id)->where('location_id', $locationId)->value('qty')
                    : 0;
                $actualQty = (int) $data['actual_qty'];
                $difference = $actualQty - $systemQty;

                $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $record->id);

                $itemData = [
                    'product_id' => $record->id,
                    'name' => $record->name,
                    'system_qty' => $systemQty,
                    'actual_qty' => $actualQty,
                    'qty' => $actualQty, // alias for preview rendering
                    'difference' => $difference,
                    'notes' => $data['notes'] ?? null,
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
                        TextColumn::make('system_qty')
                            ->label('Stok Sistem')
                            ->formatStateUsing(function ($record) {
                                $locationId = $this->data['location_id'] ?? null;
                                $systemQty = $locationId
                                    ? (int) Stock::where('product_id', $record->id)->where('location_id', $locationId)->value('qty')
                                    : 0;

                                return 'Stok Sistem: '.$systemQty;
                            })
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
                    'location_id' => $data['location_id'],
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->record->items()->delete();

                foreach ($items as $item) {
                    $this->record->items()->create([
                        'product_id' => $item['product_id'],
                        'system_qty' => $item['system_qty'],
                        'actual_qty' => $item['actual_qty'],
                        'difference' => $item['difference'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            $this->notifyError('Terjadi Kesalahan', 'Silakan coba lagi atau hubungi administrator.');

            return;
        }

        $this->notifySuccess('Stock Opname Diperbarui', 'Penyesuaian stok berhasil diperbarui.');
        $this->redirect(StockOpnameResource::getUrl('view', ['record' => $this->record->id]));
    }
}
