<?php

namespace App\Filament\Admin\Resources\Locations\RelationManagers;

use App\Actions\AdjustStock;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
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
                    ->label('Harga Outlet')
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->placeholder('Harga 1')
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
                            ->label('Harga Outlet')
                            ->options(function (Stock $record): array {
                                $product = $record->product;
                                $options = [];

                                if ((float) $product->price_1 > 0) {
                                    $options['price_1'] = 'Harga 1: Rp '.number_format((float) $product->price_1, 0, ',', '.');
                                }
                                if ((float) $product->price_2 > 0) {
                                    $options['price_2'] = 'Harga 2: Rp '.number_format((float) $product->price_2, 0, ',', '.');
                                }
                                if ((float) $product->reseller_price > 0) {
                                    $options['reseller'] = 'Harga Reseller: Rp '.number_format((float) $product->reseller_price, 0, ',', '.');
                                }

                                return $options;
                            })
                            ->default(function (Stock $record): ?string {
                                if ($record->price === null) {
                                    return null;
                                }
                                $product = $record->product;
                                if ((float) $record->price == (float) $product->price_1 && (float) $product->price_1 > 0) {
                                    return 'price_1';
                                } elseif ((float) $record->price == (float) $product->price_2 && (float) $product->price_2 > 0) {
                                    return 'price_2';
                                } elseif ((float) $record->price == (float) $product->reseller_price && (float) $product->reseller_price > 0) {
                                    return 'reseller';
                                }

                                return null;
                            })
                            ->nullable(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->maxLength(1000),
                    ])
                    ->action(function (Stock $record, array $data): void {
                        $priceType = $data['price_type'] ?? null;
                        if ($priceType === 'custom') {
                            $priceValue = (float) ($data['custom_price'] ?? 0);
                        } elseif ($priceType === 'reseller') {
                            $priceValue = (float) $record->product->reseller_price;
                        } elseif ($priceType === 'price_1') {
                            $priceValue = (float) $record->product->price_1;
                        } elseif ($priceType === 'price_2') {
                            $priceValue = (float) $record->product->price_2;
                        } else {
                            $priceValue = null;
                        }

                        $record->update(['price' => $priceValue]);

                        app(AdjustStock::class)->execute(
                            $record,
                            (int) $data['qty'],
                            $data['notes'] ?? null,
                        );

                        NotifiesWithDetail::buildSuccessNotification(
                            'Stok Disesuaikan',
                            'Jumlah untuk '.Product::find($record->product_id)?->name.' telah disetel menjadi '.$data['qty'].' unit.'
                        )->send();
                    })
                    ->successNotification(null),
            ]);
    }
}
