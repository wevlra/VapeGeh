<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice;

class InvoiceController extends Controller
{
    public function __invoke(StockMovement $stockMovement)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'staff']), 403);

        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        abort_if($stockMovement->type !== 'out', 404);

        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related', 'related.items.product']);

        $location = $stockMovement->location;
        $related = $stockMovement->related;
        $isSale = $related instanceof Sale;
        $isStockEntry = $related instanceof StockEntry;

        if ($isSale) {
            $related->loadMissing(['items.product']);
        }

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
            'Metode Pembayaran' => $paymentMethod,
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
        } elseif ($isStockEntry) {
            foreach ($related->items as $item) {
                $items[] = InvoiceItem::make($item->product->name ?? 'Product')
                    ->description($item->product->sku ?? '')
                    ->pricePerUnit((float) $item->unit_price)
                    ->quantity((int) $item->qty);
            }
        } else {
            $items[] = InvoiceItem::make($stockMovement->product->name ?? 'Product')
                ->description($stockMovement->product->sku ?? '')
                ->pricePerUnit((float) ($stockMovement->unit_price ?? 0))
                ->quantity(abs((int) $stockMovement->quantity));
        }

        $serialNumber = $isSale ? $related->invoice_number : ($isStockEntry ? '#SM-'.$stockMovement->id : '#SM-'.$stockMovement->id);

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
            ->notes($stockMovement->notes);

        if ($isSale) {
            $total = (float) $related->total;
            $invoice = $invoice->status($related->paid_amount >= $total ? 'PAID' : 'UNPAID');
        }

        if ($isStockEntry && $related->additional_costs) {
            $costs = collect($related->additional_costs);
            $totalCost = $costs->sum(fn ($c) => (float) ($c['amount'] ?? 0));
            $desc = $costs->pluck('description')->filter()->implode(', ');
            $invoice = $invoice->notes('Biaya Tambahan: '.$desc.' — Rp '.number_format($totalCost, 0, ',', '.'));
        }

        return $invoice->stream();
    }
}
