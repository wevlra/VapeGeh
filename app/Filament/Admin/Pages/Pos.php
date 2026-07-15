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
use Filament\Tables\Columns\Layout\Stack;
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
                Stack::make([
                    TextColumn::make('product.name')
                        ->searchable()
                        ->sortable()
                        ->label('Produk')
                        ->weight('bold')
                        ->formatStateUsing(fn (Stock $record): string => mb_strimwidth($record->product->name, 0, 40, '...')),
                    TextColumn::make('price_display')
                        ->state(fn (Stock $record) => $record)
                        ->formatStateUsing(function (Stock $record): string {
                            if ($record->price > 0) {
                                return 'Rp '.number_format((float) $record->price, 0, ',', '.').' (Outlet)';
                            }

                            return 'Rp '.number_format((float) $record->product->selling_price, 0, ',', '.');
                        })
                        ->color('primary'),
                    TextColumn::make('qty')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state <= 0 => 'danger',
                            $state < 10 => 'warning',
                            default => 'success',
                        })
                        ->formatStateUsing(fn (int $state, Stock $record): string => 'Stok: '.$state.' • '.$record->location->name),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
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
            ->button()
            ->extraAttributes(['class' => 'w-full justify-center'])
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

                        if ((float) $product->reseller_price > 0) {
                            $options['reseller'] = 'Reseller: Rp '.number_format((float) $product->reseller_price, 0, ',', '.');
                        }
                        if ((float) $product->price_1 > 0) {
                            $options['price_1'] = 'Harga 1: Rp '.number_format((float) $product->price_1, 0, ',', '.');
                        }
                        if ((float) $product->price_2 > 0) {
                            $options['price_2'] = 'Harga 2: Rp '.number_format((float) $product->price_2, 0, ',', '.');
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
                    $price = (float) $product->selling_price;
                    $label = 'Default';
                } elseif ($selected === 'stock') {
                    $price = (float) $record->price;
                    $label = 'Harga Outlet';
                } elseif ($selected === 'reseller') {
                    $price = (float) $product->reseller_price;
                    $label = 'Reseller';
                } elseif ($selected === 'price_1') {
                    $price = (float) $product->price_1;
                    $label = 'Harga 1';
                } elseif ($selected === 'price_2') {
                    $price = (float) $product->price_2;
                    $label = 'Harga 2';
                } else {
                    $price = 0;
                    $label = 'Tidak Diketahui';
                }

                $locationId = $record->location_id;

                $existingIndex = collect($this->cart)->search(
                    fn ($item) => $item['product_id'] === $product->id && ($item['location_id'] ?? null) === $locationId
                );

                if ($existingIndex !== false) {
                    $currentQty = (int) $this->cart[$existingIndex]['qty'] + 1;
                    $stock = Stock::where('product_id', $product->id)
                        ->where('location_id', $locationId)
                        ->first();

                    if (! $stock || $stock->qty < $currentQty) {
                        $this->notifyWarning('Stok Tidak Mencukupi');

                        return;
                    }
                    $this->cart[$existingIndex]['qty'] = $currentQty;
                    $this->cart[$existingIndex]['location_id'] = $locationId;
                } else {
                    $stock = Stock::where('product_id', $product->id)
                        ->where('location_id', $locationId)
                        ->first();

                    if (! $stock || $stock->qty < 1) {
                        $this->notifyWarning('Stok Habis');

                        return;
                    }
                    $this->cart[] = [
                        'product_id' => $product->id,
                        'location_id' => $locationId,
                        'qty' => 1,
                        'price' => $price,
                        'price_label' => $label,
                    ];
                }
            });
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

    public function updateQty(int $index, int $qty): void
    {
        $qty = max(0, (int) $qty);

        if ($qty < 1) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);

            return;
        }

        if (! isset($this->cart[$index])) {
            return;
        }

        $item = $this->cart[$index];
        $stock = Stock::where('product_id', $item['product_id'])
            ->where('location_id', $item['location_id'])
            ->first();

        if (! $stock || $stock->qty < $qty) {
            $this->notifyWarning('Stok Tidak Mencukupi');

            return;
        }

        $this->cart[$index]['qty'] = $qty;
    }

    public function processSale(array $data): void
    {
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $paymentMethod = $data['payment_method'] ?? 'cash';

        try {
            $sale = DB::transaction(function () use ($paidAmount, $paymentMethod, $data) {
                $total = 0;
                $saleItems = [];

                foreach ($this->cart as $cartItem) {
                    $product = Product::findOrFail($cartItem['product_id']);
                    $qty = (int) $cartItem['qty'];
                    $locationId = $cartItem['location_id'];

                    $stock = Stock::where('product_id', $product->id)
                        ->where('location_id', $locationId)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->qty < $qty) {
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
                        'stock' => $stock,
                    ];
                }

                $sale = Sale::create([
                    'user_id' => auth()->id(),
                    'location_id' => $this->cart[0]['location_id'],
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

                    $stock = $saleItem['stock'];
                    $stock->qty -= $saleItem['qty'];
                    $stock->save();

                    StockMovement::create([
                        'product_id' => $saleItem['product_id'],
                        'location_id' => $stock->location_id,
                        'type' => 'out',
                        'quantity' => -$saleItem['qty'],
                        'related_type' => Sale::class,
                        'related_id' => $sale->id,
                        'notes' => "Sale {$sale->invoice_number}",
                        'created_by' => auth()->id(),
                    ]);
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
                    ->url($printUrl, shouldOpenInNewTab: true)
                    ->extraAttributes(function () use ($sale) {
                        return [
                            'x-on:click.prevent' => "
                                window.dispatchEvent(new CustomEvent('open-receipt-action-modal', {
                                    detail: {
                                        jsonUrl: '".route('admin.sales.receipt', ['sale' => $sale->id, 'format' => 'json'])."',
                                        receiptUrl: '".route('admin.sales.receipt', $sale)."',
                                    }
                                }));
                            ",
                        ];
                    }),
            ])
            ->send();
    }
}
