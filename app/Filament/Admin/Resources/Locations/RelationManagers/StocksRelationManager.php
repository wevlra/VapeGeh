<?php

namespace App\Filament\Admin\Resources\Locations\RelationManagers;

use App\Actions\AdjustStock;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Stock;
use Filament\Actions\EditAction as TableEditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    use NotifiesWithDetail;

    protected static string $relationship = 'stocks';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Jumlah')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga Jual Outlet')
                    ->money('IDR')
                    ->placeholder('Default')
                    ->sortable(),
            ])
            ->defaultSort('product.name')
            ->recordActions([
                TableEditAction::make()
                    ->modalHeading('Sesuaikan Stok & Harga')
                    ->form([
                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(fn (Stock $record): int => $record->qty),
                        Select::make('price_type')
                            ->label('Harga Jual Outlet')
                            ->options(function (Stock $record): array {
                                $product = $record->product;
                                $options = [];

                                $defaultPrice = (float) $product->default_price;
                                if ($defaultPrice > 0) {
                                    $options['default'] = 'Default: Rp '.number_format($defaultPrice, 0, ',', '.');
                                }

                                foreach ($product->prices as $price) {
                                    $options['pp_'.$price->id] = "{$price->label}: Rp ".number_format((float) $price->price, 0, ',', '.');
                                }

                                $options['custom'] = 'Harga Kustom...';

                                return $options;
                            })
                            ->default(function (Stock $record): ?string {
                                if ($record->price === null) {
                                    return null;
                                }
                                $product = $record->product;
                                $prices = $product->prices->pluck('price')->map(fn ($p) => (string) $p)->toArray();
                                if ((float) $record->price == (float) $product->default_price) {
                                    return 'default';
                                }

                                foreach ($product->prices as $p) {
                                    if ((float) $record->price == (float) $p->price) {
                                        return 'pp_'.$p->id;
                                    }
                                }

                                return 'custom';
                            })
                            ->live()
                            ->nullable(),
                        TextInput::make('custom_price')
                            ->label('Harga Jual Kustom')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Stock $record): ?float => $record->price)
                            ->visible(fn (callable $get) => $get('price_type') === 'custom')
                            ->required(fn (callable $get) => $get('price_type') === 'custom'),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->maxLength(1000),
                    ])
                    ->action(function (Stock $record, array $data): void {
                        $priceType = $data['price_type'] ?? null;
                        if ($priceType === 'custom') {
                            $priceValue = (float) ($data['custom_price'] ?? 0);
                        } elseif ($priceType === 'default') {
                            $priceValue = (float) $record->product->default_price;
                        } elseif ($priceType && str_starts_with($priceType, 'pp_')) {
                            $priceId = (int) str_replace('pp_', '', $priceType);
                            $priceModel = ProductPrice::find($priceId);
                            $priceValue = $priceModel ? (float) $priceModel->price : null;
                        } else {
                            $priceValue = null;
                        }

                        $record->update(['price' => $priceValue]);

                        app(AdjustStock::class)->execute(
                            $record,
                            (int) $data['qty'],
                            $data['notes'] ?? null,
                        );

                        $this->notifySuccess(
                            'Stok Disesuaikan',
                            'Jumlah untuk '.Product::find($record->product_id)?->name.' telah disetel menjadi '.$data['qty'].' unit.'
                        );
                    }),
            ]);
    }
}
