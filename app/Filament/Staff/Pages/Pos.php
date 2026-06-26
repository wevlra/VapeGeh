<?php

namespace App\Filament\Staff\Pages;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use BackedEnum;
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

    protected static ?string $navigationLabel = 'Cashier';

    protected static \UnitEnum|string|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Cashier';

    protected string $view = 'filament.staff.pages.pos';

    public array $cart = [];

    public function table(Table $table): Table
    {
        $locationId = auth()->user()->location_id;

        return $table
            ->query(
                Product::query()
                    ->whereHas('vendor')
                    ->whereHas('stocks', fn ($q) => $q
                        ->where('location_id', $locationId)
                        ->where('qty', '>', 0),
                    )
                    ->with(['stocks' => fn ($q) => $q->where('location_id', $locationId)])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('stock_qty')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->getStateUsing(fn (Product $record): int => $record->stocks->first()?->qty ?? 0),
                TextColumn::make('store_price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->actions([
                Action::make('addToCart')
                    ->label('Add')
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
            $item['price'] = $product->store_price;
            $item['subtotal'] = $product->store_price * $item['qty'];

            return $item;
        })->filter()->values()->toArray();
    }

    public function getCartTotal(): float
    {
        return collect($this->getCartItems())->sum('subtotal');
    }

    public function addToCart(int $productId): void
    {
        $existingIndex = collect($this->cart)->search(fn ($item) => $item['product_id'] === $productId);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty']++;
        } else {
            $this->cart[] = [
                'product_id' => $productId,
                'qty' => 1,
            ];
        }
    }

    public function updateQty(int $index, int $qty): void
    {
        if ($qty < 1) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);

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

    public function checkoutAction(): Action
    {
        return Action::make('checkout')
            ->label('Checkout')
            ->icon('heroicon-m-credit-card')
            ->modalHeading('Checkout')
            ->modalContent(view('filament.staff.pages.checkout-summary'))
            ->form([
                Radio::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Cash',
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
                    ->placeholder('Catatan untuk transaksi ini...'),
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
            return 'Kembalian: Rp '.number_format($paidAmount - $total, 0, ',', '.');
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
        $total = $this->getCartTotal();
        $locationId = auth()->user()->location_id;
        $paidAmount = (float) ($data['paid_amount'] ?? 0);

        $sale = DB::transaction(function () use ($total, $locationId, $data, $paidAmount) {
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'location_id' => $locationId,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            $cartItems = $this->getCartItems();

            foreach ($cartItems as $item) {
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('location_id', $locationId)
                    ->first();

                if (! $stock || $stock->qty < $item['qty']) {
                    throw new \DomainException(
                        "Insufficient stock for product \"{$item['name']}\"."
                    );
                }

                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $stock->decrement('qty', $item['qty']);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'location_id' => $locationId,
                    'type' => 'out',
                    'quantity' => $item['qty'],
                    'related_type' => Sale::class,
                    'related_id' => $sale->id,
                    'notes' => "Sale {$sale->invoice_number}",
                    'created_by' => auth()->id(),
                ]);
            }

            return $sale;
        });

        $this->cart = [];

        Notification::make()
            ->title("Sale {$sale->invoice_number} created")
            ->success()
            ->send();
    }
}
