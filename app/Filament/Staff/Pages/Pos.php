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
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
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

    public array $cart = [];

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
                TextColumn::make('name')
                    ->label('Produk')
                    ->formatStateUsing(fn (Product $record): string => mb_strimwidth($record->name, 0, 30, '...'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('selling_price')
                    ->label('Harga')
                    ->getStateUsing(fn (Product $record): float => $record->stocks->first()?->price > 0 ? (float) $record->stocks->first()?->price : (float) $record->selling_price)
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->visibleFrom('md'),
                TextColumn::make('stock_qty')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->getStateUsing(fn (Product $record): int => $record->stocks->first()?->qty ?? 0)
                    ->visibleFrom('md'),
                Split::make([
                    Stack::make([
                        TextColumn::make('name_mobile')
                            ->state(fn (Product $record): string => $record->name)
                            ->label('Produk')
                            ->formatStateUsing(fn (string $state): string => mb_strimwidth($state, 0, 30, '...'))
                            ->weight('bold'),
                        TextColumn::make('selling_price_mobile')
                            ->state(fn (Product $record): float => $record->stocks->first()?->price > 0 ? (float) $record->stocks->first()?->price : (float) $record->selling_price)
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                            ->color('gray'),
                    ]),
                    Stack::make([
                        TextColumn::make('stock_qty_mobile')
                            ->label('Stok')
                            ->badge()
                            ->alignEnd()
                            ->color(fn (int $state): string => match (true) {
                                $state <= 0 => 'danger',
                                $state < 10 => 'warning',
                                default => 'success',
                            })
                            ->getStateUsing(fn (Product $record): int => $record->stocks->first()?->qty ?? 0),
                    ])->space(1),
                ])->hiddenFrom('md'),
            ])
            ->actions([
                Action::make('addToCart')
                    ->label('Tambah')
                    ->icon('heroicon-m-plus')
                    ->color('primary')
                    ->action(fn (Product $record) => $this->addToCart($record->id)),
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
        $productIds = collect($this->cart)->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $locationId = auth()->user()->location_id;

        $stocks = Stock::whereIn('product_id', $productIds)
            ->where('location_id', $locationId)
            ->get()
            ->keyBy('product_id');

        return collect($this->cart)->map(function ($item) use ($products, $stocks) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                return null;
            }
            $item['name'] = $product->name;
            $stock = $stocks->get($item['product_id']);
            $item['price'] = $stock && $stock->price > 0 ? (float) $stock->price : (float) $product->selling_price;
            $item['subtotal'] = $item['price'] * (int) $item['qty'];

            return $item;
        })->filter()->values()->toArray();
    }

    public function getCartTotal(): float
    {
        return (float) collect($this->getCartItems())->sum('subtotal');
    }

    public function addToCart(int $productId): void
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
            ];
        }
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

        if (! $this->isStockAvailable($this->cart[$index]['product_id'], $qty)) {
            $this->notifyWarning('Stok Tidak Mencukupi');

            return;
        }

        $this->cart[$index]['qty'] = $qty;
    }

    public function removeCartItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    protected function isStockAvailable(int $productId, int $qty): bool
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
            ->hidden(fn (): bool => empty($this->cart));
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

                foreach ($this->cart as $cartItem) {
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

                    $price = $stock && $stock->price > 0 ? (float) $stock->price : (float) $product->selling_price;
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
                                const url = '".route('admin.sales.receipt', ['sale' => $sale->id, 'format' => 'json'])."';
                                
                                const savedPrinter = localStorage.getItem('savedPrinter');
                                if (savedPrinter) {
                                    window.dispatchEvent(new CustomEvent('print-native', { detail: { url } }));
                                } else {
                                    window.dispatchEvent(new CustomEvent('open-printer-modal', { detail: { url } }));
                                }
                            ",
                        ];
                    }),
            ])
            ->send();
    }
}
