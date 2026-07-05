<?php

namespace App\Filament\Admin\Resources\StockOpnames\Schemas;

use App\Models\Product;
use App\Models\Stock;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StockOpnameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
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
                                            ->relationship('location', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, $state): void {
                                                if (! $state) {
                                                    $set('items', []);

                                                    return;
                                                }

                                                $products = Product::all();
                                                $items = [];
                                                foreach ($products as $product) {
                                                    $stock = Stock::where('product_id', $product->id)
                                                        ->where('location_id', $state)
                                                        ->first();
                                                    $systemQty = $stock ? $stock->qty : 0;
                                                    $items[] = [
                                                        'product_id' => $product->id,
                                                        'product_name' => $product->name,
                                                        'system_qty' => $systemQty,
                                                        'actual_qty' => $systemQty,
                                                        'difference' => 0,
                                                        'notes' => null,
                                                    ];
                                                }
                                                $set('items', $items);
                                            }),
                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(3),
                                    ]),
                            ]),
                        Step::make('Input Stok Fisik')
                            ->description('Masukkan stok fisik untuk setiap produk')
                            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->hiddenLabel()
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->defaultItems(0)
                                    ->table([
                                        TableColumn::make('Produk'),
                                        TableColumn::make('Stok Sistem'),
                                        TableColumn::make('Stok Fisik'),
                                        TableColumn::make('Selisih'),
                                        TableColumn::make('Catatan'),
                                    ])
                                    ->schema([
                                        TextInput::make('product_name')
                                            ->label('Produk')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Hidden::make('product_id'),
                                        TextInput::make('system_qty')
                                            ->label('Stok Sistem')
                                            ->integer()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0),
                                        TextInput::make('actual_qty')
                                            ->label('Stok Fisik')
                                            ->integer()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(function (callable $get, callable $set, ?string $state): void {
                                                $set('difference', self::calcDiff($get('system_qty'), $state ?? '0'));
                                            }),
                                        TextInput::make('difference')
                                            ->label('Selisih')
                                            ->integer()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0),
                                        TextInput::make('notes')
                                            ->label('Catatan'),
                                    ])
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $data['difference'] = (int) ($data['actual_qty'] ?? 0) - (int) ($data['system_qty'] ?? 0);

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        $data['difference'] = (int) ($data['actual_qty'] ?? 0) - (int) ($data['system_qty'] ?? 0);

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                        $data['product_name'] = Product::find($data['product_id'])?->name ?? '-';

                                        return $data;
                                    }),
                            ]),
                    ])
                    ->nextAction(fn ($action) => $action
                        ->label('Lanjut'))
                    ->previousAction(fn ($action) => $action
                        ->label('Kembali')),
            ]);
    }

    protected static function calcDiff(?string $systemQty, string $actualQty): int
    {
        return (int) $actualQty - (int) ($systemQty ?? 0);
    }
}
