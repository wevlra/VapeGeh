<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Staff\Pages\Pos as StaffPos;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Pos extends StaffPos
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Cashier';

    protected static \UnitEnum|string|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Cashier';

    protected string $view = 'filament.admin.pages.pos';

    public function table(Table $table): Table
    {
        $locationId = auth()->user()->location_id;

        $query = Stock::query()
            ->where('qty', '>', 0)
            ->with('product.prices');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('product.prices.first.price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) ($state ?? 0), 0, ',', '.'))
                    ->sortable(false),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::pluck('name', 'id'))
                    ->visible(! $locationId),
            ])
            ->actions([
                Action::make('addToCart')
                    ->label('Add')
                    ->icon('heroicon-m-plus')
                    ->color('primary')
                    ->action(fn (Stock $record) => $this->addToCart($record->product_id)),
            ])
            ->defaultSort('product.name');
    }

    protected function isStockAvailable(int $productId, int $qty): bool
    {
        $locationId = auth()->user()->location_id;

        if ($locationId) {
            $stock = Stock::where('product_id', $productId)
                ->where('location_id', $locationId)
                ->first();

            return $stock !== null && $stock->qty >= $qty;
        }

        return Stock::where('product_id', $productId)
            ->sum('qty') >= $qty;
    }

    public function processSale(array $data): void
    {
        $locationId = auth()->user()->location_id;
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $paymentMethod = $data['payment_method'] ?? 'cash';

        try {
            $sale = DB::transaction(function () use ($locationId, $paidAmount, $paymentMethod, $data) {
                $total = 0;
                $saleItems = [];

                foreach ($this->cart as $cartItem) {
                    $product = Product::findOrFail($cartItem['product_id']);
                    $qty = (int) $cartItem['qty'];

                    if ($locationId) {
                        $stock = Stock::where('product_id', $product->id)
                            ->where('location_id', $locationId)
                            ->lockForUpdate()
                            ->first();

                        if (! $stock || $stock->qty < $qty) {
                            throw new DomainException(
                                "Insufficient stock for product \"{$product->name}\"."
                            );
                        }

                        $stockLocationId = $locationId;
                    } else {
                        $stocks = Stock::where('product_id', $product->id)
                            ->where('qty', '>', 0)
                            ->lockForUpdate()
                            ->get();

                        $remaining = $qty;
                        $stockLocations = [];

                        foreach ($stocks as $s) {
                            $take = min($s->qty, $remaining);
                            $stockLocations[] = ['stock' => $s, 'take' => $take];
                            $remaining -= $take;
                            if ($remaining <= 0) {
                                break;
                            }
                        }

                        if ($remaining > 0) {
                            throw new DomainException(
                                "Insufficient stock for product \"{$product->name}\"."
                            );
                        }

                        $price = (float) ($product->prices->first()?->price ?? 0);
                        $subtotal = $price * $qty;
                        $total += $subtotal;

                        $saleItems[] = [
                            'product_id' => $product->id,
                            'qty' => $qty,
                            'price' => $price,
                            'subtotal' => $subtotal,
                            'stock_locations' => $stockLocations,
                        ];

                        continue;
                    }

                    $price = (float) ($product->prices->first()?->price ?? 0);
                    $subtotal = $price * $qty;
                    $total += $subtotal;

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'price' => $price,
                        'subtotal' => $subtotal,
                        'stock_locations' => [['stock' => $stock, 'take' => $qty]],
                    ];
                }

                $sale = Sale::create([
                    'user_id' => auth()->id(),
                    'location_id' => $locationId ?? $saleItems[0]['stock_locations'][0]['stock']->location_id ?? 1,
                    'total' => $total,
                    'paid_amount' => $paidAmount,
                    'payment_method' => $paymentMethod,
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($saleItems as $saleItem) {
                    $sale->items()->create([
                        'product_id' => $saleItem['product_id'],
                        'qty' => $saleItem['qty'],
                        'price' => $saleItem['price'],
                        'subtotal' => $saleItem['subtotal'],
                    ]);

                    foreach ($saleItem['stock_locations'] as $sl) {
                        Stock::where('id', $sl['stock']->id)
                            ->decrement('qty', $sl['take']);

                        StockMovement::create([
                            'product_id' => $saleItem['product_id'],
                            'location_id' => $sl['stock']->location_id,
                            'type' => 'out',
                            'quantity' => -$sl['take'],
                            'related_type' => Sale::class,
                            'related_id' => $sale->id,
                            'notes' => "Sale {$sale->invoice_number}",
                            'created_by' => auth()->id(),
                        ]);
                    }
                }

                return $sale;
            });
        } catch (DomainException $e) {
            Notification::make()
                ->title('Sale failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->cart = [];

        $printUrl = route('admin.sales.receipt', $sale);

        Notification::make()
            ->title('Sale completed')
            ->body("Invoice \"{$sale->invoice_number}\" has been created successfully. Total: Rp ".number_format($sale->total, 0, ',', '.'))
            ->success()
            ->actions([
                Action::make('print')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->url($printUrl, shouldOpenInNewTab: true),
            ])
            ->send();
    }
}
