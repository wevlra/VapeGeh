<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Pages\Pos as StaffPos;
use App\Models\Buyer;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
                        ->formatStateUsing(function (Stock $record, $livewire): string {
                            $priceType = $livewire->tableFilters['price_type']['value'] ?? 'price_1';

                            if ($priceType === 'reseller') {
                                return 'Rp '.number_format((float) $record->product->reseller_price, 0, ',', '.').' (Reseller)';
                            } elseif ($priceType === 'price_1') {
                                return 'Rp '.number_format((float) $record->product->price_1, 0, ',', '.').' (Harga 1)';
                            } elseif ($priceType === 'price_2') {
                                return 'Rp '.number_format((float) $record->product->price_2, 0, ',', '.').' (Harga 2)';
                            }

                            return 'Rp '.number_format((float) $record->product->price_1, 0, ',', '.');
                        })
                        ->color('primary'),
                    TextColumn::make('qty')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state <= 0 => 'danger',
                            $state < 10 => 'warning',
                            default => 'success',
                        })
                        ->formatStateUsing(fn (int $state, Stock $record): string => 'Stok '.$state.' | '.$record->location->name),
                    ViewColumn::make('controls')
                        ->label('Kontrol')
                        ->view('filament.tables.columns.pos-product-inline-controls')
                        ->extraAttributes(['class' => 'w-full flex justify-end mt-4'])
                        ->state(fn () => [
                            'showPrice' => false,
                            'itemKeyField' => 'stock_id',
                        ]),
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
                SelectFilter::make('price_type')
                    ->label('Tipe Harga')
                    ->options([
                        'price_1' => 'Harga 1',
                        'price_2' => 'Harga 2',
                        'reseller' => 'Harga Reseller',
                    ])
                    ->default('price_1')
                    ->selectablePlaceholder(false)
                    ->query(fn ($query) => $query), // don't filter the actual query
            ])
            ->actions([])
            ->defaultSort('locations.name');
    }

    public function getCartItems(): array
    {
        $productIds = collect($this->items)->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $priceType = $this->tableFilters['price_type']['value'] ?? 'price_1';

        return collect($this->items)->map(function ($item) use ($products, $priceType) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                return null;
            }
            $item['name'] = $product->name;

            if ($priceType === 'reseller') {
                $item['price'] = (float) $product->reseller_price;
                $item['price_label'] = 'Reseller';
            } elseif ($priceType === 'price_1') {
                $item['price'] = (float) $product->price_1;
                $item['price_label'] = 'Harga 1';
            } elseif ($priceType === 'price_2') {
                $item['price'] = (float) $product->price_2;
                $item['price_label'] = 'Harga 2';
            } else {
                $item['price'] = (float) $product->price_1;
                $item['price_label'] = 'Harga 1';
            }

            $item['subtotal'] = $item['price'] * (int) $item['qty'];

            return $item;
        })->filter()->values()->toArray();
    }

    public function incrementItem(string $stockId): void
    {
        $existingIndex = collect($this->items)->search(fn ($item) => ($item['stock_id'] ?? null) === $stockId);
        if ($existingIndex !== false) {
            $this->updateItemQty($stockId, $this->items[$existingIndex]['qty'] + 1);
        } else {
            $this->updateItemQty($stockId, 1);
        }
    }

    public function decrementItem(string $stockId): void
    {
        $existingIndex = collect($this->items)->search(fn ($item) => ($item['stock_id'] ?? null) === $stockId);
        if ($existingIndex !== false) {
            $this->updateItemQty($stockId, $this->items[$existingIndex]['qty'] - 1);
        }
    }

    public function updateItemQty(string $stockId, $qty): void
    {
        $qty = max(0, (int) $qty);
        $existingIndex = collect($this->items)->search(fn ($item) => ($item['stock_id'] ?? null) === $stockId);

        $stock = Stock::with('product')->find($stockId);
        $available = $stock ? $stock->qty : 0;

        if ($qty > $available) {
            $qty = $available;
            $this->notifyWarning("Stok tidak mencukupi. Kuantitas disesuaikan ke stok maksimal ({$qty})");
        }

        if ($qty < 1) {
            if ($existingIndex !== false) {
                unset($this->items[$existingIndex]);
                $this->items = array_values($this->items);
            }

            return;
        }

        if ($existingIndex !== false) {
            $this->items[$existingIndex]['qty'] = $qty;
        } else {
            $this->items[] = [
                'stock_id' => $stockId,
                'product_id' => $stock->product_id,
                'location_id' => $stock->location_id,
                'qty' => $qty,
            ];
        }
    }

    public function updateQty(int $index, int $qty): void
    {
        $qty = max(0, (int) $qty);

        if ($qty < 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);

            return;
        }

        if (! isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $stock = Stock::where('product_id', $item['product_id'])
            ->where('location_id', $item['location_id'])
            ->first();
        $available = $stock ? $stock->qty : 0;

        if ($qty > $available) {
            $qty = $available;
            $this->notifyWarning("Stok tidak mencukupi. Kuantitas disesuaikan ke stok maksimal ({$qty})");
        }

        if ($qty < 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);

            return;
        }

        $this->items[$index]['qty'] = $qty;
    }

    public function removeCartItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function processSale(array $data): void
    {
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $priceType = $this->tableFilters['price_type']['value'] ?? 'price_1';

        try {
            $sale = DB::transaction(function () use ($paidAmount, $paymentMethod, $data, $priceType) {
                $total = 0;
                $saleItems = [];

                foreach ($this->items as $cartItem) {
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

                    if ($priceType === 'reseller') {
                        $price = (float) $product->reseller_price;
                    } elseif ($priceType === 'price_1') {
                        $price = (float) $product->price_1;
                    } elseif ($priceType === 'price_2') {
                        $price = (float) $product->price_2;
                    } else {
                        $price = (float) $product->price_1;
                    }

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
                    'location_id' => $this->items[0]['location_id'],
                    'buyer_id' => $data['buyer_id'] ?? null,
                    'total' => $total,
                    'paid_amount' => $paidAmount,
                    'payment_method' => $paymentMethod,
                    'notes' => $data['notes'] ?? null,
                ]);

                $buyer = Buyer::find($data['buyer_id'] ?? null);
                $buyerName = $buyer ? " - {$buyer->name}" : '';
                Income::create([
                    'location_id' => $this->items[0]['location_id'],
                    'category' => 'sale',
                    'description' => "Penjualan Kasir {$sale->invoice_number}{$buyerName}",
                    'amount' => $total,
                    'date' => now(),
                    'created_by' => auth()->id(),
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
                        'buyer_id' => $data['buyer_id'] ?? null,
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

        $this->items = [];
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

    public function checkoutAction(): Action
    {
        return Action::make('checkout')
            ->label('Bayar')
            ->icon('heroicon-m-credit-card')
            ->modalHeading('Bayar')
            ->modalSubmitActionLabel('Proses Pembayaran')
            ->modalContent(view('filament.staff.pages.checkout-summary'))
            ->form([
                Select::make('buyer_id')
                    ->label('Pembeli (opsional)')
                    ->options(fn () => Buyer::pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        return Buyer::create($data)->getKey();
                    }),
                Radio::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ])
                    ->inline()
                    ->default('cash')
                    ->live(),
                TextInput::make('paid_amount')
                    ->label('Bayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.')
                    ->default(fn (): float => $this->getCartTotal())
                    ->visible(fn (callable $get): bool => $get('payment_method') === 'cash')
                    ->rules(fn (callable $get): array => $get('payment_method') === 'cash'
                        ? ['numeric', 'min:'.$this->getCartTotal()]
                        : ['numeric'])
                    ->live()
                    ->hint(fn (callable $get): ?string => $this->getPaymentHint((float) ($get('paid_amount') ?? 0)))
                    ->hintColor(fn (callable $get): ?string => $this->getPaymentHintColor((float) ($get('paid_amount') ?? 0))),
                Textarea::make('notes')
                    ->label('Catatan (opsional)')
                    ->placeholder('Catatan untuk transaksi ini...')
                    ->maxLength(1000),
            ])
            ->action(function (array $data) {
                $this->processSale($data);
            })
            ->hidden(fn (): bool => empty($this->items));
    }
}
