<?php

namespace App\Filament\Staff\Pages;

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
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Pos extends Page implements HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

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
                    ->with(['stocks' => fn ($q) => $q->where('location_id', $locationId), 'prices'])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Produk')
                    ->formatStateUsing(fn (Product $record): string => mb_strimwidth($record->name, 0, 30, '...'))
                    ->description(fn (Product $record): string => 'Rp '.number_format((float) $record->default_price, 0, ',', '.'))
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('sku')
                //     ->label('SKU')
                //     ->searchable(),
                TextColumn::make('stock_qty')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->getStateUsing(fn (Product $record): int => $record->stocks->first()?->qty ?? 0),
                // TextColumn::make('id')
                //     ->label('Harga')
                //     ->formatStateUsing(fn (Product $record): string => 'Rp '.number_format((float) $record->default_price, 0, ',', '.'))
                //     ->sortable(false),
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

        return collect($this->cart)->map(function ($item) use ($products) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                return null;
            }
            $item['name'] = $product->name;
            $item['price'] = (float) $product->default_price;
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
                Notification::make()->warning()->title('Stok tidak mencukupi.')->send();

                return;
            }
            $this->cart[$existingIndex]['qty'] = $currentQty;
        } else {
            if (! $this->isStockAvailable($productId, 1)) {
                Notification::make()->warning()->title('Stok habis.')->send();

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
            Notification::make()->warning()->title('Stok tidak mencukupi.')->send();

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

                    $price = (float) $product->default_price;
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
            Notification::make()
                ->title('Penjualan gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Silakan coba lagi atau hubungi administrator.')
                ->danger()
                ->send();

            return;
        }

        $this->cart = [];
        $this->dispatch('$refresh');

        $printUrl = route('admin.sales.receipt', $sale);

        Notification::make()
            ->title('Penjualan selesai')
            ->body("Invoice \"{$sale->invoice_number}\" berhasil dibuat. Total: Rp ".number_format($sale->total, 0, ',', '.'))
            ->success()
            ->actions([
                Action::make('print')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->url($printUrl, shouldOpenInNewTab: true),
            ])
            ->send();
    }
}
