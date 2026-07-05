<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Pages\Pos as StaffPos;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Pos extends StaffPos
{
    use NotifiesWithDetail;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Kasir';

    protected static \UnitEnum|string|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Kasir';

    protected string $view = 'filament.admin.pages.pos';

    public function table(Table $table): Table
    {
        $locationId = auth()->user()->location_id;

        $query = Stock::query()
            ->select('stocks.*')
            ->join('locations', 'locations.id', '=', 'stocks.location_id')
            ->where('qty', '>', 0)
            ->with(['product', 'location']);

        if ($locationId) {
            $query->where('stocks.location_id', $locationId);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->formatStateUsing(fn (Stock $record): string => mb_strimwidth($record->product->name, 0, 30, '...'))
                    ->description(fn (Stock $record): string => $record->price > 0
                        ? 'Harga Outlet: Rp '.number_format((float) $record->price, 0, ',', '.').' (Default: Rp '.number_format((float) $record->product->default_price, 0, ',', '.').')'
                        : 'Rp '.number_format((float) $record->product->default_price, 0, ',', '.'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('qty')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->options(fn () => Location::pluck('name', 'id'))
                    ->visible(! $locationId),
            ])
            ->actions([
                $this->addToCartAction(),
            ])
            ->defaultSort('locations.name');
    }

    /**
     * Add to cart action with modal for price selection (admin only).
     */
    protected function addToCartAction(): Action
    {
        return Action::make('addToCart')
            ->label('Tambah')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->form([
                Select::make('selected_price')
                    ->label('Harga')
                    ->options(function (Stock $record): array {
                        $product = $record->product;
                        $options = [];

                        if ((float) $record->price > 0) {
                            $options['stock'] = 'Harga Outlet: Rp '.number_format((float) $record->price, 0, ',', '.');
                        }

                        if ((float) $product->selling_price > 0) {
                            $options['default'] = 'Default: Rp '.number_format((float) $product->selling_price, 0, ',', '.');
                        }

                        foreach ($product->prices as $price) {
                            $options['pp_'.$price->id] = "{$price->label}: Rp ".number_format((float) $price->price, 0, ',', '.');
                        }

                        if (empty($options)) {
                            $options['default'] = 'Rp 0';
                        }

                        return $options;
                    })
                    ->default(fn (Stock $record): string => $record->price > 0 ? 'stock' : 'default')
                    ->required(),
            ])
            ->action(function (array $data, Stock $record): void {
                $product = $record->product;
                $selected = $data['selected_price'];

                if ($selected === 'default') {
                    $price = (float) $product->default_price;
                    $label = 'Default';
                } elseif ($selected === 'stock') {
                    $price = (float) $record->price;
                    $label = 'Harga Outlet';
                } else {
                    $priceId = (int) str_replace('pp_', '', $selected);
                    $priceModel = $product->prices()->find($priceId);
                    $price = (float) ($priceModel->price ?? 0);
                    $label = $priceModel->label ?? 'Tidak Diketahui';
                }

                $this->addToCart($product->id, $price, $label);
            });
    }

    public function addToCart(int $productId, ?float $price = null, ?string $priceLabel = null): void
    {
        $existingIndex = collect($this->cart)->search(fn ($item) => $item['product_id'] === $productId);

        if ($existingIndex !== false) {
            $currentQty = (int) $this->cart[$existingIndex]['qty'] + 1;
            if (! $this->isStockAvailable($productId, $currentQty)) {
                $this->notifyWarning('Stok Tidak Mencukupi');

                return;
            }
            $this->cart[$existingIndex]['qty'] = $currentQty;
        } else {
            if (! $this->isStockAvailable($productId, 1)) {
                $this->notifyWarning('Stok Habis');

                return;
            }
            $this->cart[] = [
                'product_id' => $productId,
                'qty' => 1,
                'price' => $price ?? 0,
                'price_label' => $priceLabel ?? 'Default',
            ];
        }
    }

    public function getCartItems(): array
    {
        $productIds = collect($this->cart)->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return collect($this->cart)->map(function ($item) use ($products) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                return null;
            }
            $item['name'] = $product->name;
            $item['subtotal'] = $item['price'] * (int) $item['qty'];

            return $item;
        })->filter()->values()->toArray();
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
                                "Stok tidak cukup untuk produk \"{$product->name}\"."
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
                                "Stok tidak cukup untuk produk \"{$product->name}\"."
                            );
                        }

                        $price = (float) $cartItem['price'];
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

                    $price = (float) $cartItem['price'];
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
                        $sl['stock']->qty -= $sl['take'];
                        $sl['stock']->save();

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
            $this->notifyError('Penjualan Gagal', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->notifyError(
                'Terjadi Kesalahan',
                'Silakan coba lagi atau hubungi administrator.'
            );

            return;
        }

        $this->cart = [];
        $this->dispatch('$refresh');

        $printUrl = route('admin.sales.receipt', $sale);

        static::buildSuccessNotification(
            'Penjualan Selesai',
            "Invoice \"{$sale->invoice_number}\" berhasil dibuat. Total: Rp ".number_format($sale->total, 0, ',', '.')
        )
            ->actions([
                Action::make('print')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->url($printUrl, shouldOpenInNewTab: true),
            ])
            ->send();
    }
}
