<?php

namespace App\Filament\Staff\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Pos extends Page implements HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use NotifiesWithDetail;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Kasir';

    protected static \UnitEnum|string|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Kasir';

    protected string $view = 'filament.staff.pages.pos';

    public array $items = [];

    public function table(Table $table): Table
    {
        $locationId = auth()->user()->location_id;

        return $table
            ->query(
                Product::query()
                    ->whereHas('stocks', fn ($q) => $q
                        ->where('location_id', $locationId)
                        ->where('qty', '>', 0),
                    )
                    ->with(['stocks' => fn ($q) => $q->where('location_id', $locationId)])
            )
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->weight('bold')
                        ->formatStateUsing(fn (string $state): string => mb_strimwidth($state, 0, 40, '...')),
                    TextColumn::make('price_1')
                        ->color('primary')
                        ->getStateUsing(fn (Product $record): float => $record->stocks->first()?->price > 0 ? (float) $record->stocks->first()?->price : (float) $record->price_1)
                        ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                    TextColumn::make('stock_qty')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state <= 0 => 'danger',
                            $state < 10 => 'warning',
                            default => 'success',
                        })
                        ->getStateUsing(fn (Product $record): int => $record->stocks->first()?->qty ?? 0)
                        ->formatStateUsing(fn (int $state): string => 'Stok '.$state),
                    ViewColumn::make('controls')
                        ->label('Kontrol')
                        ->view('filament.tables.columns.pos-product-inline-controls')
                        ->extraAttributes(['class' => 'w-full flex justify-end mt-4'])
                        ->state(fn () => [
                            'showPrice' => false,
                        ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('name');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function getCartItems(): array
    {
        $productIds = collect($this->items)->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $locationId = auth()->user()->location_id;

        $stocks = Stock::whereIn('product_id', $productIds)
            ->where('location_id', $locationId)
            ->get()
            ->keyBy('product_id');

        return collect($this->items)->map(function ($item) use ($products, $stocks) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                return null;
            }
            $item['name'] = $product->name;
            $stock = $stocks->get($item['product_id']);
            $item['price'] = $stock && $stock->price > 0 ? (float) $stock->price : (float) $product->price_1;
            $item['subtotal'] = $item['price'] * (int) $item['qty'];

            return $item;
        })->filter()->values()->toArray();
    }

    public function getCartTotal(): float
    {
        return (float) collect($this->getCartItems())->sum('subtotal');
    }

    public function incrementItem(string $productId): void
    {
        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);
        if ($existingIndex !== false) {
            $this->updateItemQty($productId, $this->items[$existingIndex]['qty'] + 1);
        } else {
            $this->updateItemQty($productId, 1);
        }
    }

    public function decrementItem(string $productId): void
    {
        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);
        if ($existingIndex !== false) {
            $this->updateItemQty($productId, $this->items[$existingIndex]['qty'] - 1);
        }
    }

    public function updateItemQty(string $productId, $qty): void
    {
        $qty = max(0, (int) $qty);
        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);

        $locationId = auth()->user()->location_id;
        $stock = Stock::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();
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
                'product_id' => $productId,
                'qty' => $qty,
            ];
        }
    }

    public function removeItem(string $productId): void
    {
        $this->items = collect($this->items)
            ->reject(fn ($item) => $item['product_id'] === $productId)
            ->values()
            ->toArray();
    }

    public function clearCart(): void
    {
        $this->items = [];
    }

    protected function isStockAvailable(string $productId, int $qty): bool
    {
        $locationId = auth()->user()->location_id;
        $stock = Stock::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        return $stock !== null && $stock->qty >= $qty;
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

    public function getPaymentHint(float $paidAmount): ?string
    {
        $total = $this->getCartTotal();

        if ($paidAmount < $total) {
            return 'Kurang: Rp '.number_format($total - $paidAmount, 0, ',', '.');
        }

        if ($paidAmount > $total) {
            return 'Kembali: Rp '.number_format($paidAmount - $total, 0, ',', '.');
        }

        return null;
    }

    public function getPaymentHintColor(float $paidAmount): ?string
    {
        $total = $this->getCartTotal();

        if ($paidAmount < $total) {
            return 'danger';
        }

        if ($paidAmount > $total) {
            return 'success';
        }

        return null;
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

                foreach ($this->items as $cartItem) {
                    $product = Product::findOrFail($cartItem['product_id']);
                    $qty = (int) $cartItem['qty'];

                    $stock = Stock::where('product_id', $product->id)
                        ->where('location_id', $locationId)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->qty < $qty) {
                        throw new DomainException(
                            "Stok tidak cukup untuk produk \"{$product->name}\"."
                        );
                    }

                    $price = $stock && $stock->price > 0 ? (float) $stock->price : (float) $product->price_1;
                    $subtotal = $price * $qty;
                    $total += $subtotal;

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'qty' => $qty,
                        'price' => $price,
                        'subtotal' => $subtotal,
                        'stock' => $stock,
                    ];
                }

                $sale = Sale::create([
                    'user_id' => auth()->id(),
                    'location_id' => $locationId,
                    'buyer_id' => $data['buyer_id'] ?? null,
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
                        'location_id' => $locationId,
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
}
