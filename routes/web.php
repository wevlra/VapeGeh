<?php

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice;

Route::livewire('/', 'landing');

Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/admin/history/{stockMovement}/receipt', function (StockMovement $stockMovement) {
        if (! in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized.');
        }
        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }
        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return view('receipts.receipt', ['movement' => $stockMovement]);
    })->name('admin.history.receipt');

    Route::get('/admin/history/{stockMovement}/invoice', function (StockMovement $stockMovement) {
        if (! in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized.');
        }
        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }
        if ($stockMovement->type !== 'out') {
            abort(404);
        }
        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related.items.product']);

        $location = $stockMovement->location;
        $related = $stockMovement->related;
        $isSale = $related instanceof Sale;

        $seller = new Party([
            'name' => config('store.name'),
            'address' => $location->address ?? config('store.address'),
            'phone' => config('store.phone'),
        ]);

        $buyerData = ['name' => 'Walk-in Customer'];
        if ($isSale && $related->user) {
            $buyerData['name'] = $related->user->name;
        } elseif ($stockMovement->buyer) {
            $buyerData['name'] = $stockMovement->buyer->name;
            if ($stockMovement->buyer->phone) {
                $buyerData['phone'] = $stockMovement->buyer->phone;
            }
            if ($stockMovement->buyer->email) {
                $buyerData['address'] = $stockMovement->buyer->email;
            }
        }

        $paymentMethod = $isSale ? match ($related->payment_method) {
            'cash' => 'Cash',
            'transfer' => 'Bank Transfer',
            'qris' => 'QRIS',
            default => ucfirst($related->payment_method ?? '-'),
        } : '-';

        $buyerData['custom_fields'] = [
            'payment_method' => $paymentMethod,
        ];

        $buyer = new Party($buyerData);

        $items = [];
        if ($isSale) {
            foreach ($related->items as $item) {
                $items[] = InvoiceItem::make($item->product->name ?? 'Product')
                    ->description($item->product->sku ?? '')
                    ->pricePerUnit((float) $item->price)
                    ->quantity((int) $item->qty);
            }
        } else {
            $items[] = InvoiceItem::make($stockMovement->product->name ?? 'Product')
                ->description($stockMovement->product->sku ?? '')
                ->pricePerUnit((float) ($stockMovement->unit_price ?? 0))
                ->quantity(abs((int) $stockMovement->quantity));
        }

        $serialNumber = $isSale ? $related->invoice_number : 'SM-'.$stockMovement->id;

        $notes = $stockMovement->notes ? $stockMovement->notes : null;

        $invoice = Invoice::make('vapegeh')
            ->serialNumberFormat('{SERIES}{SEQUENCE}')
            ->seller($seller)
            ->buyer($buyer)
            ->date($stockMovement->created_at)
            ->dateFormat('d M Y')
            ->currencySymbol('Rp')
            ->currencyCode('IDR')
            ->currencyFormat('{SYMBOL} {VALUE}')
            ->currencyThousandsSeparator('.')
            ->currencyDecimalPoint(',')
            ->currencyDecimals(0)
            ->filename($serialNumber)
            ->addItems($items)
            ->logo(public_path('assets/images/logo-light-tr.png'))
            ->notes($notes);

        if ($isSale) {
            $total = (float) $related->total;
            $invoice = $invoice->status($related->paid_amount >= $total ? 'PAID' : 'UNPAID');
        }

        return $invoice->stream();
    })->name('admin.history.invoice');

    Route::get('/admin/sales/{sale}/receipt', function (Sale $sale) {
        if (! in_array(auth()->user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized.');
        }
        if (auth()->user()->role === 'staff' && $sale->location_id !== auth()->user()->location_id) {
            abort(403);
        }
        $movement = $sale->stockMovements()->first();
        if (! $movement) {
            abort(404);
        }
        $movement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return view('receipts.receipt', ['movement' => $movement]);
    })->name('admin.sales.receipt');
});
